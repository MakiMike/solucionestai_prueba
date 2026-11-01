<?php
define('BASEPATH', __DIR__);
putenv('APP_BASE_URL=https://tskull-shirt.test:9443/');
function baseFor($allowed, $host, $forwarded = null) {
    putenv('APP_LAN_HOST=' . $allowed);
    $_SERVER['HTTP_HOST'] = $host;
    $_SERVER['HTTP_X_FORWARDED_HOST'] = $forwarded;
    $config = array();
    require __DIR__ . '/../application/config/config.php';
    return $config['base_url'];
}
$canonical = 'https://tskull-shirt.test:9443/';
$cases = array(
    array('192.168.100.3:9443', '192.168.100.3:9443', null, 'https://192.168.100.3:9443/'),
    array('10.31.15.103:9443', '10.31.15.103:9443', null, 'https://10.31.15.103:9443/'),
    array('', 'evil.example:9443', null, $canonical),
    array('192.168.100.3:9443', 'evil.example:9443', '192.168.100.3:9443', $canonical),
    array('192.168.100.3:9443', '192.168.100.3:9443.evil.example', null, $canonical),
    array('192.168.100.3:9443', '192.168.100.3:80', null, $canonical),
    array('999.1.1.1:9443', '999.1.1.1:9443', null, $canonical),
    array('evil.example:9443', 'evil.example:9443', null, $canonical),
    array("192.168.100.3:9443\n", "192.168.100.3:9443\n", null, $canonical),
    array('192.168.100.3:9443', 'tskull-shirt.test:9443', null, $canonical)
);
foreach ($cases as $i => $c) {
    if (baseFor($c[0], $c[1], $c[2]) !== $c[3]) throw new RuntimeException('Caso LAN ' . ($i + 1));
}
echo "OK: 10 casos de URL LAN y rechazo de Host no autorizado.\n";
