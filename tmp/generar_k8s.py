from pathlib import Path
import json
class yaml:
 @staticmethod
 def safe_dump(obj,**kwargs): return json.dumps(obj,indent=2)+'\n'
 @staticmethod
 def safe_dump_all(objs,**kwargs): return '\n---\n'.join(json.dumps(o,indent=2) for o in objs)+'\n'

root=Path('k8s'); (root/'base').mkdir(parents=True,exist_ok=True)
sec={'runAsNonRoot':True,'allowPrivilegeEscalation':False,'readOnlyRootFilesystem':True,'capabilities':{'drop':['ALL']},'seccompProfile':{'type':'RuntimeDefault'}}
def container(name,image,port=None,uid=33):
 c={'name':name,'image':image,'imagePullPolicy':'IfNotPresent','securityContext':dict(sec,runAsUser=uid),'resources':{'requests':{'cpu':'100m','memory':'128Mi'},'limits':{'cpu':'1','memory':'512Mi'}}}
 if port:c['ports']=[{'containerPort':port}]
 return c
def envfrom(c): c['envFrom']=[{'configMapRef':{'name':'tienda-config'}}];c['env']=[{'name':key,'valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':key}}} for key in ['DB_PASSWORD','MONGO_URI','APP_ENCRYPTION_KEY']];return c
def pod(c,role,uid=33):return {'metadata':{'labels':{'app':role}},'spec':{'serviceAccountName':'tienda','automountServiceAccountToken':False,'securityContext':{'fsGroup':uid,'seccompProfile':{'type':'RuntimeDefault'}},'containers':[c]}}
def deployment(role,c):return {'apiVersion':'apps/v1','kind':'Deployment','metadata':{'name':role},'spec':{'replicas':1,'selector':{'matchLabels':{'app':role}},'template':pod(c,role)}}
def service(name,port):return {'apiVersion':'v1','kind':'Service','metadata':{'name':name},'spec':{'selector':{'app':name},'ports':[{'port':port,'targetPort':port}]}}
def pvc(name,access='ReadWriteOnce',size='2Gi'):return {'apiVersion':'v1','kind':'PersistentVolumeClaim','metadata':{'name':name},'spec':{'accessModes':[access],'resources':{'requests':{'storage':size}}}}
objects=[{'apiVersion':'v1','kind':'ServiceAccount','metadata':{'name':'tienda'},'automountServiceAccountToken':False}, {'apiVersion':'v1','kind':'ConfigMap','metadata':{'name':'tienda-config'},'data':{'CI_ENV':'production','APP_BASE_URL':'https://tienda-dev.test/','APP_SESSION_DRIVER':'database','APP_COOKIE_SECURE':'true','DB_HOST':'postgres','DB_PORT':'5432','DB_DATABASE':'tienda','DB_USERNAME':'tienda_app','DB_SSLMODE':'prefer','MONGO_DATABASE':'tienda','HOME':'/tmp'}}, {'apiVersion':'v1','kind':'ConfigMap','metadata':{'name':'database-init'},'data':{'postgres.sh':Path('docker/postgres/init-app.sh').read_text(),'mongo.js':Path('docker/mongo/init-app.js').read_text()}},pvc('uploads')]
for role,image,port,uid in [('backend','tienda-backend:0.1.0',9000,33),('frontend','tienda-frontend:0.1.0',8080,101)]:
 c=container(role,image,port,uid)
 if role=='backend':
  envfrom(c);c['livenessProbe']={'tcpSocket':{'port':9000},'initialDelaySeconds':10,'periodSeconds':15};c['readinessProbe']={'exec':{'command':['php','-r',"require 'application/services/TiendaConnection.php'; TiendaConnection::open()->query('SELECT evento_id FROM tienda_outbox LIMIT 0');"]},'initialDelaySeconds':5,'timeoutSeconds':5}
 else:
  c['livenessProbe']={'httpGet':{'path':'/healthz','port':8080},'initialDelaySeconds':5};c['readinessProbe']={'httpGet':{'path':'/health/ready','port':8080},'timeoutSeconds':5}
 c['volumeMounts']=[{'name':'uploads','mountPath':'/var/www/html/uploads','readOnly':role=='frontend'},{'name':'tmp','mountPath':'/tmp'}]
 d=deployment(role,c);d['spec']['template']['spec']['volumes']=[{'name':'uploads','persistentVolumeClaim':{'claimName':'uploads'}},{'name':'tmp','emptyDir':{}}]
 if role=='backend':
  c['volumeMounts'].append({'name':'logs','mountPath':'/var/www/html/application/logs'});d['spec']['template']['spec']['volumes'].append({'name':'logs','emptyDir':{}})
 objects.extend([d,service(role,port)])
