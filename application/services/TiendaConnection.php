<?php

class TiendaConnection
{
    public static function open()
    {
        if (!defined('BASEPATH')) define('BASEPATH', __DIR__);
        if (!defined('ENVIRONMENT')) define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');
        require __DIR__ . '/../config/database.php';
        $c = $db['default'];
        $mode = getenv('DB_SSLMODE') ?: 'prefer';
        $dsn = 'pgsql:host=' . $c['hostname'] . ';port=' . $c['port'] . ';dbname=' . $c['database'] . ';sslmode=' . $mode . ';connect_timeout=5';
        if (getenv('DB_SSLROOTCERT')) $dsn .= ';sslrootcert=' . getenv('DB_SSLROOTCERT');
        $pdo = new PDO($dsn, $c['username'], $c['password'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $pdo->exec('SET search_path TO ' . $c['schema']);
        return $pdo;
    }
}
