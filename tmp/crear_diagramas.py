from PIL import Image,ImageDraw,ImageFont
from pathlib import Path
from html import escape
import math,zipfile
O=Path('output/diagramas'); W,H=1800,1150
COL={'app':'#eee8f7','data':'#e1f0eb','infra':'#e7eef8','pending':'#fff1d9','white':'#ffffff'}
class Diagram:
 def __init__(self,name,title,sub):
  self.name=name;self.im=Image.new('RGB',(W,H),'#faf9f6');self.d=ImageDraw.Draw(self.im);self.svg=[f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}"><rect width="1800" height="1150" fill="#faf9f6"/>'];self.text(55,35,title,36,bold=True);self.text(55,90,sub,20);self.text(55,1095,'Tienda de ropa alternativa · Referencia para redibujar · Diseño previsto, no evidencia de despliegue',19)
 def text(self,x,y,s,size=22,bold=False,center=False,color='#263444'):
  font=ImageFont.truetype('C:/Windows/Fonts/arial'+('bd' if bold else '')+'.ttf',size)
  for i,line in enumerate(s.split('\n')):
   yy=y+i*int(size*1.35); xx=x-self.d.textlength(line,font=font)/2 if center else x
   self.d.text((xx,yy),line,font=font,fill=color)
   self.svg.append(f'<text x="{xx}" y="{yy+size}" font-family="Arial" font-size="{size}" font-weight="{700 if bold else 400}" fill="{color}">{escape(line)}</text>')
 def rect(self,x,y,w,h,fill='white',dash=False):
  color=COL.get(fill,fill);self.d.rounded_rectangle((x,y,x+w,y+h),radius=14,fill=color,outline='#6d7a88',width=2)
  self.svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="14" fill="{color}" stroke="#6d7a88" stroke-width="2"'+(' stroke-dasharray="8 5"' if dash else '')+'/>')
 def box(self,x,y,w,h,title,body='',fill='app',size=22):
  self.rect(x,y,w,h,fill);self.text(x+w/2,y+15,title,size,True,True)
  if body:self.text(x+w/2,y+54,body,size-2,center=True)
 def group(self,x,y,w,h,title,pending=False):
  self.rect(x,y,w,h,'#f1f3f5',pending);self.text(x+18,y+12,title,22,True)
 def arrow(self,pts,label='',color='#506576',dash=False,labelpos=None):
  self.d.line(pts,fill=color,width=3)
  x,y=pts[-1];a,b=pts[-2];ang=math.atan2(y-b,x-a);tri=[(x,y),(x-14*math.cos(ang-.5),y-14*math.sin(ang-.5)),(x-14*math.cos(ang+.5),y-14*math.sin(ang+.5))];self.d.polygon(tri,fill=color)
  points=' '.join(f'{x},{y}' for x,y in pts);self.svg.append(f'<polyline points="{points}" fill="none" stroke="{color}" stroke-width="3"'+(' stroke-dasharray="9 6"' if dash else '')+'/>');self.svg.append('<polygon points="'+' '.join(f'{a},{b}' for a,b in tri)+f'" fill="{color}"/>')
  if label:
   xx,yy=labelpos or ((pts[0][0]+pts[-1][0])/2,(pts[0][1]+pts[-1][1])/2-30);self.text(xx,yy,label,18,center=True,color=color)
 def note(self,y,t):self.text(65,y,t,20)
 def save(self):
  self.text(55,1065,'Leyenda: morado = aplicación · verde = datos · azul = infraestructura · amarillo = pendiente o excepción',18)
  self.im.save(O/(self.name+'.png'));(O/(self.name+'.svg')).write_text('\n'.join(self.svg)+ '</svg>',encoding='utf8')
