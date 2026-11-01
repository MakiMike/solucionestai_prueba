from pathlib import Path
import json,re
p=Path('tests/http.php');s=p.read_text(encoding='utf-8')
start=s.index('function requestHttp(');end=s.index('function csrfFrom(',start)
s=s[:start]+'''// Cliente HTTP nativo: no requiere la extensión curl en la imagen PHP.
function requestHttp($path,$fields=null){
 global $jar;
 $cookies=is_file($jar)?json_decode(file_get_contents($jar),true):array();
 $headers=array('Connection: close');
 if($cookies){$pairs=array();foreach($cookies as $k=>$v)$pairs[]=$k.'='.$v;$headers[]='Cookie: '.implode('; ',$pairs);}
 $options=array('method'=>$fields===null?'GET':'POST','header'=>implode("\\r\\n",$headers),'timeout'=>5,'ignore_errors'=>true,'follow_location'=>0);
 if($fields!==null){$options['header'].="\\r\\nContent-Type: application/x-www-form-urlencoded";$options['content']=http_build_query($fields);}
 $body=file_get_contents('http://127.0.0.1:8097'.$path,false,stream_context_create(array('http'=>$options)));
 $responseHeaders=$http_response_header??array();$status=0;
 foreach($responseHeaders as $header){
  if(preg_match('~^HTTP/\\S+ (\\d+)~',$header,$m))$status=(int)$m[1];
  if(preg_match('/^Set-Cookie:\\s*([^=;]+)=([^;]*)/i',$header,$m))$cookies[$m[1]]=$m[2];
 }
 file_put_contents($jar,json_encode($cookies));return array($status,$body===false?'':$body);
}
''' + s[end:]
p.write_text(s,encoding='utf-8')
env=[{'name':'DB_USERNAME','value':'tienda_owner'},{'name':'DB_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'DB_OWNER_PASSWORD'}}},{'name':'MONGO_URI','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'MONGO_OWNER_URI'}}},{'name':'DB_APP_PASSWORD','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'DB_PASSWORD'}}},{'name':'MONGO_APP_URI','valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':'MONGO_URI'}}}]
pod={'apiVersion':'v1','kind':'Pod','metadata':{'name':'validacion-cierre','namespace':'tienda-dev','labels':{'app':'migration'}},'spec':{'serviceAccountName':'tienda','automountServiceAccountToken':False,'restartPolicy':'Never','securityContext':{'runAsNonRoot':True,'runAsUser':33,'fsGroup':33,'seccompProfile':{'type':'RuntimeDefault'}},'containers':[{'name':'pruebas','image':'tienda-backend:0.1.6','imagePullPolicy':'IfNotPresent','command':['sh','-c','mkdir -p /tmp/proyecto/tmp /tmp/proyecto/tests; for p in application vendor database bin index.php router.php public assets DataTables; do ln -s /var/www/html/$p /tmp/proyecto/$p; done; sleep 7200'],'envFrom':[{'configMapRef':{'name':'tienda-config'}}],'env':env,'resources':{'requests':{'cpu':'50m','memory':'64Mi'},'limits':{'cpu':'1','memory':'384Mi'}},'securityContext':{'allowPrivilegeEscalation':False,'readOnlyRootFilesystem':True,'capabilities':{'drop':['ALL']}},'volumeMounts':[{'name':'tmp','mountPath':'/tmp'}]}],'volumes':[{'name':'tmp','emptyDir':{}}]}}
Path('tmp/validacion-pod.json').write_text(json.dumps(pod,indent=2),encoding='utf-8')
