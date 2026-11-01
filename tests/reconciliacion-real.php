<?php
// Dos motores reales, schema y base Mongo aleatorios: no toca datos de negocio.
require __DIR__.'/../application/services/TiendaConnection.php';
require __DIR__.'/../application/services/TiendaService.php';
require __DIR__.'/../application/services/TiendaHistory.php';
$db=TiendaConnection::open();$suffix=bin2hex(random_bytes(6));$schema='tienda_reconciliacion_'.$suffix;$mongoName='tienda_validacion_'.$suffix;
$uri=getenv('MONGO_URI');$oldName=getenv('MONGO_DATABASE');$manager=new MongoDB\Driver\Manager($uri);
try{
 $db->exec('CREATE SCHEMA '.$schema);$db->exec('SET search_path TO '.$schema);
 foreach(glob(__DIR__.'/../database/migrations/*.sql') as $file)$db->exec(file_get_contents($file));
 $db->exec("INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia) VALUES ('Prueba','reconciliacion@demo.test','hash');INSERT INTO tallas(tal_nombre,tal_orden) VALUES ('M',1);INSERT INTO playeras(pla_nombre,pla_genero,pla_precio) VALUES ('Prenda','H',100);INSERT INTO playera_tallas(pla_id,tal_id,pta_stock) VALUES (1,1,2)");
 $order=(new TiendaService($db))->checkout(1,[['pla_id'=>1,'tal_id'=>1,'cantidad'=>1]],bin2hex(random_bytes(16)));
 putenv('MONGO_DATABASE='.$mongoName);putenv('MONGO_URI=mongodb://127.0.0.1:1/?serverSelectionTimeoutMS=100');
 $failed=0;$interrupted=false;
 try{$failed=(new TiendaHistory($db))->sync();}catch(MongoDB\Driver\Exception\Exception $e){$interrupted=true;}
 $pending=(int)$db->query('SELECT COUNT(*) FROM tienda_outbox WHERE enviado_en IS NULL')->fetchColumn();
 if(!$interrupted||$failed!==0||$pending!==1)throw new RuntimeException('No se conservo el evento al perder conexion');
 echo "OK interrupcion: pedido SQL y evento pendiente conservados, envio cero\n";
 putenv('MONGO_URI='.$uri);$h=new TiendaHistory($db);$h->initialize();$sent=$h->sync();$events=$h->read((int)$order['ped_id']);
 if($sent!==1||count($events)!==1)throw new RuntimeException('Reconciliacion real fallo');
 echo "OK recuperacion: un evento real enviado a MongoDB y visible por lectura de historial\n";
 if($h->sync()!==0)throw new RuntimeException('Reintento sin pendientes inesperado');
 $db->exec('UPDATE tienda_outbox SET enviado_en=NULL');$h->sync();
 if(count($h->read((int)$order['ped_id']))!==1)throw new RuntimeException('Replay duplico el historial');
 echo "OK replay: indice unico y upsert evitan duplicados reales\n";
}finally{
 if($db->inTransaction())$db->rollBack();$db->exec('SET search_path TO public');
 if(preg_match('/^tienda_reconciliacion_[a-f0-9]{12}$/D',$schema))$db->exec('DROP SCHEMA IF EXISTS '.$schema.' CASCADE');
 if(preg_match('/^tienda_validacion_[a-f0-9]{12}$/D',$mongoName))$manager->executeCommand($mongoName,new MongoDB\Driver\Command(['dropDatabase'=>1]));
 putenv('MONGO_URI='.$uri);putenv('MONGO_DATABASE='.$oldName);
 echo "Esquema y base sinteticos retirados.\n";
}
