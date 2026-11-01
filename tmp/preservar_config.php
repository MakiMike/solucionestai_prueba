<?php
define('BASEPATH', __DIR__); define('ENVIRONMENT','production');
require __DIR__.'/../application/config/database.php';
$c=$db['default'];
file_put_contents(__DIR__.'/../application/config/database.local.php', '<?php'.PHP_EOL.'return '.var_export(array_intersect_key($c,array_flip(array('hostname','port','database','username','password'))),true).';'.PHP_EOL);
