$ErrorActionPreference = 'Stop'

Write-Host '1. Backend: acceso permitido a PostgreSQL y MongoDB'
$php = @'
<?php
foreach (['postgres'=>5432, 'mongo'=>27017] as $host=>$port) {
    $socket = @fsockopen($host, $port, $errno, $error, 5);
    if (!$socket) {
        fwrite(STDERR, "FALLO: backend -> $host:$port\n");
        exit(1);
    }
    fclose($socket);
    echo "PERMITIDO: backend -> $host:$port\n";
}
'@
$php | & minikube -p tienda-prod kubectl -- exec -i deployment/backend -n tienda-prod -- php
if ($LASTEXITCODE -ne 0) { throw 'Falló la conexión permitida. Revisar las políticas.' }

Write-Host '2. Frontend: verificar herramienta de conexión'
& minikube -p tienda-prod kubectl -- exec deployment/frontend -n tienda-prod -- sh -c 'command -v nc'
if ($LASTEXITCODE -ne 0) { throw 'No está disponible nc; no se puede interpretar la prueba de bloqueo.' }

Write-Host '3. Frontend: comprobar resolución del nombre de MongoDB'
& minikube -p tienda-prod kubectl -- exec deployment/frontend -n tienda-prod -- getent hosts mongo
if ($LASTEXITCODE -ne 0) { throw 'No se resuelve MongoDB; no se puede atribuir el fallo a la política.' }

Write-Host '4. Frontend: acceso directo a MongoDB debe quedar bloqueado'
& minikube -p tienda-prod kubectl -- exec deployment/frontend -n tienda-prod -- nc -z -v -w 3 mongo 27017
if ($LASTEXITCODE -eq 0) { throw 'ERROR: el frontend pudo acceder directamente a MongoDB.' }
Write-Host 'BLOQUEADO: frontend -> mongo:27017 (resultado esperado)'
