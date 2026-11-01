import json
from pathlib import Path

root = Path('k8s/minikube')

def ca_client(spec):
    container = spec['containers'][0]
    container['image'] = 'tienda-backend:0.1.2'
    for name in ('postgres-ca', 'mongo-ca'):
        spec['volumes'] = [v for v in spec['volumes'] if v['name'] != name]
        spec['volumes'].append({'name': name, 'configMap': {'name': name}})
        container['volumeMounts'] = [v for v in container['volumeMounts'] if v['name'] != name]
        container['volumeMounts'].append({'name': name, 'mountPath': '/etc/' + name, 'readOnly': True})
    for name, value in [('DB_SSLMODE', 'verify-full'), ('DB_SSLROOTCERT', '/etc/postgres-ca/ca.crt')]:
        container['env'] = [e for e in container['env'] if e['name'] != name]
        container['env'].append({'name': name, 'value': value})

for filename in ('aplicacion.yaml', 'tareas-programadas.yaml'):
    path = root / filename
    doc = json.loads(path.read_text(encoding='utf-8-sig'))
    for item in doc['items']:
        if item['kind'] == 'Deployment' and item['metadata']['name'] == 'backend':
            spec = item['spec']['template']['spec']
            ca_client(spec)
            for init in spec.get('initContainers', []):
                if init['name'] == 'preparar-fotos':
                    init['image'] = 'tienda-backend:0.1.2'
        elif item['kind'] == 'CronJob':
            ca_client(item['spec']['jobTemplate']['spec']['template']['spec'])
    path.write_text(json.dumps(doc, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
