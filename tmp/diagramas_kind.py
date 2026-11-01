from pathlib import Path
p=Path('tmp/crear_diagramas.py');s=p.read_text(encoding='utf8');start=s.index('# deployment development');end=s.index('# relational')
new='''# deployment development
D=Diagram('04_desarrollo','4  Desarrollo en Docker Desktop y kind','Una laptop Windows · entorno Linux WSL 2 de Docker · sin máquinas virtuales administradas por separado')
D.group(55,145,1690,875,'Laptop · Docker Desktop · clúster kind-tienda-dev · namespace tienda-dev')
D.box(90,215,390,130,'Navegador en Windows','Futura entrada HTTPS\\ntienda-dev.test:8443','white');D.box(570,215,1120,130,'Nodo contenedor: tienda-dev-control-plane','Control plane + cargas de desarrollo · IP interna asignada por Docker','infra');D.arrow([(480,280),(570,280)],'8443',labelpos=(525,235))
D.group(570,385,1120,570,'Cargas previstas en el único nodo')
D.box(610,440,440,130,'Traefik + frontend','Traefik por instalar · NodePort 30443\\nService frontend:8080 · Nginx x1','app');D.box(1200,440,450,130,'Backend PHP-FPM x1','Service backend:9000\\nSesiones compartidas en PostgreSQL','app');D.arrow([(1050,505),(1200,505)],'FastCGI',labelpos=(1125,465))
D.box(610,640,440,145,'PostgreSQL x1','Service :5432 · StatefulSet\\nPVC data-postgres-0 · 5 GiB','data');D.box(1200,640,450,145,'MongoDB x1','Service :27017 · StatefulSet\\nPVC data-mongo-0 · 5 GiB','data');D.arrow([(1360,570),(1360,605),(830,605),(830,640)]);D.arrow([(1450,570),(1450,640)])
D.text(620,825,'PVC uploads: 2 GiB · RWO · almacenamiento local de kind\\nJob de migración y semilla · CronJobs de vencimiento y outbox',22)
D.box(90,420,390,185,'Red del clúster','Calico: CNI + NetworkPolicy\\nCoreDNS · Services internos\\nRed Docker compartida: kind','infra');D.box(90,670,390,215,'Observabilidad pendiente','Sondas declaradas\\nMétricas, logs y paneles\\nse incorporan después','pending')
D.note(1035,'Mapeo futuro 127.0.0.1:8443 → nodo:30443. Sin controlador y certificado no hay sitio publicado.');D.save()
# prod
D=Diagram('05_produccion','5  Producción de laboratorio con kind','Los tres nodos son contenedores en la misma laptop; comparten recursos y el mismo dominio de fallo')
D.group(50,145,1700,875,'Docker Desktop / WSL 2 · clúster kind-tienda-prod · namespace tienda-prod')
D.box(85,210,550,125,'tienda-prod-control-plane','API y control del clúster\\nNodo contenedor, no una VM dedicada','infra');D.box(775,210,930,125,'Entrada HTTPS futura','127.0.0.1:9443 → worker:30443 → Traefik por instalar\\nService frontend:8080 → Service backend:9000','infra')
for x,title in [(85,'tienda-prod-worker'),(920,'tienda-prod-worker2')]:
 D.group(x,395,785,375,title);D.box(x+25,450,340,100,'Frontend','Deployment · 1 Pod Nginx','app');D.box(x+405,450,350,100,'Backend','Deployment · 1 Pod inicial','app')
D.box(110,600,730,130,'PostgreSQL x1 · ubicación ilustrativa','Service :5432 · StatefulSet · PVC 5 GiB\\nUbicación final depende del scheduler y el volumen','data');D.box(945,600,730,130,'MongoDB x1 · ubicación ilustrativa','Service :27017 · StatefulSet · PVC 5 GiB\\nNo se configura replicación del motor','data')
D.box(85,820,500,155,'Persistencia prevista','PVC de BD separados\\nuploads: shared-rwx · 2 GiB\\nProveedor RWX aún pendiente','infra');D.box(635,820,510,155,'Operación','HPA backend: 2–6 · CPU 70 %\\nDistribución por hostname + PDB\\nCronJobs y Job de migración','infra');D.box(1195,820,510,155,'Plataforma','Calico + CoreDNS\\nObservabilidad y métricas\\npendientes de completar','pending')
D.note(1035,'Dos réplicas por capa al inicio. El HPA crea Pods, no más recursos físicos. Si falla la laptop, cae todo.');D.save()
# network
D=Diagram('06_comunicacion','6  Comunicación local en una sola laptop','Rediseño para kind · Las IP internas se obtendrán con docker inspect y kubectl; no son IP de máquinas virtuales')
D.box(55,160,510,145,'Windows: navegador y PowerShell','Administración: contextos kind-tienda-dev / prod\\nAPI Kubernetes en 127.0.0.1:puerto asignado','white');D.box(750,160,980,145,'Docker Desktop con entorno Linux WSL 2','Red bridge Docker llamada kind · NAT/salida de Docker\\nFirewall del host controla publicación · no se añade VLAN ni VPN','infra');D.arrow([(565,235),(750,235)],'API local',labelpos=(655,195))
D.group(55,395,790,405,'Desarrollo · kind-tienda-dev')
D.box(90,455,720,115,'Navegador → 127.0.0.1:8443','Mapeo → nodo:30443 → Traefik (pendiente)\\nFrontend :8080 → backend FastCGI :9000','app');D.text(90,610,'Nodos: IP dinámicas de la red Docker kind\\nPods: 10.244.0.0/16 · Services: 10.96.0.0/16\\nCalico + DNS interno UDP/TCP 53\\nPostgreSQL :5432 · MongoDB :27017 internos',22)
D.group(925,395,805,405,'Producción · kind-tienda-prod')
D.box(960,455,735,115,'Navegador → 127.0.0.1:9443','Mapeo → worker:30443 → Traefik (pendiente)\\nFrontend :8080 → backend FastCGI :9000','app');D.text(960,610,'Nodos: IP dinámicas de la red Docker kind\\nPods: 10.245.0.0/16 · Services: 10.97.0.0/16\\nCalico + DNS interno UDP/TCP 53\\nPostgreSQL :5432 · MongoDB :27017 internos',22)
D.arrow([(1050,305),(1050,350),(450,350),(450,395)]);D.arrow([(1400,305),(1400,395)])
D.box(55,855,1675,170,'Alcance de red y límites','DNS local futuro: tienda-dev.test y tienda-prod.test → 127.0.0.1 (puertos distintos).\\nNo se utiliza ServiceLB de K3s ni pool VIP. Traefik se instalará con NodePort 30443.\\nLos clústeres comparten la red Docker y el host: no hay aislamiento físico ni tolerancia a su pérdida.','pending');D.save()
'''
s=s[:start]+new+s[end:]
s=s.replace('La red de la figura 06 contiene direcciones propuestas, no IP reales verificadas.','La figura 06 usa CIDR de los archivos kind. Las IP de nodos y puertos API se obtendrán al crear los clústeres.').replace('No se modificó el Google Doc ni el código de la tienda para crear estas referencias.','Los diagramas sustituyen la propuesta anterior con K3s y máquinas virtuales. No se han desplegado clústeres.')
p.write_text(s,encoding='utf8')