# 1
D=Diagram('01_aplicacion','1  Arquitectura de la aplicación','Responsabilidades y protocolos · La interfaz actual se renderiza en el servidor')
D.box(60,200,280,135,'Cliente y administrador','Navegador\nRoles distintos','white')
D.box(440,200,300,135,'Entrada y frontend','Traefik HTTPS\nNginx: estáticos y proxy','infra')
D.box(870,165,430,220,'Backend modular','PHP + CodeIgniter\nSesión, permisos y CSRF\nControladores y respuestas JSON','app')
D.arrow([(340,265),(440,265)],'HTTPS 443',labelpos=(390,225));D.arrow([(740,265),(870,265)],'FastCGI 9000',labelpos=(805,225))
D.box(750,480,310,155,'Catálogo y usuarios','Productos · tallas\nUsuarios · bajas lógicas','app');D.box(1140,480,380,155,'Compras e inventario','Carrito · reserva de 30 min\nPago simulado · estados','app')
D.arrow([(1000,385),(1000,435),(905,435),(905,480)]);D.arrow([(1180,385),(1180,435),(1330,435),(1330,480)])
D.box(600,790,470,150,'PostgreSQL','Fuente de verdad + sesiones\nPedidos, stock y tabla outbox','data');D.arrow([(900,635),(900,790)],'SQL 5432',labelpos=(1000,710));D.arrow([(1320,635),(1320,710),(1060,710),(1060,790)])
D.box(1210,790,480,150,'MongoDB','Historial consultable de pedidos\nDocumentos flexibles e índice único','data');D.box(120,790,340,150,'Procesos programados','Vencer pedidos\nEnviar eventos pendientes','infra');D.arrow([(600,860),(460,860)],'Outbox',labelpos=(530,820));D.arrow([(290,940),(290,1000),(1450,1000),(1450,940)],'Driver MongoDB · TCP 27017',labelpos=(850,970))
D.arrow([(1300,270),(1740,270),(1740,860),(1690,860)],'Consultar historial · 27017',labelpos=(1510,320))
D.note(1030,'El historial admite retraso. Las decisiones de compra siempre se validan contra PostgreSQL.');D.save()
# sequences
class Sequence(Diagram):
 def lanes(self,names):
  self.xs=[110+i*(1580/(len(names)-1)) for i in range(len(names))]
  for x,n in zip(self.xs,names):
   self.box(x-100,155,200,85,n,fill='infra',size=21);self.d.line([(x,245),(x,1035)],fill='#a4afba',width=2);self.svg.append(f'<line x1="{x}" y1="245" x2="{x}" y2="1035" stroke="#a4afba" stroke-dasharray="6 5"/>')
 def msg(self,a,b,y,t):self.arrow([(self.xs[a],y),(self.xs[b],y)],t,labelpos=((self.xs[a]+self.xs[b])/2,y-32))
