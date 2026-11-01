import json,subprocess
from pathlib import Path
M=r'C:\Program Files\Kubernetes\Minikube\minikube.exe'
d=json.loads(subprocess.check_output([M,'-p','tienda-prod','kubectl','--','get','pod','prometheus-monitoreo-kube-prometheus-prometheus-0','-n','monitoreo','-o','json']))
image=next(c['image'] for c in d['spec']['containers'] if c['name']=='prometheus')
data={'alertas-operacion.rules.json':Path('output/evidencias/cierre-final-20261031/alertas-operacion.rules.json').read_text(),'alertas-operacion.test.json':Path('tests/alertas-operacion.test.json').read_text()}
cm={'apiVersion':'v1','kind':'ConfigMap','metadata':{'name':'pruebas-alertas','namespace':'monitoreo'},'data':data}
pod={'apiVersion':'v1','kind':'Pod','metadata':{'name':'pruebas-alertas','namespace':'monitoreo'},'spec':{'automountServiceAccountToken':False,'restartPolicy':'Never','securityContext':{'runAsNonRoot':True,'runAsUser':65534,'fsGroup':65534,'seccompProfile':{'type':'RuntimeDefault'}},'containers':[{'name':'promtool','image':image,'command':['/bin/promtool','test','rules','/reglas/alertas-operacion.test.json'],'workingDir':'/reglas','resources':{'requests':{'cpu':'10m','memory':'32Mi'},'limits':{'cpu':'500m','memory':'128Mi'}},'securityContext':{'allowPrivilegeEscalation':False,'readOnlyRootFilesystem':True,'capabilities':{'drop':['ALL']}},'volumeMounts':[{'name':'reglas','mountPath':'/reglas','readOnly':True},{'name':'tmp','mountPath':'/tmp'}]}],'volumes':[{'name':'reglas','configMap':{'name':'pruebas-alertas'}},{'name':'tmp','emptyDir':{}}]}}
Path('tmp/pruebas-alertas.json').write_text(json.dumps({'apiVersion':'v1','kind':'List','items':[cm,pod]}),encoding='utf-8')
