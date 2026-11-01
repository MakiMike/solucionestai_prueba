<?php
require __DIR__.'/../application/services/TiendaConnection.php';
require __DIR__.'/../application/services/TiendaService.php';
require __DIR__.'/../application/services/TiendaHistory.php';
$db=TiendaConnection::open();
$schema='tienda_test_'.bin2hex(random_bytes(6));
$checks=0;
function check($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;echo 'OK '.$message.PHP_EOL;}
function rejects(callable $fn,$message){try{$fn();}catch(DomainException $e){check(true,$message);return;}throw new RuntimeException('No rechazó: '.$message);}
function keyForTest(){return bin2hex(random_bytes(16));}
try {
 $db->exec('CREATE SCHEMA '.$schema);$db->exec('SET search_path TO '.$schema);
 foreach(glob(__DIR__.'/../database/migrations/*.sql') as $file)$db->exec(file_get_contents($file));
 $db->exec("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia) VALUES ('Uno','uno@demo.test','hash'),('Dos','dos@demo.test','hash'); INSERT INTO tallas(tal_nombre,tal_orden) VALUES ('M',1),('L',2); INSERT INTO playeras(pla_nombre,pla_genero,pla_precio,pla_edicion_limitada) VALUES ('Playera limitada','H',199.99,TRUE),('Playera normal','M',150,FALSE); INSERT INTO playera_tallas(pla_id,tal_id,pta_stock) VALUES (1,1,5),(1,2,5),(2,1,1)");
 $s=new TiendaService($db);$item=array('pla_id'=>1,'tal_id'=>1,'cantidad'=>1,'precio'=>.01);
 $key=keyForTest();$o=$s->checkout(1,array($item),$key);
 check($o['ped_estado']==='pendiente_pago','checkout crea reserva sin autorizar ni pagar');
 check($o['ped_total']==='199.99','precio tomado del catálogo y no del cliente');
 check((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=1')->fetchColumn()===4,'reserva descuenta una unidad');
 $repeat=$s->checkout(1,array($item),$key);check($repeat['ped_id']===$o['ped_id'],'reintento de checkout no duplica pedido');
 rejects(function()use($s,$o){$s->change($o['ped_id'],2,'pagar');},'otro cliente no puede pagar ni consultar pedido ajeno');
 rejects(function()use($s,$o){$s->detail($o['ped_id'],2,false);},'detalle rechaza acceso ajeno');
 rejects(function()use($s,$o){$s->change($o['ped_id'],1,'entregar',true);},'administrador no puede saltar estados');
 $s->change($o['ped_id'],1,'pagar');$s->change($o['ped_id'],1,'pagar');
 check((int)$db->query("SELECT COUNT(*) FROM tienda_outbox WHERE tipo='pago_confirmado'")->fetchColumn()===1,'pago simulado automático e idempotente');
 check((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=1')->fetchColumn()===4,'pagar no vuelve a descontar stock');
 rejects(function()use($s,$o){$s->change($o['ped_id'],1,'cancelar');},'cliente no cancela pedido pagado');
 $s->change($o['ped_id'],1,'preparar',true);$s->change($o['ped_id'],1,'listo',true);$s->change($o['ped_id'],1,'entregar',true);
 $item['tal_id']=2;$item['cantidad']=2;
 rejects(function()use($s,$item){$s->checkout(1,array($item),keyForTest());},'límite acumulado incluye otras tallas y pedidos entregados');
 $item['cantidad']=1;$second=$s->checkout(1,array($item),keyForTest());
 $db->exec("UPDATE pedidos SET ped_vence_en=clock_timestamp()-interval '1 second' WHERE ped_id=".(int)$second['ped_id']);
 rejects(function()use($s,$second){$s->change($second['ped_id'],1,'pagar');},'pago tardío vence pedido aunque el worker no haya corrido');
 $s->expireDue();$s->expireDue();
 check((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=2')->fetchColumn()===5,'vencimiento restaura existencias exactamente una vez');
 $third=$s->checkout(1,array($item),keyForTest());$s->change($third['ped_id'],1,'cancelar');$s->change($third['ped_id'],1,'cancelar');
 check((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=2')->fetchColumn()===5,'cancelación idempotente libera reserva');
 $normal=array('pla_id'=>2,'tal_id'=>1,'cantidad'=>2);
 rejects(function()use($s,$normal){$s->checkout(2,array($normal),keyForTest());},'sin stock se rechaza toda la transacción');
 $normal['cantidad']=0;rejects(function()use($s,$normal){$s->checkout(2,array($normal),keyForTest());},'cantidad cero rechazada');
 $db->exec("UPDATE playeras SET pla_precio=999,pla_nombre='Cambio de catálogo' WHERE pla_id=1");
 $details=$s->detail($o['ped_id'],1,false);check($details[0]['pde_precio']==='199.99'&&$details[0]['nombre']==='Playera limitada','detalle conserva precio y nombre originales');
 check((int)$db->query('SELECT COUNT(*) FROM tienda_outbox')->fetchColumn()>0,'eventos guardados con el cambio principal');
 $pending=(int)$db->query('SELECT COUNT(*) FROM tienda_outbox WHERE enviado_en IS NULL')->fetchColumn();
 $oldUri=getenv('MONGO_URI');putenv('MONGO_URI=mongodb://127.0.0.1:1');
 try{(new TiendaHistory($db))->sync(1);throw new RuntimeException('Mongo inaccesible debía fallar');}catch(Error $e){}catch(RuntimeException $e){if($e->getMessage()==='Mongo inaccesible debía fallar')throw $e;}
 if($oldUri===false)putenv('MONGO_URI');else putenv('MONGO_URI='.$oldUri);
 check((int)$db->query('SELECT COUNT(*) FROM tienda_outbox WHERE enviado_en IS NULL')->fetchColumn()===$pending,'fallo de Mongo conserva todos los eventos para reintento');
 check((int)$db->query('SELECT SUM(intentos) FROM tienda_outbox')->fetchColumn()===1,'fallo de sincronización registra un intento sin marcar envío');
 // Concurrencia real en dos conexiones contra la última unidad disponible.
 $processes=array();
 for($i=0;$i<2;$i++){
  $pipes=array();$p=proc_open(array(PHP_BINARY,__DIR__.'/race.php',$schema,(string)($i+1)),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
  fclose($pipes[0]);$processes[]=array($p,$pipes);
 }
 $success=0;
 foreach($processes as $p){$out=stream_get_contents($p[1][1]);$err=stream_get_contents($p[1][2]);fclose($p[1][1]);fclose($p[1][2]);$exit=proc_close($p[0]);if($exit!==0)throw new RuntimeException('Falló proceso de concurrencia');if(trim($out)==='reserved')$success++;}
 check($success===1,'dos compradores concurrentes no reservan la misma última unidad');
 check((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=2 AND tal_id=1')->fetchColumn()===0,'inventario nunca negativo tras concurrencia');
 echo 'TOTAL '.$checks.' verificaciones correctas'.PHP_EOL;
} finally {
 if($db->inTransaction())$db->rollBack();
 $db->exec('SET search_path TO public');
 // Solo el esquema aleatorio creado en esta prueba. Nunca utiliza datos reales.
 if(preg_match('/^tienda_test_[a-f0-9]{12}$/D',$schema))$db->exec('DROP SCHEMA IF EXISTS '.$schema.' CASCADE');
}
