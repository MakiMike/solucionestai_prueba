$ErrorActionPreference = 'Stop'
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
$rng.Dispose()
$metricsPassword = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()

$sql = @'
DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'tienda_metrics') THEN
    CREATE ROLE tienda_metrics LOGIN;
  END IF;
END $$;
ALTER ROLE tienda_metrics PASSWORD '__PASSWORD__';
GRANT pg_monitor TO tienda_metrics;
GRANT CONNECT ON DATABASE tienda TO tienda_metrics;
'@
$sql.Replace('__PASSWORD__', $metricsPassword) | & minikube -p tienda-prod kubectl -- exec -i postgres-0 -n tienda-prod -- psql -X -q -v ON_ERROR_STOP=1 -U tienda_owner -d tienda
if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar el usuario de métricas.' }

$secret = @{
    apiVersion = 'v1'
    kind = 'Secret'
    metadata = @{ name = 'postgres-metrics'; namespace = 'tienda-prod' }
    type = 'Opaque'
    data = @{
        password = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($metricsPassword))
    }
}
$secret | ConvertTo-Json -Depth 5 | & minikube -p tienda-prod kubectl -- apply -f -
if ($LASTEXITCODE -ne 0) { throw 'No se pudo registrar el Secret de métricas.' }
Write-Host 'Usuario tienda_metrics y Secret preparados. No se muestra la contraseña.'
