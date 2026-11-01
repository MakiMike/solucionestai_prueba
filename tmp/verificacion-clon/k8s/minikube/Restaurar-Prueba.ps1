param(
    [Parameter(Mandatory = $true)]
    [string]$CarpetaRespaldo,
    [ValidatePattern('^tienda-restore-\d{8}-\d{6}$')]
    [string]$ContinuarNamespace
)
$ErrorActionPreference = 'Stop'
$backupPath = (Resolve-Path -LiteralPath $CarpetaRespaldo).Path
$timer = [Diagnostics.Stopwatch]::StartNew()
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$namespace = 'tienda-restore-' + $stamp
if ($ContinuarNamespace) {
    $namespace = $ContinuarNamespace
    $stamp = $namespace.Substring('tienda-restore-'.Length)
}
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot ('..\..\output\evidencias\restauracion\' + $stamp)))
New-Item -ItemType Directory -Force $evidencePath | Out-Null

Write-Host '1/6 Verificando integridad de los tres archivos...'
$expectedFiles = @('postgres.dump', 'mongo.archive.gz', 'uploads.tar.gz')
$checks = @(Import-Csv -LiteralPath (Join-Path $backupPath 'hashes.csv'))
foreach ($name in $expectedFiles) {
    $entry = @($checks | Where-Object Archivo -eq $name)
    if ($entry.Count -ne 1) { throw ('No hay un hash único para ' + $name) }
    $hash = Get-FileHash -LiteralPath (Join-Path $backupPath $name) -Algorithm SHA256
    if ($hash.Hash -ne $entry[0].SHA256) { throw ('El hash no coincide: ' + $name) }
    Write-Host ($name + ': SHA256 OK')
}

if (-not $ContinuarNamespace) {
Write-Host ('2/6 Creando entorno aislado: ' + $namespace)
$manifest = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'entorno-restauracion.yaml') -Raw | ConvertFrom-Json
foreach ($item in $manifest.items) {
    if ($item.kind -eq 'Namespace') { $item.metadata.name = $namespace }
    else { $item.metadata.namespace = $namespace }
}
$nsResource = $manifest.items | Where-Object kind -eq 'Namespace'
$nsResource | ConvertTo-Json -Depth 10 | & minikube -p tienda-prod kubectl -- apply -f -
if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el namespace aislado.' }
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
$rng.Dispose()
$password = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
$secret = @{apiVersion='v1';kind='Secret';metadata=@{name='restore-credentials';namespace=$namespace};type='Opaque';data=@{password=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($password))}}
$secret | ConvertTo-Json -Depth 5 | & minikube -p tienda-prod kubectl -- apply -f -
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron crear las credenciales independientes.' }
$manifestPath = Join-Path $evidencePath 'entorno.yaml'
$manifest | ConvertTo-Json -Depth 100 | Set-Content -LiteralPath $manifestPath -Encoding UTF8
& minikube -p tienda-prod kubectl -- apply -f $manifestPath
if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el entorno aislado.' }
& minikube -p tienda-prod kubectl -- wait --for=condition=Ready pod/postgres-restore pod/mongo-restore pod/fotos-restore -n $namespace --timeout=600s
if ($LASTEXITCODE -ne 0) { throw ('El entorno no está listo; revisar Pods en ' + $namespace) }

Write-Host '3/6 Copiando respaldos al entorno aislado...'
Push-Location $backupPath
try {
    & minikube -p tienda-prod kubectl -- cp './postgres.dump' ($namespace + '/postgres-restore:/tmp/postgres.dump')
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia PostgreSQL.' }
    & minikube -p tienda-prod kubectl -- cp './mongo.archive.gz' ($namespace + '/mongo-restore:/tmp/mongo.archive.gz')
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia MongoDB.' }
    & minikube -p tienda-prod kubectl -- cp './uploads.tar.gz' ($namespace + '/fotos-restore:/tmp/uploads.tar.gz')
    if ($LASTEXITCODE -ne 0) { throw 'Falló la copia de imágenes.' }
} finally { Pop-Location }

