import json
from pathlib import Path
r=json.load(open('k8s/minikube/alertas-operacion.yaml',encoding='utf-8'));groups=r['spec']['groups'];rules=groups[0]['rules']
out=Path('output/evidencias/cierre-final-20261031')
(out/'alertas-operacion.rules.json').write_text(json.dumps({'groups':groups},indent=2),encoding='utf-8')
def series(name,values):return {'series':name,'values':values}
def unit(rule,inputs,labels,when):
 return {'interval':'1m','input_series':inputs,'alert_rule_test':[{'eval_time':when,'alertname':rule['alert'],'exp_alerts':[{'exp_labels':{**rule['labels'],**labels},'exp_annotations':rule['annotations']}]}]}
tests=[
unit(rules[0],[series('kube_deployment_status_replicas_available{namespace="tienda-prod",deployment="backend"}','0+0x10')],{'namespace':'tienda-prod','deployment':'backend'},'2m'),
unit(rules[1],[series('kube_node_status_condition{node="tienda-prod-m03",condition="Ready",status="true"}','0+0x10')],{'node':'tienda-prod-m03','condition':'Ready','status':'true'},'2m'),
unit(rules[2],[series('traefik_service_requests_total{service="tienda-prod-frontend",code="200"}','0+98x20'),series('traefik_service_requests_total{service="tienda-prod-frontend",code="500"}','0+2x20')],{},'10m'),
unit(rules[3],[series('container_memory_working_set_bytes{namespace="tienda-prod",container="backend",pod="backend-prueba"}','900+0x20'),series('kube_pod_container_resource_limits{namespace="tienda-prod",resource="memory",pod="backend-prueba"}','1000+0x20')],{'pod':'backend-prueba'},'6m'),
unit(rules[4],[series('tienda_backup_last_success_timestamp_seconds','0+0x10'),series('tienda_backup_last_run_success','0+0x10')],{},'2m')]
healthy=[series('kube_deployment_status_replicas_available{namespace="tienda-prod",deployment="backend"}','2+0x20'),series('kube_node_status_condition{node="tienda-prod-m03",condition="Ready",status="true"}','1+0x20'),series('tienda_backup_last_success_timestamp_seconds','1000000000+0x20'),series('tienda_backup_last_run_success','1+0x20')]
tests.append({'interval':'1m','input_series':healthy,'alert_rule_test':[{'eval_time':'10m','alertname':x['alert'],'exp_alerts':[]} for x in rules]})
doc={'rule_files':['alertas-operacion.rules.json'],'evaluation_interval':'1m','tests':tests}
Path('tests/alertas-operacion.test.json').write_text(json.dumps(doc,indent=2,ensure_ascii=False)+'\n',encoding='utf-8')
panels=[]
queries=[('Replicas disponibles','kube_deployment_status_replicas_available{namespace="tienda-prod",deployment=~"backend|frontend"}'),('CPU por Pod','sum by(pod)(rate(container_cpu_usage_seconds_total{namespace="tienda-prod",container!="",container!="POD"}[5m]))'),('Memoria por Pod','sum by(pod)(container_memory_working_set_bytes{namespace="tienda-prod",container!="",container!="POD"})'),('p95 HTTP (s)','histogram_quantile(0.95, sum by(le)(rate(traefik_service_request_duration_seconds_bucket{service=~".*tienda-prod.*"}[5m])))'),('Errores 5xx (req/s)','sum(rate(traefik_service_requests_total{service=~".*tienda-prod.*",code=~"5.."}[5m]))'),('Antiguedad respaldo (horas)','(time()-tienda_backup_last_success_timestamp_seconds)/3600')]
for i,(title,expr) in enumerate(queries):panels.append({'id':i+1,'title':title,'type':'timeseries','gridPos':{'x':(i%2)*12,'y':(i//2)*7,'w':12,'h':7},'datasource':{'type':'prometheus','uid':'prometheus'},'targets':[{'refId':'A','expr':expr}]})
panels.append({'id':7,'title':'Logs centralizados de aplicacion por Pod/nodo','type':'logs','gridPos':{'x':0,'y':21,'w':24,'h':10},'datasource':{'type':'loki','uid':'loki-tienda'},'targets':[{'refId':'A','expr':'{namespace="tienda-prod"}'}]})
dashboard={'uid':'tskull-operacion','title':'TSkull-Shirt — operacion','schemaVersion':39,'version':1,'refresh':'30s','time':{'from':'now-30m','to':'now'},'panels':panels,'tags':['tienda','practica']}
cm={'apiVersion':'v1','kind':'ConfigMap','metadata':{'name':'grafana-dashboard-tskull','namespace':'monitoreo','labels':{'grafana_dashboard':'1'}},'data':{'tskull-operacion.json':json.dumps(dashboard,ensure_ascii=False)}}
Path('k8s/minikube/dashboard-operacion.yaml').write_text(json.dumps(cm,indent=2,ensure_ascii=False)+'\n',encoding='utf-8')