D=Sequence('02_secuencia_crud','2  Secuencia de un CRUD de productos','Ejemplo: modificar una prenda · Solo el administrador puede guardar cambios');D.lanes(['Administrador','Nginx','Controlador','PostgreSQL'])
for a,b,y,t in [(0,1,300,'1. Abrir formulario de edición'),(1,2,385,'2. Solicitar producto y tallas'),(2,3,465,'3. Consultar datos'),(3,2,545,'4. Datos para el formulario'),(0,2,640,'5. Enviar cambios y token CSRF'),(2,3,760,'6. Transacción: producto + stock por talla'),(3,2,850,'7. Commit o rollback'),(2,0,970,'8. Confirmación o mensaje de error')]:D.msg(a,b,y,t)
D.text(700,690,'Validar sesión, rol, campos y cantidades antes de escribir',20,True,True)
D.note(1030,'Errores: sin permisos o CSRF → rechazar; datos inválidos → corregir; error SQL → revertir la operación.');D.save()
D=Sequence('03_secuencia_dos_bases','3  Reserva e historial con dos bases de datos','El pago ocurre después, desde la cuenta del cliente; no requiere autorización del administrador');D.lanes(['Cliente','Backend','PostgreSQL','Worker','MongoDB'])
for a,b,y,t in [(0,1,300,'1. Crear pedido'),(1,2,390,'2. Bloquear stock; guardar pedido + outbox'),(2,1,475,'3. COMMIT: reserva de 30 minutos'),(1,0,550,'4. Pedido pendiente y plazo de pago'),(3,2,630,'5. Leer evento pendiente'),(3,4,710,'6. Upsert por evento_id'),(4,3,790,'7. Fallo temporal: evento no enviado'),(3,4,885,'8. Tras recuperación: reintentar mismo evento'),(4,3,945,'8b. Escritura confirmada'),(3,2,1010,'9. Marcar enviado en PostgreSQL')]:D.msg(a,b,y,t)
D.text(80,1037,'Si falla la transacción SQL, no hay pedido ni reserva. Si falla MongoDB, el pedido se conserva.',20)
D.text(1110,825,'Índice único evita duplicados',18,bold=True);D.save()
# deployment development
D=Diagram('04_desarrollo','4  Desarrollo en Docker Desktop y kind','Una laptop Windows · entorno Linux WSL 2 de Docker · sin máquinas virtuales administradas por separado')
D.group(55,145,1690,875,'Laptop · Docker Desktop · clúster kind-tienda-dev · namespace tienda-dev')
D.box(90,215,390,130,'Navegador en Windows','Futura entrada HTTPS\ntienda-dev.test:8443','white');D.box(570,215,1120,130,'Nodo contenedor: tienda-dev-control-plane','Control plane + cargas de desarrollo · IP interna asignada por Docker','infra');D.arrow([(480,280),(570,280)],'8443',labelpos=(525,235))
D.group(570,385,1120,570,'Cargas previstas en el único nodo')
D.box(610,440,440,130,'Traefik + frontend','Traefik por instalar · NodePort 30443\nService frontend:8080 · Nginx x1','app');D.box(1200,440,450,130,'Backend PHP-FPM x1','Service backend:9000\nSesiones compartidas en PostgreSQL','app');D.arrow([(1050,505),(1200,505)],'FastCGI',labelpos=(1125,465))
D.box(610,640,440,145,'PostgreSQL x1','Service :5432 · StatefulSet\nPVC data-postgres-0 · 5 GiB','data');D.box(1200,640,450,145,'MongoDB x1','Service :27017 · StatefulSet\nPVC data-mongo-0 · 5 GiB','data');D.arrow([(1360,570),(1360,605),(830,605),(830,640)]);D.arrow([(1450,570),(1450,640)])
D.text(620,825,'PVC uploads: 2 GiB · RWO · almacenamiento local de kind\nJob de migración y semilla · CronJobs de vencimiento y outbox',22)
D.box(90,420,390,185,'Red del clúster','Calico: CNI + NetworkPolicy\nCoreDNS · Services internos\nRed Docker compartida: kind','infra');D.box(90,670,390,215,'Observabilidad pendiente','Sondas declaradas\nMétricas, logs y paneles\nse incorporan después','pending')
D.note(1035,'Mapeo futuro 127.0.0.1:8443 → nodo:30443. Sin controlador y certificado no hay sitio publicado.');D.save()
# prod
D=Diagram('05_produccion','5  Producción de laboratorio con kind','Los tres nodos son contenedores en la misma laptop; comparten recursos y el mismo dominio de fallo')
D.group(50,145,1700,875,'Docker Desktop / WSL 2 · clúster kind-tienda-prod · namespace tienda-prod')
D.box(85,210,550,125,'tienda-prod-control-plane','API y control del clúster\nNodo contenedor, no una VM dedicada','infra');D.box(775,210,930,125,'Entrada HTTPS futura','127.0.0.1:9443 → worker:30443 → Traefik por instalar\nService frontend:8080 → Service backend:9000','infra')
for x,title in [(85,'tienda-prod-worker'),(920,'tienda-prod-worker2')]:
 D.group(x,395,785,375,title);D.box(x+25,450,340,100,'Frontend','Deployment · 1 Pod Nginx','app');D.box(x+405,450,350,100,'Backend','Deployment · 1 Pod inicial','app')
