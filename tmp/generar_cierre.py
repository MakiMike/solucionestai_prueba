from pathlib import Path
import json
R=[]
def obj(kind,name,spec=None,ns='monitoreo',**extra):
 d={'apiVersion':'v1','kind':kind,'metadata':{'name':name,'namespace':ns}}
 if spec is not None:d['spec']=spec
 d.update(extra);R.append(d);return d
def deployment(name,image,args,mounts,volumes,port,uid,request,limit,sa=None):
 c={'name':name,'image':image,'args':args,'ports':[{'name':'http','containerPort':port}], 'resources':{'requests':{'cpu':'20m','memory':request},'limits':{'cpu':'500m','memory':limit}},'securityContext':{'runAsNonRoot':True,'runAsUser':uid,'allowPrivilegeEscalation':False,'readOnlyRootFilesystem':True,'capabilities':{'drop':['ALL']}},'volumeMounts':mounts}
 pod={'automountServiceAccountToken':bool(sa),'securityContext':{'fsGroup':uid,'seccompProfile':{'type':'RuntimeDefault'}},'containers':[c],'volumes':volumes}
 if sa:pod['serviceAccountName']=sa
 d=obj('Deployment',name,{'replicas':1,'strategy':{'type':'Recreate'},'selector':{'matchLabels':{'app':name}},'template':{'metadata':{'labels':{'app':name}},'spec':pod}});d['apiVersion']='apps/v1'
 obj('Service',name,{'selector':{'app':name},'ports':[{'name':'http','port':port,'targetPort':'http'}]})
 return c
obj('PersistentVolumeClaim','loki-datos',{'storageClassName':'csi-hostpath-sc','accessModes':['ReadWriteOnce'],'resources':{'requests':{'storage':'1Gi'}}})
loki='''auth_enabled: false
server:
  http_listen_port: 3100
common:
  path_prefix: /loki
  replication_factor: 1
  ring:
    kvstore:
      store: inmemory
  storage:
    filesystem:
      chunks_directory: /loki/chunks
      rules_directory: /loki/rules
schema_config:
  configs:
    - from: 2024-01-01
      store: tsdb
      object_store: filesystem
      schema: v13
      index:
        prefix: index_
        period: 24h
compactor:
  working_directory: /loki/compactor
  retention_enabled: true
  delete_request_store: filesystem
limits_config:
  retention_period: 24h
  ingestion_rate_mb: 2
  ingestion_burst_size_mb: 4
  max_query_parallelism: 2
  max_global_streams_per_user: 1000
analytics:
  reporting_enabled: false
'''
obj('ConfigMap','loki-config',data={'loki.yaml':loki})
c=deployment('loki-tienda','grafana/loki:3.7.9',['-config.file=/etc/loki/loki.yaml'],[{'name':'config','mountPath':'/etc/loki','readOnly':True},{'name':'datos','mountPath':'/loki'},{'name':'tmp','mountPath':'/tmp'}],[{'name':'config','configMap':{'name':'loki-config'}},{'name':'datos','persistentVolumeClaim':{'claimName':'loki-datos'}},{'name':'tmp','emptyDir':{}}],3100,10001,'96Mi','384Mi')
c['readinessProbe']={'httpGet':{'path':'/ready','port':'http'},'initialDelaySeconds':15,'periodSeconds':10}
obj('ServiceAccount','alloy-logs',automountServiceAccountToken=True)
for ns in ['tienda-prod','kube-system']:
 d=obj('Role','tienda-lector-logs',ns=ns,rules=[{'apiGroups':[''],'resources':['pods'],'verbs':['get','list','watch']},{'apiGroups':[''],'resources':['pods/log'],'verbs':['get']}]);d['apiVersion']='rbac.authorization.k8s.io/v1'
 d=obj('RoleBinding','tienda-lector-logs',ns=ns,roleRef={'apiGroup':'rbac.authorization.k8s.io','kind':'Role','name':'tienda-lector-logs'},subjects=[{'kind':'ServiceAccount','name':'alloy-logs','namespace':'monitoreo'}]);d['apiVersion']='rbac.authorization.k8s.io/v1'
