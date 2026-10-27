<?php
// Sin sesión ni conexión a BD para liveness; readiness sí verifica esquema y PostgreSQL.
header('Content-Type: application/json');
if (isset($_GET['ready'])) {
    try {
        require __DIR__ . '/../application/services/TiendaConnection.php';
        TiendaConnection::open()->query('SELECT evento_id FROM tienda_outbox LIMIT 0');
    } catch (Throwable $e) { http_response_code(503); echo '{"status":"not_ready"}'; exit; }
}
echo '{"status":"ok"}';
