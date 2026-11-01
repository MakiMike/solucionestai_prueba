<?php
require __DIR__.'/../application/services/TiendaConnection.php';$db=TiendaConnection::open();foreach($db->query("SELECT conname,pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid='pedidos'::regclass AND contype='c'") as $r)echo $r['conname'].' '.$r['definition'].PHP_EOL;