alloy='''logging { level = "warn" }
discovery.kubernetes "pods" {
  role = "pod"
  namespaces { names = ["tienda-prod", "kube-system"] }
}
discovery.relabel "logs" {
  targets = discovery.kubernetes.pods.targets
  rule {
    source_labels = ["__meta_kubernetes_namespace", "__meta_kubernetes_pod_label_app", "__meta_kubernetes_pod_label_app_kubernetes_io_name"]
    regex = "tienda-prod;(backend|frontend);.*|kube-system;.*;traefik"
    action = "keep"
  }
  rule {
    source_labels = ["__meta_kubernetes_namespace"]
    target_label = "namespace"
  }
  rule {
    source_labels = ["__meta_kubernetes_pod_name"]
    target_label = "pod"
  }
  rule {
    source_labels = ["__meta_kubernetes_pod_container_name"]
    target_label = "container"
  }
  rule {
    source_labels = ["__meta_kubernetes_pod_node_name"]
    target_label = "node"
  }
}
loki.source.kubernetes "pods" {
  targets = discovery.relabel.logs.output
  forward_to = [loki.write.local.receiver]
}
loki.write "local" {
  endpoint { url = "http://loki-tienda:3100/loki/api/v1/push" }
}
'''
obj('ConfigMap','alloy-config',data={'config.alloy':alloy})
deployment('alloy-logs','grafana/alloy:v1.21.3',['run','/etc/alloy/config.alloy','--storage.path=/var/lib/alloy','--server.http.listen-addr=0.0.0.0:12345'],[{'name':'config','mountPath':'/etc/alloy','readOnly':True},{'name':'datos','mountPath':'/var/lib/alloy'},{'name':'tmp','mountPath':'/tmp'}],[{'name':'config','configMap':{'name':'alloy-config'}},{'name':'datos','emptyDir':{}},{'name':'tmp','emptyDir':{}}],12345,473,'32Mi','192Mi','alloy-logs')
obj('ConfigMap','tienda-backup-metrics',data={'backup.prom':'tienda_backup_last_success_timestamp_seconds 0\ntienda_backup_last_run_success 0\n'})
obj('ConfigMap','backup-nginx',data={'default.conf':'server { listen 8080; location = /metrics { default_type text/plain; alias /metrics/backup.prom; } location = /healthz { return 200 "ok"; } }'})
deployment('backup-metrics','tienda-frontend:0.1.1',[],[{'name':'config','mountPath':'/etc/nginx/conf.d','readOnly':True},{'name':'metrics','mountPath':'/metrics','readOnly':True},{'name':'tmp','mountPath':'/tmp'}],[{'name':'config','configMap':{'name':'backup-nginx'}},{'name':'metrics','configMap':{'name':'tienda-backup-metrics'}},{'name':'tmp','emptyDir':{}}],8080,101,'16Mi','64Mi')
sm=obj('ServiceMonitor','tienda-backup',{'selector':{'matchLabels':{'app':'backup-metrics'}},'endpoints':[{'port':'http','path':'/metrics','interval':'30s'}]});sm['apiVersion']='monitoring.coreos.com/v1';sm['metadata']['labels']={'release':'monitoreo'}
for x in R:
 if x['kind']=='Service':x['metadata']['labels']={'app':x['metadata']['name']}
cm=obj('ConfigMap','grafana-loki-tienda',data={'loki.yaml':json.dumps({'apiVersion':1,'datasources':[{'name':'Logs TSkull-Shirt','uid':'loki-tienda','type':'loki','access':'proxy','url':'http://loki-tienda:3100','isDefault':False,'editable':False}]})});cm['metadata']['labels']={'grafana_datasource':'1'}
Path('k8s/minikube/observabilidad.yaml').write_text(json.dumps({'apiVersion':'v1','kind':'List','items':R},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
exprs=[
 ('TiendaAplicacionNoDisponible','kube_deployment_status_replicas_available{namespace="tienda-prod",deployment=~"backend|frontend"} < 1','1m','critical','Aplicacion sin replicas disponibles'),
 ('TiendaNodoNoDisponible','kube_node_status_condition{node=~"tienda-prod.*",condition="Ready",status="true"} == 0','1m','critical','Nodo de produccion no Ready'),
 ('TiendaTasaErroresAlta','sum(rate(traefik_service_requests_total{service=~".*tienda-prod.*",code=~"5.."}[5m])) / clamp_min(sum(rate(traefik_service_requests_total{service=~".*tienda-prod.*"}[5m])), 0.001) > 0.01','2m','warning','Mas del 1 por ciento de solicitudes con 5xx'),
 ('TiendaSaturacion','(sum by(pod)(rate(container_cpu_usage_seconds_total{namespace="tienda-prod",container!="",container!="POD"}[5m])) / sum by(pod)(kube_pod_container_resource_limits{namespace="tienda-prod",resource="cpu"}) > 0.90) or (sum by(pod)(container_memory_working_set_bytes{namespace="tienda-prod",container!="",container!="POD"}) / sum by(pod)(kube_pod_container_resource_limits{namespace="tienda-prod",resource="memory"}) > 0.85)','5m','warning','CPU o memoria cerca del limite'),
 ('TiendaRespaldoVencidoOFallido','(time() - tienda_backup_last_success_timestamp_seconds > 93600) or (tienda_backup_last_run_success == 0) or absent(tienda_backup_last_success_timestamp_seconds)','1m','critical','Respaldo fallido, sin metrica o con mas de 26 horas')]
rule={'apiVersion':'monitoring.coreos.com/v1','kind':'PrometheusRule','metadata':{'name':'tienda-operacion','namespace':'monitoreo','labels':{'release':'monitoreo'}},'spec':{'groups':[{'name':'tienda.operacion','rules':[{'alert':a,'expr':e,'for':f,'labels':{'severity':s,'proyecto':'tienda'},'annotations':{'summary':t,'description':'Consultar docs/OPERACION_Y_VALIDACION.md y registrar diagnostico, intervencion y recuperacion.'}} for a,e,f,s,t in exprs]}]}}
Path('k8s/minikube/alertas-operacion.yaml').write_text(json.dumps(rule,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print('Logging, metricas de respaldo y cinco alertas generados.')