D.box(110,600,730,130,'PostgreSQL x1 · ubicación ilustrativa','Service :5432 · StatefulSet · PVC 5 GiB\nUbicación final depende del scheduler y el volumen','data');D.box(945,600,730,130,'MongoDB x1 · ubicación ilustrativa','Service :27017 · StatefulSet · PVC 5 GiB\nNo se configura replicación del motor','data')
D.box(85,820,500,155,'Persistencia prevista','PVC de BD separados\nuploads: shared-rwx · 2 GiB\nProveedor RWX aún pendiente','infra');D.box(635,820,510,155,'Operación','HPA backend: 2–6 · CPU 70 %\nDistribución por hostname + PDB\nCronJobs y Job de migración','infra');D.box(1195,820,510,155,'Plataforma','Calico + CoreDNS\nObservabilidad y métricas\npendientes de completar','pending')
D.note(1035,'Dos réplicas por capa al inicio. El HPA crea Pods, no más recursos físicos. Si falla la laptop, cae todo.');D.save()
# network
D=Diagram('06_comunicacion','6  Comunicación local en una sola laptop','Rediseño para kind · Las IP internas se obtendrán con docker inspect y kubectl; no son IP de máquinas virtuales')
D.box(55,160,510,145,'Windows: navegador y PowerShell','Administración: contextos kind-tienda-dev / prod\nAPI Kubernetes en 127.0.0.1:puerto asignado','white');D.box(750,160,980,145,'Docker Desktop con entorno Linux WSL 2','Red bridge Docker llamada kind · NAT/salida de Docker\nFirewall del host controla publicación · no se añade VLAN ni VPN','infra');D.arrow([(565,235),(750,235)],'API local',labelpos=(655,195))
D.group(55,395,790,405,'Desarrollo · kind-tienda-dev')
D.box(90,455,720,115,'Navegador → 127.0.0.1:8443','Mapeo → nodo:30443 → Traefik (pendiente)\nFrontend :8080 → backend FastCGI :9000','app');D.text(90,610,'Nodos: IP dinámicas de la red Docker kind\nPods: 10.244.0.0/16 · Services: 10.96.0.0/16\nCalico + DNS interno UDP/TCP 53\nPostgreSQL :5432 · MongoDB :27017 internos',22)
D.group(925,395,805,405,'Producción · kind-tienda-prod')
D.box(960,455,735,115,'Navegador → 127.0.0.1:9443','Mapeo → worker:30443 → Traefik (pendiente)\nFrontend :8080 → backend FastCGI :9000','app');D.text(960,610,'Nodos: IP dinámicas de la red Docker kind\nPods: 10.245.0.0/16 · Services: 10.97.0.0/16\nCalico + DNS interno UDP/TCP 53\nPostgreSQL :5432 · MongoDB :27017 internos',22)
D.arrow([(1050,305),(1050,350),(450,350),(450,395)]);D.arrow([(1400,305),(1400,395)])
D.box(55,855,1675,170,'Alcance de red y límites','DNS local futuro: tienda-dev.test y tienda-prod.test → 127.0.0.1 (puertos distintos).\nNo se utiliza ServiceLB de K3s ni pool VIP. Traefik se instalará con NodePort 30443.\nLos clústeres comparten la red Docker y el host: no hay aislamiento físico ni tolerancia a su pérdida.','pending');D.save()
# relational
D=Diagram('07_modelo_datos','7  Modelo de datos de la tienda','Apoyo adicional · Relaciones principales y proyección documental')
D.box(55,180,430,180,'us_usuarios','PK us_id\nCorreo único · rol · us_activo','data');D.box(680,180,430,180,'pedidos','PK ped_id · FK us_id\nEstado · total · clave · vence_en','data');D.arrow([(485,270),(680,270)],'1 → N')
D.box(1280,180,455,180,'tienda_outbox','PK evento_id · FK ped_id\nFK actor_id → us_usuarios\nTipo · datos JSON · enviado_en','data');D.arrow([(1110,270),(1280,270)],'1 → N')
D.box(680,510,430,210,'pedido_detalle','PK pde_id · FK ped_id\nFK pla_id · FK tal_id\nCantidad · precio · nombre y talla\nSe conservan al comprar','data');D.arrow([(900,360),(900,510)],'1 → N',labelpos=(990,420))
D.box(55,510,430,180,'playeras','PK pla_id\nPrecio · activa · edición limitada','data');D.arrow([(485,600),(680,600)],'1 → N')
D.box(1280,510,455,180,'tallas','PK tal_id\nNombre · orden','data');D.arrow([(1280,600),(1110,600)],'1 → N')
D.box(430,850,610,160,'playera_tallas','PK pta_id · FK pla_id · FK tal_id\nUNIQUE (pla_id, tal_id) · stock disponible','data');D.arrow([(270,690),(270,930),(430,930)]);D.arrow([(1505,690),(1505,790),(850,790),(850,850)])
D.box(1210,850,525,160,'MongoDB · eventos','evento_id único · pedido_id · fecha\nTipo y datos variables · sin FK entre motores','app');D.arrow([(1510,360),(1790,360),(1790,1050),(1460,1050),(1460,1010)],'Worker outbox',labelpos=(1600,1030));D.save()
# states
D=Diagram('08_estados_pedido','8  Estados del pedido y plazo de pago','Apoyo adicional · El servidor valida el tiempo con el reloj de PostgreSQL')
D.box(60,225,320,145,'Carrito','Todavía no hay reserva','white');D.box(550,225,450,145,'Pendiente de pago','Reserva de 30 minutos\nPlazo inicia al crear pedido','app');D.arrow([(380,295),(550,295)],'Crear pedido',labelpos=(465,255))
D.box(1230,225,460,145,'Confirmado','Pago simulado por el cliente\nSin autorización administrativa','data');D.arrow([(1000,295),(1230,295)],'Pagar dentro del plazo',labelpos=(1115,255))
D.box(440,535,360,145,'Cancelado','Cliente cancela antes de pagar\nDevuelve reserva una vez','pending');D.box(875,535,360,145,'Vencido','30 minutos sin pagar\nDevuelve reserva una vez','pending');D.arrow([(680,370),(680,455),(620,455),(620,535)],'Cancelar',labelpos=(575,430));D.arrow([(900,370),(900,455),(1055,455),(1055,535)],'Plazo agotado',labelpos=(1040,430))
D.box(1270,535,420,145,'En preparación','Solo administrador\nRequiere pedido confirmado','app');D.arrow([(1480,370),(1480,535)],'Preparar',labelpos=(1570,445))
D.box(1270,850,420,145,'Listo para entrega','Administrador termina preparación','app');D.arrow([(1480,680),(1480,850)],'Marcar listo',labelpos=(1570,760))
D.box(680,850,420,145,'Entregado','Administrador registra entrega','data');D.arrow([(1270,920),(1100,920)],'Entregar',labelpos=(1185,880))
D.text(65,840,'No se puede pagar ni reactivar un pedido vencido.\nPara comprar de nuevo, crear otro sujeto a existencias.\nPagar no vuelve a descontar inventario.\nNo se permiten saltos de estado.',23);D.save()
readme='''DIAGRAMAS DE REFERENCIA PARA LA TIENDA

01 a 06: figuras obligatorias de la sección 3.1 del documento.
07: modelo de datos adicional. 08: estados de pedido adicional.

PNG: imagen lista para consultar o insertar.
SVG: versión vectorial editable en Inkscape, Figma u otro editor compatible.

Las topologías son de diseño, no capturas ni evidencia de implementación.
La figura 06 usa CIDR de los archivos kind. Las IP de nodos y puertos API se obtendrán al crear los clústeres.
La ubicación de las bases en producción es ilustrativa: no se han fijado por worker en los manifiestos.
shared-rwx, observabilidad, TLS de motores y la entrada externa siguen pendientes de completar y probar.
La figura 01 resume el sistema; la lectura del historial la hace el backend con el driver MongoDB.
Las flechas indican el sentido principal de solicitud o envío, no todas las respuestas de red.
El modelo 07 omite campos de perfil, recuperación de contraseña y sesiones para facilitar el redibujado.
Los diagramas sustituyen la propuesta anterior con K3s y máquinas virtuales. No se han desplegado clústeres.
'''
(O/'LEEME.txt').write_text(readme,encoding='utf8')
with zipfile.ZipFile(O/'Diagramas_Tienda_Alternativa.zip','w',zipfile.ZIP_DEFLATED) as z:
 for p in O.iterdir():
  if p.suffix in ['.png','.svg','.txt']:z.write(p,p.name)
print('8 PNG + 8 SVG + paquete ZIP listos')
