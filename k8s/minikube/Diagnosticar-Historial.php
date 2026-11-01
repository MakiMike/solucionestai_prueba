<?php
// Para enviar por stdin a php en el backend. Consulta datos; no sincroniza ni escribe eventos.
$phase = 'inicio';
$uri = (string) getenv('MONGO_URI');
function historyDiagnosticMessage($message, $uri) {
    if ($uri !== '') {
        $message = str_replace($uri, '[URI omitida]', $message);
        $parts = parse_url($uri);
        foreach (array('user', 'pass') as $key) {
            $value = is_array($parts) ? ($parts[$key] ?? '') : '';
            if ($value !== '') $message = str_replace(array($value, rawurldecode($value)), '[credencial omitida]', $message);
        }
    }
    foreach (array('DB_PASSWORD', 'APP_ENCRYPTION_KEY', 'MONGO_APP_PASSWORD') as $key) {
        $value = (string) getenv($key);
        if ($value !== '') $message = str_replace($value, '[secreto omitido]', $message);
    }
    $message = preg_replace('~mongodb(?:\+srv)?://[^\s\x22\x27]+~i', '[URI omitida]', $message);
    return substr(str_replace(array("\r", "\n"), ' ', $message), 0, 600);
}
set_exception_handler(function ($error) use (&$phase, $uri) {
    file_put_contents('php://stderr', 'Fase: ' . $phase . PHP_EOL);
    file_put_contents('php://stderr', get_class($error) . ': ' . historyDiagnosticMessage($error->getMessage(), $uri) . PHP_EOL);
    exit(1);
});
echo 'PHP ' . PHP_VERSION . '; mongodb ' . phpversion('mongodb') . PHP_EOL;
$phase = 'conexion PostgreSQL';
require getcwd() . '/application/services/TiendaConnection.php';
$db = TiendaConnection::open();
$phase = 'construccion del cliente MongoDB';
$manager = new MongoDB\Driver\Manager($uri, array('serverSelectionTimeoutMS'=>3000, 'connectTimeoutMS'=>2000));
$phase = 'ping autenticado MongoDB';
$manager->executeCommand(getenv('MONGO_DATABASE') ?: 'tienda', new MongoDB\Driver\Command(array('ping'=>1)));
echo 'Conexion autenticada MongoDB OK.' . PHP_EOL;
$phase = 'lectura de evento pendiente';
$event = $db->query('SELECT * FROM tienda_outbox WHERE enviado_en IS NULL ORDER BY evento_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$event) { echo 'Sin eventos pendientes en outbox.' . PHP_EOL; exit; }
echo 'Evento pendiente: ' . (int)$event['evento_id'] . '; pedido: ' . (int)$event['ped_id'] . PHP_EOL;
$phase = 'conversion de fecha BSON';
$date = new MongoDB\BSON\UTCDateTime((int)(strtotime($event['creado_en']) * 1000));
$phase = 'construccion del documento y del upsert';
$payload = array('evento_id'=>(int)$event['evento_id'], 'pedido_id'=>(int)$event['ped_id'],
    'actor_id'=>$event['actor_id'] === null ? null : (int)$event['actor_id'],
    'tipo'=>$event['tipo'], 'fecha'=>$date, 'datos'=>json_decode($event['datos'], false, 512, JSON_THROW_ON_ERROR));
$bulk = new MongoDB\Driver\BulkWrite();
$bulk->update(array('evento_id'=>(int)$event['evento_id']), array('$setOnInsert'=>$payload), array('upsert'=>true));
$phase = 'construccion de WriteConcern';
$concern = new MongoDB\Driver\WriteConcern('majority', 3000);
echo 'Fecha, documento, upsert y WriteConcern construidos correctamente.' . PHP_EOL;
echo 'Diagnostico sin escrituras terminado. Si la tarea falla al escribir, falta revisar esa fase.' . PHP_EOL;
