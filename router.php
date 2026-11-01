<?php
// Solo para pruebas locales con php -S. La imagen utiliza Nginx y PHP-FPM.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(assets|DataTables|uploads)/#', $path) && strpos($path, '..') === false) {
    if (preg_match('/\.php(?:$|\/)/i', $path)) { http_response_code(404); exit; }
    return false;
}
if ($path === '/health/live' || $path === '/health/ready') {
    if ($path === '/health/ready') $_GET['ready'] = 1;
    require __DIR__.'/public/health.php'; return;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__.'/index.php';