for role,image,port,init in [('postgres','postgres:17.6-bookworm',5432,'postgres.sh'),('mongo','mongo:8.0.14',27017,'mongo.js')]:
 c=container(role,image,port,999)
 c['resources']={'requests':{'cpu':'250m','memory':'256Mi'},'limits':{'cpu':'1','memory':'1Gi'}}
 if role=='postgres':
  c['env']=[{'name':'POSTGRES_DB','value':'tienda'},{'name':'POSTGRES_USER','value':'tienda_owner'},{'name':'POSTGRES_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'DB_OWNER_PASSWORD'}}},{'name':'DB_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'DB_PASSWORD'}}},{'name':'PGDATA','value':'/var/lib/postgresql/data/pgdata'}]
  mount='/var/lib/postgresql/data';dest='/docker-entrypoint-initdb.d/10-app.sh'
  c['readinessProbe']={'exec':{'command':['pg_isready','-U','tienda_owner','-d','tienda']},'initialDelaySeconds':5}
 else:
  c['env']=[{'name':'HOME','value':'/tmp'},{'name':'MONGO_DATABASE','value':'tienda'},{'name':'MONGO_INITDB_ROOT_USERNAME','value':'tienda_owner'},{'name':'MONGO_INITDB_ROOT_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'MONGO_OWNER_PASSWORD'}}},{'name':'MONGO_APP_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'MONGO_APP_PASSWORD'}}}]
  mount='/data/db';dest='/docker-entrypoint-initdb.d/10-app.js';c['readinessProbe']={'tcpSocket':{'port':port},'initialDelaySeconds':10}
 c['startupProbe']={'tcpSocket':{'port':port},'periodSeconds':5,'failureThreshold':60};c['livenessProbe']={'tcpSocket':{'port':port},'initialDelaySeconds':30,'periodSeconds':15}
 c['volumeMounts']=[{'name':'data','mountPath':mount},{'name':'init','mountPath':dest,'subPath':init,'readOnly':True},{'name':'tmp','mountPath':'/tmp'}]
 template=pod(c,role,999);template['spec']['volumes']=[{'name':'init','configMap':{'name':'database-init','defaultMode':365}},{'name':'tmp','emptyDir':{}}]
 extra='/var/run/postgresql' if role=='postgres' else '/data/configdb';c['volumeMounts'].append({'name':'runtime','mountPath':extra});template['spec']['volumes'].append({'name':'runtime','emptyDir':{}})
 state={'apiVersion':'apps/v1','kind':'StatefulSet','metadata':{'name':role},'spec':{'serviceName':role,'replicas':1,'selector':{'matchLabels':{'app':role}},'template':template,'volumeClaimTemplates':[{'metadata':{'name':'data'},'spec':{'accessModes':['ReadWriteOnce'],'resources':{'requests':{'storage':'5Gi'}}}}]}}
 objects.extend([state,service(role,port)])
for name,action in [('vencer-pedidos','expire'),('sincronizar-historial','sync')]:
 c=envfrom(container(name,'tienda-backend:0.1.0'));c['command']=['php','bin/tienda.php',action];c['volumeMounts']=[{'name':'tmp','mountPath':'/tmp'}]
 template=pod(c,'worker');template['spec'].update({'restartPolicy':'OnFailure','volumes':[{'name':'tmp','emptyDir':{}}]})
 objects.append({'apiVersion':'batch/v1','kind':'CronJob','metadata':{'name':name},'spec':{'schedule':'* * * * *','concurrencyPolicy':'Forbid','startingDeadlineSeconds':60,'successfulJobsHistoryLimit':1,'failedJobsHistoryLimit':2,'jobTemplate':{'spec':{'backoffLimit':2,'activeDeadlineSeconds':50,'template':template}}}})