Write-Host '4/6 Restaurando PostgreSQL y MongoDB...'
& minikube -p tienda-prod kubectl -- exec postgres-restore -n $namespace -- pg_restore -U restore_owner -d tienda --no-owner --no-privileges --exit-on-error /tmp/postgres.dump
if ($LASTEXITCODE -ne 0) { throw 'Falló pg_restore en el entorno aislado.' }
$mongoRestore = @'
set -eu
config=$(mktemp /tmp/mongorestore-config.XXXXXX)
trap 'rm -f "$config"' EXIT
mongosh --quiet --nodb --eval 'print(JSON.stringify({password:process.env.MONGO_INITDB_ROOT_PASSWORD}))' > "$config"
mongorestore --quiet --config="$config" --host=127.0.0.1 --username="$MONGO_INITDB_ROOT_USERNAME" --authenticationDatabase=admin --archive=/tmp/mongo.archive.gz --gzip --nsInclude='tienda.*' --stopOnError
echo 'MongoDB restaurado sin errores.'
'@
$mongoRestore | & minikube -p tienda-prod kubectl -- exec -i mongo-restore -n $namespace -- sh
if ($LASTEXITCODE -ne 0) { throw 'Falló mongorestore en el entorno aislado.' }
} else {
    Write-Host ('2-4/6 Continuando la restauracion existente: ' + $namespace)
    & minikube -p tienda-prod kubectl -- wait --for=condition=Ready pod/postgres-restore pod/mongo-restore pod/fotos-restore -n $namespace --timeout=120s
    if ($LASTEXITCODE -ne 0) { throw 'El entorno existente no esta listo.' }
}

Write-Host '5/6 Restaurando y comparando las imágenes con el archivo de respaldo...'
& minikube -p tienda-prod kubectl -- exec fotos-restore -n $namespace -- mkdir -p /restaurado/fotos
if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar la carpeta de imágenes restauradas.' }
& minikube -p tienda-prod kubectl -- exec fotos-restore -n $namespace -- tar -xzf /tmp/uploads.tar.gz -C /restaurado/fotos --no-same-owner
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron restaurar las imágenes.' }
$imageCheck = @'
<?php
exec('tar -tzf /tmp/uploads.tar.gz', $entries, $status);
if ($status !== 0) throw new RuntimeException('No se pudo listar el respaldo');
$expected = [];
foreach ($entries as $name) {
    if (substr($name, -1) === '/') continue;
    $relative = preg_replace('#^(\./)+#', '', $name);
    if (in_array('..', explode('/', $relative), true)) throw new RuntimeException('Ruta no permitida');
    $target = '/restaurado/fotos/' . $relative;
    $stream = popen('tar -xOzf /tmp/uploads.tar.gz -- ' . escapeshellarg($name), 'r');
    if ($stream === false) throw new RuntimeException('No se pudo leer el archivo');
    $context = hash_init('sha256');
    hash_update_stream($context, $stream);
    $status = pclose($stream);
    if ($status !== 0) throw new RuntimeException('Error leyendo contenido del respaldo');
    $originalHash = hash_final($context);
    if (!is_file($target) || is_link($target) || $originalHash === false || $originalHash !== hash_file('sha256', $target)) {
        throw new RuntimeException('Contenido diferente: ' . $relative);
    }
    $expected[$relative] = true;
    echo 'SHA256 OK: ' . $relative . PHP_EOL;
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/restaurado/fotos', FilesystemIterator::SKIP_DOTS)) as $entry) {
    if ($entry->isDir()) continue;
    $relative = substr($entry->getPathname(), strlen('/restaurado/fotos/'));
    if ($entry->isLink() || !isset($expected[$relative])) throw new RuntimeException('Archivo adicional: ' . $relative);
}
echo 'Archivos comparados por contenido: ' . count($expected) . PHP_EOL;
'@
$imageResult = @($imageCheck | & minikube -p tienda-prod kubectl -- exec -i fotos-restore -n $namespace -- php)
if ($LASTEXITCODE -ne 0) { throw 'La comparación de imágenes con el archivo de respaldo detectó diferencias.' }
$imageResult | Tee-Object -FilePath (Join-Path $evidencePath 'verificacion-imagenes.txt')

