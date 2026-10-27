$ErrorActionPreference = 'Stop'
$pods = & minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend --field-selector=status.phase=Running -o json
if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el backend.' }
$pod = ($pods | ConvertFrom-Json).items | Where-Object {
    $_.metadata.name -like 'backend-*' -and @($_.status.conditions | Where-Object { $_.type -eq 'Ready' -and $_.status -eq 'True' }).Count -gt 0
} | Select-Object -First 1
if (-not $pod) { throw 'No hay backend listo.' }
$check = @'
<?php
$phase='configuracion del cliente';
set_exception_handler(function($error) use (&$phase) {
    fwrite(STDERR, 'Fallo en ' . $phase . ': ' . get_class($error) . PHP_EOL);
    exit(1);
});
$uri=getenv('MONGO_URI');
$ca='/etc/mongo-ca/ca.crt';
parse_str(parse_url($uri, PHP_URL_QUERY) ?: '', $options);
if (($options['tls'] ?? '') !== 'true' || ($options['tlsCAFile'] ?? '') !== $ca) throw new RuntimeException('Falta TLS y CA en el cliente');
foreach (['tlsAllowInvalidCertificates','tlsAllowInvalidHostnames'] as $name) {
    if (($options[$name] ?? '') !== 'false') throw new RuntimeException('Validacion TLS incompleta');
}
$phase='conexion autenticada con TLS';
$manager=new MongoDB\Driver\Manager($uri,['serverSelectionTimeoutMS'=>3000,'connectTimeoutMS'=>2000]);
$database=getenv('MONGO_DATABASE') ?: 'tienda';
$manager->executeCommand($database,new MongoDB\Driver\Command(['ping'=>1]));
echo 'Backend -> MongoDB: TLS, CA y nombre validados; autenticacion correcta' . PHP_EOL;
$phase='comprobacion del canal cifrado';
$context=stream_context_create(['ssl'=>['cafile'=>$ca,'verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>'mongo']]);
$socket=stream_socket_client('tls://mongo:27017',$errno,$error,5,STREAM_CLIENT_CONNECT,$context);
if ($socket === false) throw new RuntimeException('No se pudo comprobar el canal TLS');
$crypto=stream_get_meta_data($socket)['crypto'];
echo 'Cifrado: ' . $crypto['protocol'] . ' / ' . $crypto['cipher_name'] . PHP_EOL;
fclose($socket);
$phase='rechazo de nombre incorrecto';
$wrong=stream_context_create(['ssl'=>['cafile'=>$ca,'verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>'nombre-invalido.test']]);
set_error_handler(function(){return true;});
try { $socket=stream_socket_client('tls://mongo:27017',$errno,$error,5,STREAM_CLIENT_CONNECT,$wrong); }
finally { restore_error_handler(); }
if ($socket !== false) { fclose($socket); throw new RuntimeException('Se acepto un nombre incorrecto'); }
echo 'Nombre de servidor incorrecto: RECHAZADO (esperado)' . PHP_EOL;
$phase='rechazo de conexion sin TLS';
$parts=explode('?',$uri,2);
$plainQuery=[];
foreach (explode('&',$parts[1] ?? '') as $part) {
    if ($part !== '' && !preg_match('/^(tls|ssl)/i',$part)) $plainQuery[]=$part;
}
$plainUri=$parts[0].'?'.implode('&',array_merge($plainQuery,['tls=false']));
$rejected=false;
try {
    $plain=new MongoDB\Driver\Manager($plainUri,['serverSelectionTimeoutMS'=>2000,'connectTimeoutMS'=>1000]);
    $plain->executeCommand($database,new MongoDB\Driver\Command(['ping'=>1]));
} catch (MongoDB\Driver\Exception\ConnectionException $error) { $rejected=true; }
if (!$rejected) throw new RuntimeException('Se permitio conectar sin TLS');
echo 'Conexion TCP sin TLS: RECHAZADO (esperado)' . PHP_EOL;
$phase='consulta de la coleccion eventos';
$count=$manager->executeCommand($database,new MongoDB\Driver\Command(['count'=>'eventos','query'=>new stdClass()]))->toArray()[0]->n;
echo 'Coleccion eventos consultada: ' . $count . ' documentos' . PHP_EOL;
echo 'TLS MONGODB VERIFICADO' . PHP_EOL;
'@
$result = @($check | & minikube -p tienda-prod kubectl -- exec -i $pod.metadata.name -c backend -n tienda-prod -- php)
if ($LASTEXITCODE -ne 0) { throw 'Fallo la comprobacion TLS MongoDB. Conservar el error.' }
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\tls-mongo'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
$result | Tee-Object -FilePath (Join-Path $evidencePath ('comprobacion-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt'))