c=envfrom(container('migrate','tienda-backend:0.1.0'));c['env']=[{'name':'DB_USERNAME','value':'tienda_owner'},{'name':'DB_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'DB_OWNER_PASSWORD'}}},{'name':'MONGO_URI','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'MONGO_OWNER_URI'}}}];c['env'] += [{'name':key,'valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':key}}} for key in ['SEED_ADMIN_PASSWORD','SEED_CLIENT_PASSWORD']];c['command']=['sh','-c','php bin/tienda.php migrate && php bin/tienda.php seed && php bin/tienda.php grant && php bin/tienda.php mongo-init']
template=pod(c,'migration');template['spec']['restartPolicy']='OnFailure'
# Job separado: se aplica manualmente después de que los motores estén disponibles.
(root/'migration-job.yaml').write_text(yaml.safe_dump({'apiVersion':'batch/v1','kind':'Job','metadata':{'name':'tienda-migrate'},'spec':{'backoffLimit':3,'template':template}},sort_keys=False))
objects.append({'apiVersion':'networking.k8s.io/v1','kind':'Ingress','metadata':{'name':'tienda'},'spec':{'ingressClassName':'traefik','tls':[{'hosts':['tienda-dev.test'],'secretName':'tienda-tls'}],'rules':[{'host':'tienda-dev.test','http':{'paths':[{'path':'/','pathType':'Prefix','backend':{'service':{'name':'frontend','port':{'number':8080}}}}]}}]}})
# Denegación por defecto, DNS y flujos internos explícitos.
objects.append({'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'deny-all'},'spec':{'podSelector':{},'policyTypes':['Ingress','Egress']}})
objects.append({'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'allow-dns'},'spec':{'podSelector':{},'policyTypes':['Egress'],'egress':[{'to':[{'namespaceSelector':{'matchLabels':{'kubernetes.io/metadata.name':'kube-system'}}}],'ports':[{'protocol':'UDP','port':53},{'protocol':'TCP','port':53}]}]}})
for dest,port,sources in [('backend',9000,['frontend']),('postgres',5432,['backend','worker','migration']),('mongo',27017,['backend','worker','migration'])]:
 objects.append({'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'ingress-'+dest},'spec':{'podSelector':{'matchLabels':{'app':dest}},'policyTypes':['Ingress'],'ingress':[{'from':[{'podSelector':{'matchLabels':{'app':s}}} for s in sources],'ports':[{'protocol':'TCP','port':port}]}]}})
 for source in sources:
  objects.append({'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'egress-'+source+'-'+dest},'spec':{'podSelector':{'matchLabels':{'app':source}},'policyTypes':['Egress'],'egress':[{'to':[{'podSelector':{'matchLabels':{'app':dest}}}],'ports':[{'protocol':'TCP','port':port}]}]}})
objects.append({'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'ingress-frontend'},'spec':{'podSelector':{'matchLabels':{'app':'frontend'}},'policyTypes':['Ingress'],'ingress':[{'from':[{'namespaceSelector':{'matchLabels':{'kubernetes.io/metadata.name':'kube-system'}},'podSelector':{'matchLabels':{'app.kubernetes.io/name':'traefik'}}}],'ports':[{'protocol':'TCP','port':8080}]}]}})
(root/'base'/'resources.yaml').write_text(yaml.safe_dump_all(objects,sort_keys=False))
(root/'base'/'kustomization.yaml').write_text('apiVersion: kustomize.config.k8s.io/v1beta1\nkind: Kustomization\nresources:\n  - resources.yaml\n')
for name,ns,host in [('development','tienda-dev','tienda-dev.test'),('production','tienda-prod','tienda-prod.test')]:
 folder=root/'overlays'/name;folder.mkdir(parents=True,exist_ok=True)
 (folder/'namespace.yaml').write_text(yaml.safe_dump({'apiVersion':'v1','kind':'Namespace','metadata':{'name':ns,'labels':{'pod-security.kubernetes.io/enforce':'restricted','pod-security.kubernetes.io/audit':'restricted','pod-security.kubernetes.io/warn':'restricted'}}},sort_keys=False))
 patches=[{'target':{'kind':'ConfigMap','name':'tienda-config'},'patch':yaml.safe_dump([{'op':'replace','path':'/data/APP_BASE_URL','value':'https://'+host+'/'}])},{'target':{'kind':'Ingress','name':'tienda'},'patch':yaml.safe_dump([{'op':'replace','path':'/spec/tls/0/hosts/0','value':host},{'op':'replace','path':'/spec/rules/0/host','value':host}])}]
 resources=['../../base','namespace.yaml']
 k={'apiVersion':'kustomize.config.k8s.io/v1beta1','kind':'Kustomization','namespace':ns,'resources':resources,'patches':patches}
 if name=='production':
  k['replicas']=[{'name':'frontend','count':2},{'name':'backend','count':2}]
  k['patches'].append({'target':{'kind':'PersistentVolumeClaim','name':'uploads'},'patch':yaml.safe_dump([{'op':'replace','path':'/spec/accessModes/0','value':'ReadWriteMany'},{'op':'add','path':'/spec/storageClassName','value':'shared-rwx'}])})
  for role in ['frontend','backend']:
   k['patches'].append({'target':{'kind':'Deployment','name':role},'patch':yaml.safe_dump([{'op':'add','path':'/spec/template/spec/topologySpreadConstraints','value':[{'maxSkew':1,'topologyKey':'kubernetes.io/hostname','whenUnsatisfiable':'DoNotSchedule','labelSelector':{'matchLabels':{'app':role}}}]}])})
  resources.append('scaling.yaml')
  (folder/'scaling.yaml').write_text(yaml.safe_dump_all([{'apiVersion':'autoscaling/v2','kind':'HorizontalPodAutoscaler','metadata':{'name':'backend'},'spec':{'scaleTargetRef':{'apiVersion':'apps/v1','kind':'Deployment','name':'backend'},'minReplicas':2,'maxReplicas':6,'metrics':[{'type':'Resource','resource':{'name':'cpu','target':{'type':'Utilization','averageUtilization':70}}}]}},*({'apiVersion':'policy/v1','kind':'PodDisruptionBudget','metadata':{'name':role},'spec':{'minAvailable':1,'selector':{'matchLabels':{'app':role}}}} for role in ['frontend','backend'])],sort_keys=False))
 (folder/'kustomization.yaml').write_text(yaml.safe_dump(k,sort_keys=False))
print('Manifiestos de ambos ambientes generados')
