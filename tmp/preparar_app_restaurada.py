import json
from pathlib import Path

root = Path('k8s/minikube')
namespace = 'tienda-restore-20261025-235828'
items = json.loads((root / 'desarrollo/aplicacion.yaml').read_text(encoding='utf-8-sig'))['items']
secret = lambda key: {'secretKeyRef': {'name': 'restore-credentials', 'key': key}}
for item in items:
    item['metadata']['namespace'] = namespace
    if item['kind'] != 'Deployment':
        continue
    spec = item['spec']['template']['spec']
    spec.pop('serviceAccountName', None)
    spec.pop('initContainers', None)
    for volume in spec['volumes']:
        if volume['name'] == 'uploads':
            volume['persistentVolumeClaim']['claimName'] = 'fotos-datos'
    for container in spec['containers']:
        for mount in container['volumeMounts']:
            if mount['name'] == 'uploads':
                mount['subPath'] = 'fotos'
                mount['readOnly'] = True
        if container['name'] == 'backend':
            container['envFrom'] = [{'configMapRef': {'name': 'restore-app-config'}}]
            container['env'] = [
                {'name': 'DB_PASSWORD', 'valueFrom': secret('password')},
                {'name': 'RESTORE_PASSWORD', 'valueFrom': secret('password')},
                {'name': 'MONGO_URI', 'value': 'mongodb://restore_owner:$(RESTORE_PASSWORD)@mongo:27017/tienda?authSource=admin'},
                {'name': 'APP_ENCRYPTION_KEY', 'valueFrom': secret('password')},
            ]

def resource(kind, name, spec, api='v1'):
    return {'apiVersion': api, 'kind': kind, 'metadata': {'name': name, 'namespace': namespace}, 'spec': spec}

config = {'apiVersion': 'v1', 'kind': 'ConfigMap', 'metadata': {'name': 'restore-app-config', 'namespace': namespace}, 'data': {
    'CI_ENV': 'production', 'APP_BASE_URL': 'http://127.0.0.1:9080/',
    'APP_SESSION_DRIVER': 'database', 'APP_COOKIE_SECURE': 'false',
    'DB_HOST': 'postgres', 'DB_PORT': '5432', 'DB_DATABASE': 'tienda',
    'DB_USERNAME': 'restore_owner', 'DB_SSLMODE': 'disable',
    'MONGO_DATABASE': 'tienda', 'HOME': '/tmp',
}}
items.insert(0, config)
for name, port in [('postgres', 5432), ('mongo', 27017)]:
    items.append(resource('Service', name, {'selector': {'app': name + '-restore'}, 'ports': [{'port': port, 'targetPort': port}]}))

def policy(name, app, **rules):
    spec = {'podSelector': {'matchLabels': {'app': app}},
            'policyTypes': [direction.capitalize() for direction in rules], **rules}
    items.append(resource('NetworkPolicy', name, spec, 'networking.k8s.io/v1'))

def peer(app):
    return {'podSelector': {'matchLabels': {'app': app}}}

def port(n):
    return {'protocol': 'TCP', 'port': n}

policy('restore-frontend-backend', 'frontend', egress=[{'to': [peer('backend')], 'ports': [port(9000)]}])
policy('restore-backend', 'backend',
       ingress=[{'from': [peer('frontend')], 'ports': [port(9000)]}],
       egress=[{'to': [peer('postgres-restore')], 'ports': [port(5432)]},
               {'to': [peer('mongo-restore')], 'ports': [port(27017)]}])
for app in ['backend', 'frontend']:
    policy('restore-dns-' + app, app, egress=[{
        'to': [{'namespaceSelector': {'matchLabels': {'kubernetes.io/metadata.name': 'kube-system'}},
                'podSelector': {'matchLabels': {'k8s-app': 'kube-dns'}}}],
        'ports': [{'protocol': protocol, 'port': 53} for protocol in ['UDP', 'TCP']]
    }])
for name, n in [('postgres', 5432), ('mongo', 27017)]:
    policy('restore-' + name, name + '-restore', ingress=[{'from': [peer('backend')], 'ports': [port(n)]}])

doc = {'apiVersion': 'v1', 'kind': 'List', 'items': items}
assert all(x['metadata']['namespace'] == namespace for x in items)
assert not any(x['kind'] in ['Secret', 'PersistentVolumeClaim', 'StatefulSet', 'CronJob', 'Job'] for x in items)
for deploy in [x for x in items if x['kind'] == 'Deployment']:
    spec = deploy['spec']['template']['spec']
    assert deploy['spec']['replicas'] == 1
    assert spec['automountServiceAccountToken'] is False
    assert next(v for v in spec['volumes'] if v['name'] == 'uploads')['persistentVolumeClaim']['claimName'] == 'fotos-datos'
    assert next(m for m in spec['containers'][0]['volumeMounts'] if m['name'] == 'uploads')['readOnly'] is True
(root / 'restauracion/aplicacion-recuperada.yaml').write_text(json.dumps(doc, indent=2) + '\n', encoding='utf-8')
print('Manifiesto generado y validado: 2 Deployments, 4 Services, 1 ConfigMap, 6 NetworkPolicies; solo namespace de restauracion.')
