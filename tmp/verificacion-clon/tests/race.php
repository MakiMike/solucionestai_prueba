<?php
require __DIR__.'/../application/services/TiendaConnection.php';require __DIR__.'/../application/services/TiendaService.php';
if(!isset($argv[1])||!preg_match('/^tienda_test_[a-f0-9]{12}$/D',$argv[1]))exit(1);
$db=TiendaConnection::open();$db->exec('SET search_path TO '.$argv[1]);
try{(new TiendaService($db))->checkout((int)$argv[2],array(array('pla_id'=>2,'tal_id'=>1,'cantidad'=>1)),bin2hex(random_bytes(16)));echo 'reserved';}catch(DomainException $e){echo 'unavailable';}
