$ErrorActionPreference = 'Stop'
$pods = & minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend --field-selector=status.phase=Running -o json
if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el backend.' }
$pod = ($pods | ConvertFrom-Json).items | Where-Object {
    $_.metadata.name -like 'backend-*' -and @($_.status.conditions | Where-Object { $_.type -eq 'Ready' -and $_.status -eq 'True' }).Count -gt 0
} | Select-Object -First 1
if (-not $pod) { throw 'No hay backend listo.' }
$check = @'
<?php
require '/var/www/html/application/services/TiendaConnection.php';
if (getenv('DB_SSLMODE') !== 'verify-full') throw new RuntimeException('El backend no exige verify-full');
$pdo = TiendaConnection::open();
$tls = $pdo->query('SELECT ssl,version,cipher FROM pg_stat_ssl WHERE pid=pg_backend_pid()')->fetch(PDO::FETCH_ASSOC);
if (empty($tls['ssl'])) throw new RuntimeException('Conexion sin cifrar');
echo 'Backend -> PostgreSQL: verify-full, CA y nombre validados' . PHP_EOL;
echo 'Cifrado: ' . $tls['version'] . ' / ' . $tls['cipher'] . PHP_EOL;
$params = ['host'=>getenv('DB_HOST'), 'port'=>getenv('DB_PORT') ?: '5432',
    'dbname'=>getenv('DB_DATABASE'), 'user'=>getenv('DB_USERNAME'),
    'password'=>getenv('DB_PASSWORD'), 'connect_timeout'=>'5',
    'sslrootcert'=>getenv('DB_SSLROOTCERT'), 'sslmode'=>'verify-full'];
function connectionString($params) {
    $parts=[];
    foreach ($params as $key=>$value) $parts[]=$key . "='" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    return implode(' ', $parts);
}
function expectRejected($params, $expectedError, $label) {
    $error='';
    set_error_handler(function($severity,$message) use (&$error) { $error=$message; return true; });
    try { $connection=pg_connect(connectionString($params), PGSQL_CONNECT_FORCE_NEW); }
    finally { restore_error_handler(); }
    if ($connection !== false) { pg_close($connection); throw new RuntimeException('Se permitio: ' . $label); }
    if (stripos($error,$expectedError) === false) throw new RuntimeException('Fallo por motivo inesperado en: ' . $label);
    echo $label . ': RECHAZADO (esperado)' . PHP_EOL;
}
$wrongName=$params;
$wrongName['hostaddr']=gethostbyname($params['host']);
$wrongName['host']='nombre-invalido.test';
expectRejected($wrongName, 'does not match host name', 'Nombre de servidor incorrecto');
$plain=$params;
$plain['sslmode']='disable';
expectRejected($plain, 'no encryption', 'Conexion TCP sin TLS');
echo 'Consulta de datos: ' . $pdo->query('SELECT COUNT(*) FROM playeras')->fetchColumn() . ' prendas' . PHP_EOL;
echo 'TLS POSTGRESQL VERIFICADO' . PHP_EOL;
'@
$result = @($check | & minikube -p tienda-prod kubectl -- exec -i $pod.metadata.name -c backend -n tienda-prod -- php)
if ($LASTEXITCODE -ne 0) { throw 'Fallo la comprobacion TLS. Conservar el error para revisarlo.' }
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\tls-postgres'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
$result | Tee-Object -FilePath (Join-Path $evidencePath ('comprobacion-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt'))
