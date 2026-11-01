<?php
// Respaldo previo a migrar la instalación local. El archivo queda fuera del repositorio.
if (PHP_SAPI !== 'cli') exit(1);
define('BASEPATH', __DIR__); define('ENVIRONMENT','production');
require __DIR__.'/../application/config/database.php';$c=$db['default'];
$destination=__DIR__.'/../tmp/antes-reservas-'.date('Ymd-His').'.dump';
$tool=getenv('PG_DUMP_PATH')?:'pg_dump';
$env=getenv();$env['PGPASSWORD']=$c['password'];
$pipes=array();$p=proc_open(array($tool,'--host='.$c['hostname'],'--port='.$c['port'],'--username='.$c['username'],'--dbname='.$c['database'],'--format=custom','--file='.$destination),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,null,$env);
fclose($pipes[0]);stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
if(proc_close($p)!==0){fwrite(STDERR,'No se pudo crear el respaldo. No ejecutar migraciones.');exit(1);}
echo 'Respaldo local creado antes de la migración.'.PHP_EOL;
