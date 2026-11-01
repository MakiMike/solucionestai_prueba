<?php
// Solo dentro del backend del namespace tienda-restore-...; cuenta sintetica temporal.
require '/var/www/html/application/services/TiendaConnection.php';
require '/var/www/html/application/services/TiendaHistory.php';
$db=TiendaConnection::open();
if(getenv('DB_USERNAME')!=='restore_owner')throw new RuntimeException('No ejecutar fuera de la copia restaurada');
$password=bin2hex(random_bytes(16));$email='validacion-'.bin2hex(random_bytes(6)).'@restore.test';$jar=[];$id=null;
function webRestore($path,$fields=null){
 global $jar;$headers=['Connection: close'];if($jar){$v=[];foreach($jar as $k=>$x)$v[]=$k.'='.$x;$headers[]='Cookie: '.implode('; ',$v);}
 $o=['method'=>$fields===null?'GET':'POST','header'=>implode("\r\n",$headers),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>10];
 if($fields!==null){$o['header'].="\r\nContent-Type: application/x-www-form-urlencoded";$o['content']=http_build_query($fields);}
 $body=file_get_contents('http://frontend:8080'.$path,false,stream_context_create(['http'=>$o]));$code=0;
 foreach($http_response_header??[] as $h){if(preg_match('~^HTTP/\S+ (\d+)~',$h,$m))$code=(int)$m[1];if(preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i',$h,$m))$jar[$m[1]]=$m[2];}
 return [$code,$body];
}
try{
 $q=$db->prepare("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia,us_rol) VALUES ('Validacion restauracion',?,?,'A') RETURNING us_id");$q->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);$id=$q->fetchColumn();
 [$code,$body]=webRestore('/login');if($code!==200||!preg_match('/const name="[^"]+",token="([a-f0-9]+)"/',$body,$token))throw new RuntimeException('Login restaurado invalido');
 [$code,$body]=webRestore('/login/submit',['email'=>$email,'password'=>$password,'csrf_test_name'=>$token[1]]);if($code<300||$code>=400)throw new RuntimeException('Autenticacion restaurada fallo');
 [$code,$body]=webRestore('/productos/listar');$data=json_decode($body,true);
 $expected=(int)$db->query('SELECT COUNT(*) FROM playeras')->fetchColumn();
 if($code!==200||!is_array($data)||!isset($data['data'])||count($data['data'])!==$expected)throw new RuntimeException('Catalogo no coincide con SQL restaurado');
 $order=$db->query('SELECT ped_id FROM pedidos ORDER BY ped_id LIMIT 1')->fetchColumn();$events=$order?(new TiendaHistory($db))->read((int)$order):[];
 echo json_encode(['autenticacion'=>'OK con cuenta sintetica temporal','catalogoHTTP'=>$expected,'eventosMongoDelPrimerPedido'=>count($events),'cuentaTemporalRetirada'=>true],JSON_PRETTY_PRINT).PHP_EOL;
}finally{if($id){$q=$db->prepare('DELETE FROM us_usuarios WHERE us_id=? AND us_correo=?');$q->execute([$id,$email]);}}
