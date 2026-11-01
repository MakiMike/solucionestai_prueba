param([string]$CarpetaDestino)
$ErrorActionPreference = 'Stop'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupFolder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot ('..\..\output\respaldos\' + $stamp)))
if ($CarpetaDestino) { $backupFolder = [IO.Path]::GetFullPath($CarpetaDestino) }
New-Item -ItemType Directory -Path $backupFolder | Out-Null
$started = [DateTime]::UtcNow.ToString('o')
$pgRemote = '/tmp/tienda-' + $stamp + '.dump'
$mongoRemote = '/tmp/tienda-' + $stamp + '.archive.gz'
$uploadsRemote = '/tmp/uploads-' + $stamp + '.tar.gz'

Write-Host '1/5 Creando respaldo de PostgreSQL...'
& minikube -p tienda-prod kubectl -- exec postgres-0 -n tienda-prod -- pg_dump -U tienda_owner -d tienda --format=custom --no-owner --no-privileges --file=$pgRemote
if ($LASTEXITCODE -ne 0) { throw 'Falló pg_dump.' }
& minikube -p tienda-prod kubectl -- exec postgres-0 -n tienda-prod -- pg_restore --list $pgRemote | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'No se puede leer el índice del respaldo PostgreSQL.' }

Write-Host '2/5 Creando respaldo de MongoDB...'
$mongoScript = @'
set -eu
config=$(mktemp /tmp/mongodump-config.XXXXXX)
trap 'rm -f "$config"' EXIT
mongosh --quiet --nodb --eval 'print(JSON.stringify({password:process.env.MONGO_INITDB_ROOT_PASSWORD}))' > "$config"
set --
if [ -f /etc/mongo-ca/ca.crt ]; then
    set -- --ssl --sslCAFile=/etc/mongo-ca/ca.crt
fi
mongodump --quiet --config="$config" --host=127.0.0.1 --username="$MONGO_INITDB_ROOT_USERNAME" --authenticationDatabase=admin --db=tienda --archive=__ARCHIVE__ --gzip "$@"
echo 'Archivo MongoDB generado.'
'@
$mongoScript.Replace('__ARCHIVE__', $mongoRemote) | & minikube -p tienda-prod kubectl -- exec -i mongo-0 -n tienda-prod -- sh
if ($LASTEXITCODE -ne 0) { throw 'Falló mongodump.' }

Write-Host '3/5 Respaldando las imágenes compartidas...'
$podJson = & minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend --field-selector=status.phase=Running -o json
if ($LASTEXITCODE -ne 0) { throw 'No se pueden consultar los Pods backend.' }
$backendPod = ($podJson | ConvertFrom-Json).items | Where-Object {
    $_.metadata.name -like 'backend-*' -and
    @($_.status.conditions | Where-Object { $_.type -eq 'Ready' -and $_.status -eq 'True' }).Count -gt 0
} | Select-Object -First 1
if (!$backendPod) { throw 'No hay backend listo para respaldar las imágenes.' }
$backendName = $backendPod.metadata.name
& minikube -p tienda-prod kubectl -- exec $backendName -c backend -n tienda-prod -- tar -czf $uploadsRemote -C /var/www/html/uploads .
if ($LASTEXITCODE -ne 0) { throw 'Falló el respaldo de imágenes.' }

Write-Host '4/5 Copiando los archivos a la laptop...'
# Destinos relativos para evitar que kubectl cp interprete C: como nombre de Pod.
Push-Location $backupFolder
try {
    & minikube -p tienda-prod kubectl -- cp ('tienda-prod/postgres-0:' + $pgRemote) './postgres.dump'
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia de PostgreSQL.' }
    & minikube -p tienda-prod kubectl -- cp ('tienda-prod/mongo-0:' + $mongoRemote) './mongo.archive.gz'
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia de MongoDB.' }
    & minikube -p tienda-prod kubectl -- cp ('tienda-prod/' + $backendName + ':' + $uploadsRemote) './uploads.tar.gz' -c backend
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia de imágenes.' }
} finally {
    Pop-Location
}

Write-Host '5/5 Calculando hashes SHA-256...'
$files = @('postgres.dump', 'mongo.archive.gz', 'uploads.tar.gz')
$checks = foreach ($name in $files) {
    $file = Get-Item -LiteralPath (Join-Path $backupFolder $name)
    if ($file.Length -eq 0) { throw ('Archivo vacío: ' + $name) }
    $hash = Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256
    [pscustomobject]@{ Archivo = $name; Bytes = $file.Length; SHA256 = $hash.Hash }
}
$checks | Export-Csv -LiteralPath (Join-Path $backupFolder 'hashes.csv') -NoTypeInformation -Encoding UTF8
$manifest = [ordered]@{
    perfil = 'tienda-prod'
    namespace = 'tienda-prod'
    base = 'tienda'
    inicioUTC = $started
    finUTC = [DateTime]::UtcNow.ToString('o')
    archivos = $checks
    alcance = 'Bases de datos e imágenes. Los roles de servidor y secretos se reconstruyen desde su configuración.'
    consistencia = 'Respaldos independientes; no representan una transacción atómica entre PostgreSQL, MongoDB e imágenes.'
    restauracion = 'Pendiente de prueba aislada.'
}
$manifest | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath (Join-Path $backupFolder 'manifest.json') -Encoding UTF8
& minikube -p tienda-prod kubectl -- exec postgres-0 -n tienda-prod -- rm -f -- $pgRemote
if ($LASTEXITCODE -ne 0) { throw 'No se pudo retirar el archivo temporal PostgreSQL.' }
& minikube -p tienda-prod kubectl -- exec mongo-0 -n tienda-prod -- rm -f -- $mongoRemote
if ($LASTEXITCODE -ne 0) { throw 'No se pudo retirar el archivo temporal MongoDB.' }
& minikube -p tienda-prod kubectl -- exec $backendName -c backend -n tienda-prod -- rm -f -- $uploadsRemote
if ($LASTEXITCODE -ne 0) { throw 'No se pudo retirar el archivo temporal de imagenes.' }
$checks | Format-Table Archivo, Bytes, SHA256 -AutoSize
Write-Host ('RESPALDO GUARDADO EN: ' + $backupFolder)
Write-Host 'La restauración y su tiempo de recuperación todavía no se han probado.'
