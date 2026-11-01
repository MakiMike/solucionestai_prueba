<?php
require __DIR__.'/../application/services/TiendaConnection.php';
$db=TiendaConnection::open();$schema='tienda_http_'.bin2hex(random_bytes(6));$server=null;$jar=__DIR__.'/../tmp/http-cookies-'.bin2hex(random_bytes(4));$count=0;
function expectHttp($ok,$msg){global $count;if(!$ok)throw new RuntimeException($msg);$count++;echo 'OK '.$msg.PHP_EOL;}
// Cliente HTTP nativo: no requiere la extensión curl en la imagen PHP.
function requestHttp($path,$fields=null){
 global $jar;
 $cookies=is_file($jar)?json_decode(file_get_contents($jar),true):array();
 $headers=array('Connection: close');
 if($cookies){$pairs=array();foreach($cookies as $k=>$v)$pairs[]=$k.'='.$v;$headers[]='Cookie: '.implode('; ',$pairs);}
 $options=array('method'=>$fields===null?'GET':'POST','header'=>implode("\r\n",$headers),'timeout'=>5,'ignore_errors'=>true,'follow_location'=>0);
 if($fields!==null){$options['header'].="\r\nContent-Type: application/x-www-form-urlencoded";$options['content']=http_build_query($fields);}
 $body=file_get_contents('http://127.0.0.1:8097'.$path,false,stream_context_create(array('http'=>$options)));
 $responseHeaders=$http_response_header??array();$status=0;
 foreach($responseHeaders as $header){
  if(preg_match('~^HTTP/\S+ (\d+)~',$header,$m))$status=(int)$m[1];
  if(preg_match('/^Set-Cookie:\s*([^=;]+)=([^;]*)/i',$header,$m))$cookies[$m[1]]=$m[2];
 }
 file_put_contents($jar,json_encode($cookies));return array($status,$body===false?'':$body);
}
function csrfFrom($html){if(!preg_match('/const name="[^"]+",token="([a-f0-9]+)"/',$html,$m))throw new RuntimeException('No se encontró token CSRF.');return $m[1];}
try{
 $db->exec('CREATE SCHEMA '.$schema);$db->exec('SET search_path TO '.$schema);
 foreach(glob(__DIR__.'/../database/migrations/*.sql') as $file)$db->exec(file_get_contents($file));
 $q=$db->prepare("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia) VALUES ('Cliente HTTP','http@demo.test',?)");$q->execute(array(password_hash('PruebaSoloLocal123',PASSWORD_DEFAULT)));
 $db->exec("INSERT INTO tallas(tal_nombre,tal_orden) VALUES ('M',1);INSERT INTO playeras(pla_nombre,pla_genero,pla_precio) VALUES ('Prenda HTTP','H',199);INSERT INTO playera_tallas(pla_id,tal_id,pta_stock) VALUES (1,1,3)");
 $admin=$db->prepare("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia,us_rol) VALUES ('Admin HTTP','admin-http@demo.test',?,'A')");$admin->execute(array(password_hash('PruebaSoloLocal123',PASSWORD_DEFAULT)));
 $env=getenv();$env['MONGO_URI']='mongodb://127.0.0.1:1/tienda?serverSelectionTimeoutMS=100';$env['DB_SCHEMA']=$schema;$env['APP_BASE_URL']='http://127.0.0.1:8097/';$env['CI_ENV']='production';$env['APP_COOKIE_SECURE']='false';$env['APP_SESSION_DRIVER']='database';
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
 // Cliente no puede ejecutar CRUD administrativo.
 list($code,$body)=requestHttp('/usuarios');expectHttp($code>=300&&$code<400,'rol cliente rechazado en administracion HTTP '.$code);
 // Pedido: segunda reserva y cancelacion desde API real; conserva el pagado.
 list($code,$body)=requestHttp('/carrito');$csrf=csrfFrom($body);
 requestHttp('/carrito/agregar',array('pla_id'=>1,'tal_id'=>1,'cantidad'=>1,'csrf_test_name'=>$csrf));
 list($code,$body)=requestHttp('/carrito');$csrf=csrfFrom($body);preg_match('/pedido_token:\s*"([a-f0-9]+)"/',$body,$token);
 list($code,$body)=requestHttp('/carrito/procesar',array('pedido_token'=>$token[1],'csrf_test_name'=>$csrf));$pending=json_decode($body,true);
 expectHttp($code===200&&!empty($pending['success']),'pedido pendiente creado por API');
 list($code,$body)=requestHttp('/pedidos');$csrf=csrfFrom($body);preg_match('/name="pedido_token" value="([a-f0-9]+)"/',$body,$token);
 requestHttp('/pedidos/accion',array('ped_id'=>$pending['ped_id'],'accion'=>'cancelar','pedido_token'=>$token[1],'csrf_test_name'=>$csrf));
 expectHttp($db->query('SELECT ped_estado FROM pedidos WHERE ped_id='.(int)$pending['ped_id'])->fetchColumn()==='cancelado','baja de pedido por cancelacion');
 expectHttp((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=1')->fetchColumn()===2,'cancelacion API devuelve stock sin devolver el pedido pagado');
 requestHttp('/logout');list($code,$body)=requestHttp('/login');$csrf=csrfFrom($body);
 $userFields=array('nombre'=>'Usuario CRUD','email'=>'crud@demo.test','telefono'=>'','sexo'=>'O','password'=>'PruebaSoloLocal123','csrf_test_name'=>$csrf);
 requestHttp('/register/submit',$userFields);
 $uid=$db->query("SELECT us_id FROM us_usuarios WHERE us_correo='crud@demo.test'")->fetchColumn();expectHttp((int)$uid>0,'usuario creado por registro HTTP');
 requestHttp('/register/submit',$userFields);
 expectHttp((int)$db->query("SELECT COUNT(*) FROM us_usuarios WHERE us_correo='crud@demo.test'")->fetchColumn()===1,'correo duplicado rechazado');
 list($code,$body)=requestHttp('/login');$csrf=csrfFrom($body);
 requestHttp('/login/submit',array('email'=>'admin-http@demo.test','password'=>'PruebaSoloLocal123','csrf_test_name'=>$csrf));
 list($code,$body)=requestHttp('/usuarios');expectHttp($code===200,'interfaz de usuarios disponible para administrador');$csrf=csrfFrom($body);
 list($code,$body)=requestHttp('/usuarios/obtener_usuario',array('us_id'=>$uid,'csrf_test_name'=>$csrf));$u=json_decode($body,true);
 expectHttp(!empty($u['success'])&&empty($u['usuario']['us_contrasenia']),'consulta usuario por API sin exponer hash');
 list($code,$body)=requestHttp('/usuarios/actualizar_usuario',array('us_id'=>$uid,'us_nombre'=>'Usuario editado','us_correo'=>'crud@demo.test','us_telefono'=>'','us_sexo'=>'O','us_rol'=>'U','csrf_test_name'=>$csrf));
 expectHttp(!empty(json_decode($body,true)['success'])&&$db->query('SELECT us_nombre FROM us_usuarios WHERE us_id='.(int)$uid)->fetchColumn()==='Usuario editado','usuario actualizado por API');
 requestHttp('/usuarios/eliminar_usuario',array('us_id'=>$uid,'csrf_test_name'=>$csrf));
 expectHttp(!$db->query('SELECT us_activo FROM us_usuarios WHERE us_id='.(int)$uid)->fetchColumn(),'usuario desactivado sin borrar relaciones');
 list($code,$body)=requestHttp('/productos');expectHttp($code===200,'interfaz de productos disponible');$csrf=csrfFrom($body);
 $product=array('pla_nombre'=>'Prenda CRUD','pla_descripcion'=>'Sintetica','pla_genero'=>'H','pla_precio'=>'175','stock_M'=>5,'csrf_test_name'=>$csrf);
 list($code,$body)=requestHttp('/productos/crear',$product);expectHttp(!empty(json_decode($body,true)['success']),'producto creado por API');
 $pid=$db->query("SELECT pla_id FROM playeras WHERE pla_nombre='Prenda CRUD'")->fetchColumn();
 list($code,$body)=requestHttp('/productos/listar');expectHttp($code===200&&strpos($body,'Prenda CRUD')!==false,'consulta productos por API');
 $product['pla_id']=$pid;$product['pla_nombre']='Prenda actualizada';$product['pla_precio']='190';
 list($code,$body)=requestHttp('/productos/editar',$product);expectHttp(!empty(json_decode($body,true)['success'])&&$db->query('SELECT pla_nombre FROM playeras WHERE pla_id='.(int)$pid)->fetchColumn()==='Prenda actualizada','producto editado por API');
 requestHttp('/productos/eliminar',array('pla_id'=>$pid,'csrf_test_name'=>$csrf));
 expectHttp(!$db->query('SELECT pla_activa FROM playeras WHERE pla_id='.(int)$pid)->fetchColumn(),'producto dado de baja logicamente');
 echo 'TOTAL '.$count.' verificaciones HTTP correctas'.PHP_EOL;
}finally{
 if(is_resource($server)) {proc_terminate($server);proc_close($server);}
 if(is_file($jar))unlink($jar);
 $db->exec('SET search_path TO public');if(preg_match('/^tienda_http_[a-f0-9]{12}$/D',$schema))$db->exec('DROP SCHEMA IF EXISTS '.$schema.' CASCADE');
}
