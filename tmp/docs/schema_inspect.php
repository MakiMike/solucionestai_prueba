<?php
define('BASEPATH', __DIR__); define('ENVIRONMENT','production');
require __DIR__.'/../../application/config/database.php';
$c=$db['default'];
try { $p=new PDO('pgsql:host='.$c['hostname'].';port='.$c['port'].';dbname='.$c['database'],$c['username'],$c['password']);
foreach($p->query("SELECT table_name,column_name,data_type,column_default FROM information_schema.columns WHERE table_schema='public' ORDER BY table_name,ordinal_position") as $r) echo json_encode(array_slice($r,0,8), JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch(Exception $e) { echo 'DB not reachable: '.get_class($e).PHP_EOL; exit(1); }
