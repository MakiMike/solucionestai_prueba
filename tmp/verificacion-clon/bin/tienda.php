<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../application/services/TiendaConnection.php';
require __DIR__ . '/../application/services/TiendaService.php';
require __DIR__ . '/../application/services/TiendaHistory.php';
try {
    $db = TiendaConnection::open();
    $action = isset($argv[1]) ? $argv[1] : '';
    if ($action === 'migrate') {
        $db->exec('CREATE TABLE IF NOT EXISTS tienda_migrations (version varchar(150) PRIMARY KEY, aplicado_en timestamptz DEFAULT CURRENT_TIMESTAMP)');
        $db->query('SELECT pg_advisory_lock(740013)');
        try {
            foreach (glob(__DIR__ . '/../database/migrations/*.sql') as $file) {
                $q = $db->prepare('SELECT 1 FROM tienda_migrations WHERE version=?'); $q->execute(array(basename($file)));
                if ($q->fetchColumn()) continue;
                $db->beginTransaction();
                $db->exec(file_get_contents($file));
                $q = $db->prepare('INSERT INTO tienda_migrations(version) VALUES(?)'); $q->execute(array(basename($file)));
                $db->commit();
                echo 'Aplicada: ' . basename($file) . PHP_EOL;
            }
        } finally { $db->query('SELECT pg_advisory_unlock(740013)'); }
    } elseif ($action === 'grant') {
        $db->exec('GRANT USAGE ON SCHEMA public TO tienda_app; GRANT SELECT,INSERT,UPDATE,DELETE ON ALL TABLES IN SCHEMA public TO tienda_app; GRANT USAGE,SELECT ON ALL SEQUENCES IN SCHEMA public TO tienda_app; REVOKE ALL ON tienda_migrations FROM tienda_app');
        echo 'Permisos de ejecuci髇 aplicados.' . PHP_EOL;
    } elseif ($action === 'expire') {
        echo 'Pedidos vencidos: ' . (new TiendaService($db))->expireDue(1000) . PHP_EOL;
    } elseif ($action === 'sync') {
        echo 'Eventos sincronizados: ' . (new TiendaHistory($db))->sync() . PHP_EOL;
    } elseif ($action === 'mongo-init') {
        (new TiendaHistory($db))->initialize(); echo 'Colecci贸n e 铆ndices preparados.' . PHP_EOL;
    } elseif ($action === 'seed') {
        require __DIR__ . '/../database/seed.php';
    } else {
        fwrite(STDERR, "Uso: php bin/tienda.php migrate|seed|expire|mongo-init|sync\n"); exit(1);
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    // Los mensajes de conexi贸n pueden incluir destinos o credenciales. No imprimirlos.
    fwrite(STDERR, 'La operaci贸n fall贸 (' . get_class($e) . '). Revisa configuraci贸n, permisos y disponibilidad.' . PHP_EOL);
    exit(1);
}