Write-Host '6/6 Verificando los datos recuperados...'
$sql = @'
SELECT 'playeras' AS tabla, COUNT(*) AS filas FROM playeras
UNION ALL SELECT 'tallas',COUNT(*) FROM tallas
UNION ALL SELECT 'playera_tallas',COUNT(*) FROM playera_tallas
UNION ALL SELECT 'usuarios',COUNT(*) FROM us_usuarios
UNION ALL SELECT 'pedidos',COUNT(*) FROM pedidos
UNION ALL SELECT 'detalle_pedidos',COUNT(*) FROM pedido_detalle
UNION ALL SELECT 'eventos_outbox',COUNT(*) FROM tienda_outbox;
'@
$pgResult = @($sql | & minikube -p tienda-prod kubectl -- exec -i postgres-restore -n $namespace -- psql -X -v ON_ERROR_STOP=1 -U restore_owner -d tienda)
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron consultar las tablas restauradas.' }
$pgResult | Tee-Object -FilePath (Join-Path $evidencePath 'conteos-postgres.txt')
$mongoCheck = @'
const admin = new Mongo('mongodb://127.0.0.1:27017/admin').getDB('admin');
admin.auth(process.env.MONGO_INITDB_ROOT_USERNAME, process.env.MONGO_INITDB_ROOT_PASSWORD);
const target = admin.getSiblingDB('tienda');
if (!target.getCollectionNames().includes('eventos')) throw new Error('No se restauró la colección eventos');
print('Documentos en eventos: ' + target.eventos.countDocuments({}));
const indexes=target.eventos.getIndexes();
for (const index of indexes) print('Indice restaurado: ' + index.name);
for (const name of ['evento_unico', 'pedido_fecha']) {
  if (!indexes.some(index => index.name === name)) throw new Error('Falta el índice ' + name);
}
'@
$mongoResult = @($mongoCheck | & minikube -p tienda-prod kubectl -- exec -i mongo-restore -n $namespace -- mongosh --quiet --nodb --file /dev/stdin)
if ($LASTEXITCODE -ne 0) { throw 'Falló la verificación de colección e índices MongoDB.' }
$mongoResult | Tee-Object -FilePath (Join-Path $evidencePath 'verificacion-mongo.txt')
$timer.Stop()
$result = [ordered]@{namespace=$namespace;respaldo=$backupPath;finUTC=[DateTime]::UtcNow.ToString('o');segundos=[Math]::Round($timer.Elapsed.TotalSeconds,1);reanudado=[bool]$ContinuarNamespace;integridad='SHA256 de tres archivos verificado';postgres='Restaurado sin errores y tablas consultadas';mongo='Restaurado sin errores; colección e índices consultados';imagenes='Archivos restaurados comparados por SHA256; permisos y grupo no comparados';alcance='Tiempo de esta ejecucion (si se reanudo, excluye el intento anterior); no simula por si solo una recuperacion completa del servicio ni demuestra un RPO periodico.'}
$result | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $evidencePath 'resultado.json') -Encoding UTF8
Write-Host ('RESTAURACION VERIFICADA. Tiempo: ' + $result.segundos + ' segundos.')
if ($ContinuarNamespace) { Write-Host 'Tiempo solo de la continuacion; no representa el tiempo total de recuperacion.' }
Write-Host ('Namespace de prueba: ' + $namespace)
Write-Host ('Evidencias: ' + $evidencePath)
Write-Host 'El entorno de prueba se conserva para las capturas. Producción no se ha sobrescrito.'
