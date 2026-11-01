$ErrorActionPreference = 'Stop'
# Los valores brutos de etcd se procesan en memoria; no se imprimen ni se guardan.
$raw = & minikube -p tienda-prod kubectl -- exec -n kube-system etcd-tienda-prod -- etcdctl --endpoints=https://127.0.0.1:2379 --cacert=/var/lib/minikube/certs/etcd/ca.crt --cert=/var/lib/minikube/certs/etcd/healthcheck-client.crt --key=/var/lib/minikube/certs/etcd/healthcheck-client.key get /registry/secrets/ --prefix -w json
if ($LASTEXITCODE -ne 0) { throw 'No se pudo comprobar el almacenamiento de etcd.' }
$stored = $raw | ConvertFrom-Json
if (@($stored.kvs).Count -eq 0) { throw 'No se encontraron Secrets almacenados para verificar.' }
$prefix = 'k8s:enc:secretbox:v1:tienda-key1:'
$encrypted = 0
foreach ($entry in $stored.kvs) {
    $bytes = [Convert]::FromBase64String($entry.value)
    if ($bytes.Length -lt $prefix.Length) { throw 'Un Secret no tiene el prefijo cifrado esperado.' }
    $header = [Text.Encoding]::ASCII.GetString($bytes, 0, $prefix.Length)
    if ($header -ne $prefix) { throw 'Hay Secrets sin el cifrado esperado. Repetir la migracion y revisar.' }
    $encrypted++
}
$appRaw = & minikube -p tienda-prod kubectl -- get secret tienda-secrets -n tienda-prod -o json
if ($LASTEXITCODE -ne 0) { throw 'La API no pudo descifrar el Secret de la tienda.' }
$appSecret = $appRaw | ConvertFrom-Json
$expectedKeys = @('DB_PASSWORD','DB_OWNER_PASSWORD','MONGO_APP_PASSWORD','MONGO_OWNER_PASSWORD','MONGO_URI','MONGO_OWNER_URI','APP_ENCRYPTION_KEY','SEED_ADMIN_PASSWORD','SEED_CLIENT_PASSWORD')
foreach ($name in $expectedKeys) { if (-not $appSecret.data.$name) { throw 'Falta una clave requerida en el Secret de la tienda.' } }
$result = @(
    'Proveedor de cifrado: secretbox (XSalsa20 + Poly1305, clave de 256 bits)',
    ('Secrets comprobados directamente en etcd: ' + $encrypted),
    ('Prefijo de almacenamiento: ' + $prefix),
    'Todos los Secrets actuales comprobados tienen contenido cifrado.',
    'Lectura mediante la API autorizada: correcta; 9 claves requeridas de la tienda presentes.',
    'No se muestran valores de Secrets ni claves de cifrado.',
    'CIFRADO DE SECRETS VERIFICADO'
)
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\seguridad'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
$result | Tee-Object -FilePath (Join-Path $evidencePath ('cifrado-secrets-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt'))
