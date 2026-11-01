from pathlib import Path
import json
p=Path('k8s/minikube/restauracion/aplicacion-recuperada.yaml')
data=json.loads(p.read_text(encoding='utf-8'))
for item in data['items']:
 item.get('metadata',{}).pop('namespace',None)
s=json.dumps(data,ensure_ascii=False,indent=2)+'\n'
s=s.replace('tienda-backend:0.1.2','tienda-backend:0.1.6').replace('tienda-frontend:0.1.0','tienda-frontend:0.1.1')
p.write_text(s,encoding='utf-8')
