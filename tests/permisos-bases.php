<?php
// Ejecutar exclusivamente en validacion-cierre: usa cuentas propias del ambiente.
require __DIR__.'/../application/services/TiendaConnection.php';
$originalUser=getenv('DB_USERNAME');$originalPassword=getenv('DB_PASSWORD');
putenv('DB_USERNAME=tienda_app');putenv('DB_PASSWORD='.getenv('DB_APP_PASSWORD'));
$db=TiendaConnection::open();$db->query('SELECT COUNT(*) FROM playeras')->fetchColumn();
echo "OK SQL: lectura con tienda_app\n";
$flags=$db->query('SELECT rolsuper,rolcreatedb,rolcreaterole FROM pg_roles WHERE rolname=current_user')->fetch(PDO::FETCH_ASSOC);
foreach($flags as $flag)if($flag===true||$flag==='t'||$flag===1)throw new RuntimeException('Cuenta SQL con privilegios elevados');
echo "OK SQL: sin superusuario, CREATEDB ni CREATEROLE\n";
foreach(['SELECT * FROM pg_authid','CREATE TABLE public.tienda_prueba_permiso_temporal(id integer)','ALTER TABLE public.playeras ADD COLUMN prueba_temporal integer'] as $query){
 $denied=false;$db->beginTransaction();
 try{$db->exec($query);}catch(PDOException $e){$denied=$e->getCode()==='42501';}
 finally{if($db->inTransaction())$db->rollBack();}
 if(!$denied)throw new RuntimeException('Permiso SQL no rechazado como se esperaba');
 echo "OK SQL: operacion administrativa rechazada (42501), sin cambios\n";
}
putenv('DB_USERNAME='.$originalUser);putenv('DB_PASSWORD='.$originalPassword);
$manager=new MongoDB\Driver\Manager(getenv('MONGO_APP_URI'));
$denied=false;
try{$manager->executeQuery('admin.system.users',new MongoDB\Driver\Query([],['limit'=>1]));}
catch(MongoDB\Driver\Exception\Exception $e){$denied=$e->getCode()===13;}
if(!$denied)throw new RuntimeException('Cuenta Mongo con acceso administrativo inesperado');
echo "OK Mongo: acceso administrativo rechazado (13)\n";
$collection='validacion_'.bin2hex(random_bytes(8));$ns='tienda.'.$collection;
try{
 for($i=0;$i<2;$i++){
  $bulk=new MongoDB\Driver\BulkWrite();$bulk->update(['evento_id'=>'prueba'],['$setOnInsert'=>['tipo'=>'validacion','fecha'=>new MongoDB\BSON\UTCDateTime()]],['upsert'=>true]);
  $manager->executeBulkWrite($ns,$bulk,['writeConcern'=>new MongoDB\Driver\WriteConcern('majority',3000)]);
 }
 $count=count($manager->executeQuery($ns,new MongoDB\Driver\Query([]))->toArray());
 if($count!==1)throw new RuntimeException('Upsert no idempotente');
 echo "OK Mongo: escritura/lectura autorizadas y reintento sin duplicar\n";
}finally{$manager->executeCommand('tienda',new MongoDB\Driver\Command(['drop'=>$collection]));}
echo "Coleccion sintetica retirada; datos existentes conservados.\n";
