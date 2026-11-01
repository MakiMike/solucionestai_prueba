<?php
require __DIR__.'/../application/services/TiendaConnection.php';$db=TiendaConnection::open();
$db->beginTransaction();try{$db->exec(file_get_contents(__DIR__.'/../database/migrations/002_reservas_outbox.sql'));echo 'Migration OK';}catch(PDOException $e){echo $e->getCode().' '.($e->errorInfo[2]??'').PHP_EOL;}finally{$db->rollBack();}
