<?php
require 'application/services/TiendaConnection.php';
$db=TiendaConnection::open();
$names=json_decode(base64_decode('__NAMES__'),true);
foreach($names as $name){
 if(strpos($name,'VERIFICACION_UPLOAD_')!==0)throw new RuntimeException('Nombre inesperado');
 $q=$db->prepare('SELECT pla_id,pla_imagen FROM playeras WHERE pla_nombre=?');$q->execute([$name]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
  $db->beginTransaction();
  $db->prepare('DELETE FROM playera_tallas WHERE pla_id=?')->execute([$row['pla_id']]);
  $db->prepare('DELETE FROM playeras WHERE pla_id=?')->execute([$row['pla_id']]);
  $db->commit();
  if($row['pla_imagen']){ $path='uploads/playeras/'.basename($row['pla_imagen']); if(is_file($path))unlink($path); }
  echo 'Producto temporal de prueba retirado'.PHP_EOL;
 }
}
