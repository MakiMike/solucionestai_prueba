<?php
require __DIR__.'/../application/services/TiendaConnection.php';
$db=TiendaConnection::open();$schema='tienda_http_'.bin2hex(random_bytes(6));$server=null;$jar=__DIR__.'/../tmp/http-cookies-'.bin2hex(random_bytes(4));$count=0;
function expectHttp($ok,$msg){global $count;if(!$ok)throw new RuntimeException($msg);$count++;echo 'OK '.$msg.PHP_EOL;}
function requestHttp($path,$fields=null){global $jar;$c=curl_init('http://127.0.0.1:8097'.$path);curl_setopt_array($c,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$jar,CURLOPT_COOKIEFILE=>$jar,CURLOPT_TIMEOUT=>5));if($fields!==null)curl_setopt_array($c,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields)));$body=curl_exec($c);$status=curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);return array($status,$body);}
function csrfFrom($html){if(!preg_match('/const name="[^"]+",token="([a-f0-9]+)"/',$html,$m))throw new RuntimeException('No se encontró token CSRF.');return $m[1];}
try{
 $db->exec('CREATE SCHEMA '.$schema);$db->exec('SET search_path TO '.$schema);
 foreach(glob(__DIR__.'/../database/migrations/*.sql') as $file)$db->exec(file_get_contents($file));
 $q=$db->prepare("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia) VALUES ('Cliente HTTP','http@demo.test',?)");$q->execute(array(password_hash('PruebaSoloLocal123',PASSWORD_DEFAULT)));
 $db->exec("INSERT INTO tallas(tal_nombre,tal_orden) VALUES ('M',1);INSERT INTO playeras(pla_nombre,pla_genero,pla_precio) VALUES ('Prenda HTTP','H',199);INSERT INTO playera_tallas(pla_id,tal_id,pta_stock) VALUES (1,1,3)");
 $env=getenv();$env['DB_SCHEMA']=$schema;$env['APP_BASE_URL']='http://127.0.0.1:8097/';$env['CI_ENV']='production';$env['APP_COOKIE_SECURE']='false';$env['APP_SESSION_DRIVER']='database';
 $pipes=array();$server=proc_open(array(PHP_BINARY,'-S','127.0.0.1:8097','router.php'),array(0=>array('pipe','r'),1=>array('file',__DIR__.'/../tmp/http-server.log','a'),2=>array('file',__DIR__.'/../tmp/http-server.log','a')),$pipes,dirname(__DIR__),$env);fclose($pipes[0]);
 for($i=0;$i<30;$i++){usleep(100000);list($code,$body)=requestHttp('/health/live');if($code===200)break;}
 expectHttp($code===200,'sonda live responde');
 list($code,$body)=requestHttp('/login');expectHttp($code===200,'login disponible con framework actualizado');$csrf=csrfFrom($body);
 list($code,$body)=requestHttp('/login/submit',array('email'=>'http@demo.test','password'=>'PruebaSoloLocal123'));expectHttp($code===403,'POST sin CSRF bloqueado');
 list($code,$body)=requestHttp('/login/submit',array('email'=>'http@demo.test','password'=>'PruebaSoloLocal123','csrf_test_name'=>$csrf));expectHttp($code===303||$code===302,'login válido con sesión compartida');
 list($code,$body)=requestHttp('/carrito');if($code!==200)file_put_contents(__DIR__.'/../tmp/http-failure.html',$body);expectHttp($code===200,'carrito autenticado disponible HTTP '.$code);$csrf=csrfFrom($body);
 preg_match('/pedido_token:\s*"([a-f0-9]+)"/',$body,$token);
 list($code,$body)=requestHttp('/carrito/agregar',array('pla_id'=>1,'tal_id'=>1,'cantidad'=>1,'csrf_test_name'=>$csrf));$j=json_decode($body,true);expectHttp($code===200&&!empty($j['success']),'agregar prenda al carrito');
 list($code,$body)=requestHttp('/carrito');preg_match('/pedido_token:\s*"([a-f0-9]+)"/',$body,$token);$csrf=csrfFrom($body);
 list($code,$body)=requestHttp('/carrito/procesar',array('pedido_token'=>$token[1],'csrf_test_name'=>$csrf));$j=json_decode($body,true);expectHttp($code===200&&!empty($j['success']),'checkout web crea pedido pendiente');$id=$j['ped_id'];
 list($code,$body)=requestHttp('/pedidos');expectHttp($code===200&&strpos($body,'Pendiente de pago')!==false,'pantalla de pedidos y plazo visible');$csrf=csrfFrom($body);preg_match('/name="pedido_token" value="([a-f0-9]+)"/',$body,$token);
 list($code,$body)=requestHttp('/pedidos/accion',array('ped_id'=>$id,'accion'=>'pagar','pedido_token'=>$token[1],'csrf_test_name'=>$csrf));expectHttp($code===302||$code===303,'cliente realiza pago sin aprobación administrativa');
 expectHttp($db->query('SELECT ped_estado FROM pedidos WHERE ped_id='.(int)$id)->fetchColumn()==='confirmado','pago web confirma pedido en PostgreSQL');
 list($code,$body)=requestHttp('/pedidos?pedido='.$id);expectHttp($code===200&&strpos($body,'temporalmente')!==false,'MongoDB caído no interrumpe consulta del pedido');
 echo 'TOTAL '.$count.' verificaciones HTTP correctas'.PHP_EOL;
}finally{
 if(is_resource($server)) {proc_terminate($server);proc_close($server);}
 if(is_file($jar))unlink($jar);
 $db->exec('SET search_path TO public');if(preg_match('/^tienda_http_[a-f0-9]{12}$/D',$schema))$db->exec('DROP SCHEMA IF EXISTS '.$schema.' CASCADE');
}
