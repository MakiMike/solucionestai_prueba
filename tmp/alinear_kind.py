from pathlib import Path
import json
for env,port in [('development','8443'),('production','9443')]:
 p=Path('k8s/overlays')/env/'kustomization.yaml';d=json.loads(p.read_text())
 for item in d['patches']:
  if item.get('target',{}).get('kind')=='ConfigMap':
   changes=json.loads(item['patch'])
   for change in changes:
    if change['path']=='/data/APP_BASE_URL':change['value']=change['value'].rstrip('/')+':'+port+'/'
   item['patch']=json.dumps(changes,indent=2)+'\n'
 p.write_text(json.dumps(d,indent=2)+'\n')
p=Path('k8s/base/resources.yaml');s=p.read_text().replace('https://tienda-dev.test/','https://tienda-dev.test:8443/');p.write_text(s)
p=Path('docs/KUBERNETES.md');s=p.read_text(encoding='utf8');s=s.replace('# Tienda de ropa alternativa', '# Tienda de ropa alternativa\n\nActualización del 24/10/2026: el laboratorio usa Docker Desktop + kind sobre una sola laptop. Para crear los clústeres sigue primero [INICIO_KIND.md](INICIO_KIND.md). Esta guía corresponde a las etapas posteriores de aplicación y datos; no debe ejecutarse durante el arranque inicial. Se descarta el procedimiento anterior con K3s y cuatro máquinas virtuales.',1)
s=s.replace('No se afirma alta disponibilidad', 'No se afirma alta disponibilidad')
s=s.replace('Configurar `tienda-dev.test` hacia la IP real del controlador y confiar en su CA.', 'Configurar `tienda-dev.test` hacia 127.0.0.1 y confiar en su CA; el acceso previsto es https://tienda-dev.test:8443/. Instalar Traefik como NodePort 30443 antes de esperar acceso web.')
s=s.replace('con certificados de `tienda-prod.test`', 'con certificados de `tienda-prod.test`')
s=s.replace('certificados de `tienda-prod.test`', 'certificados de `tienda-prod.test` y acceso https://tienda-prod.test:9443/')
s=s.replace('mecanismo de IP de entrada y certificado TLS local válido', 'mapeo local de puertos de kind hacia el NodePort 30443 de Traefik y certificado TLS local válido')
p.write_text(s,encoding='utf8')
