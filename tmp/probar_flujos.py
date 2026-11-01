from pathlib import Path
import subprocess,json,csv,datetime
M=r'C:\Program Files\Kubernetes\Minikube\minikube.exe';ns='tienda-dev';out=Path('output/evidencias/cierre-final-20261031')
def k(*args, data=None,check=True):
 p=subprocess.run([M,'-p',ns,'kubectl','--',*args],input=json.dumps(data) if data else None,text=True,capture_output=True,encoding='utf-8',errors='replace')
 if check and p.returncode:raise RuntimeError(p.stderr[:300])
 return p
sec={'runAsNonRoot':True,'runAsUser':33,'allowPrivilegeEscalation':False,'readOnlyRootFilesystem':True,'capabilities':{'drop':['ALL']}}
pod={'apiVersion':'v1','kind':'Pod','metadata':{'name':'auditoria-red','namespace':ns,'labels':{'app':'auditoria-red'}},'spec':{'serviceAccountName':'tienda','automountServiceAccountToken':False,'restartPolicy':'Never','securityContext':{'fsGroup':33,'seccompProfile':{'type':'RuntimeDefault'}},'containers':[{'name':'probe','image':'tienda-backend:0.1.6','command':['sleep','1200'],'securityContext':sec,'resources':{'requests':{'cpu':'5m','memory':'16Mi'},'limits':{'cpu':'100m','memory':'64Mi'}},'volumeMounts':[{'name':'tmp','mountPath':'/tmp'}]}],'volumes':[{'name':'tmp','emptyDir':{}}]}}
selector={'matchExpressions':[{'key':'app','operator':'In','values':['postgres','mongo']}]}
egress={'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'auditoria-egress','namespace':ns},'spec':{'podSelector':{'matchLabels':{'app':'auditoria-red'}},'policyTypes':['Egress'],'egress':[{'to':[{'podSelector':selector}],'ports':[{'protocol':'TCP','port':5432},{'protocol':'TCP','port':27017}]}]}}
ingress={'apiVersion':'networking.k8s.io/v1','kind':'NetworkPolicy','metadata':{'name':'auditoria-ingress','namespace':ns},'spec':{'podSelector':selector,'policyTypes':['Ingress'],'ingress':[{'from':[{'podSelector':{'matchLabels':{'app':'auditoria-red'}}}],'ports':[{'protocol':'TCP','port':5432},{'protocol':'TCP','port':27017}]}]}}
probe=out/'probe.php';probe.write_text('''<?php
$r=[];foreach(['postgres'=>5432,'mongo'=>27017] as $host=>$port){$ip=gethostbyname($host);$s=@fsockopen($host,$port,$errno,$err,2);$r[$host]=['dns'=>$ip!==$host,'tcp'=>(bool)$s];if($s)fclose($s);}echo json_encode($r);
''',encoding='utf-8')
rows=[]
try:
 k('apply','-f','-',data=pod);k('wait','--for=condition=ready','pod/auditoria-red','-n',ns,'--timeout=120s')
 k('cp',str(probe),ns+'/auditoria-red:/tmp/probe.php')
 for phase,action,expected in [('Solo egress: ingress bloquea','egress',False),('Ingress y egress permiten','ingress',True),('Solo ingress: egress bloquea','delete',False),('Restablecer ambos','egress',True)]:
  if action=='delete':k('delete','networkpolicy','auditoria-egress','-n',ns)
  else:k('apply','-f','-',data=egress if action=='egress' else ingress)
  import time;time.sleep(2)
  observed=json.loads(k('exec','auditoria-red','-n',ns,'--','php','/tmp/probe.php').stdout)
  for host,result in observed.items():
   good=result['dns'] and result['tcp']==expected
   rows.append({'fechaUTC':datetime.datetime.now(datetime.timezone.utc).isoformat(),'fase':phase,'motor':host,'dns':result['dns'],'tcp':result['tcp'],'esperado':expected,'correcto':good})
   print(phase,host,'DNS',result['dns'],'TCP',result['tcp'],'OK',good)
   if not good:raise RuntimeError('Resultado de politica distinto al esperado')
finally:
 for name in ['auditoria-egress','auditoria-ingress']:k('delete','networkpolicy',name,'-n',ns,'--ignore-not-found',check=False)
 k('delete','pod','auditoria-red','-n',ns,'--ignore-not-found','--wait=false',check=False)
 if rows:
  with (out/'flujos-ingress-egress.csv').open('w',encoding='utf-8-sig',newline='') as f:w=csv.DictWriter(f,fieldnames=rows[0].keys());w.writeheader();w.writerows(rows)
