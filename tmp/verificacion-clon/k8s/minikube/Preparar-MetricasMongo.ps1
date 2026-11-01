$ErrorActionPreference = 'Stop'
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
$rng.Dispose()
$metricsPassword = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()

$js = @'
const fs = require('fs');
const tls = fs.existsSync('/etc/mongo-ca/ca.crt');
const admin = new Mongo('mongodb://127.0.0.1:27017/admin' + (tls ? '?tls=true&tlsCAFile=%2Fetc%2Fmongo-ca%2Fca.crt' : '')).getDB('admin');
admin.auth(process.env.MONGO_INITDB_ROOT_USERNAME, process.env.MONGO_INITDB_ROOT_PASSWORD);
const user = {
  pwd: '__PASSWORD__',
  roles: [{role: 'clusterMonitor', db: 'admin'}, {role: 'read', db: 'local'}]
};
if (admin.getUser('tienda_metrics')) {
  admin.updateUser('tienda_metrics', user);
} else {
  admin.createUser(Object.assign({user: 'tienda_metrics'}, user));
}
print('Usuario de metricas de MongoDB preparado.');
'@
$js.Replace('__PASSWORD__', $metricsPassword) | & minikube -p tienda-prod kubectl -- exec -i mongo-0 -n tienda-prod -- mongosh --quiet --nodb --file /dev/stdin
if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar el usuario de métricas de MongoDB.' }

$uri = 'mongodb://tienda_metrics:' + $metricsPassword + '@mongo:27017/admin?authSource=admin&directConnection=true'
$tlsEnabled = & minikube -p tienda-prod kubectl -- get configmap mongo-ca -n tienda-prod --ignore-not-found -o name
if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar la configuracion TLS MongoDB.' }
if ($tlsEnabled) { $uri += '&tls=true&tlsCAFile=%2Fetc%2Fmongo-ca%2Fca.crt&tlsAllowInvalidCertificates=false&tlsAllowInvalidHostnames=false' }
$secret = @{
    apiVersion = 'v1'
    kind = 'Secret'
    metadata = @{ name = 'mongo-metrics'; namespace = 'tienda-prod' }
    type = 'Opaque'
    data = @{ uri = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($uri)) }
}
$secret | ConvertTo-Json -Depth 5 | & minikube -p tienda-prod kubectl -- apply -f -
if ($LASTEXITCODE -ne 0) { throw 'No se pudo registrar el Secret de métricas de MongoDB.' }
Write-Host 'Usuario tienda_metrics y Secret preparados. No se muestra la contraseña.'
