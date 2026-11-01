$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\Seguridad-Respaldos.ps1')
$privateFolder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\tls\dev-private'))
Set-CarpetaPrivada $privateFolder
$envFile = Join-Path $privateFolder 'secrets.env'
if (Test-Path -LiteralPath $envFile) {
    $lines = [IO.File]::ReadAllLines($envFile)
    $expected = @('DB_PASSWORD','DB_OWNER_PASSWORD','MONGO_APP_PASSWORD','MONGO_OWNER_PASSWORD','MONGO_URI','MONGO_OWNER_URI','APP_ENCRYPTION_KEY','SEED_ADMIN_PASSWORD','SEED_CLIENT_PASSWORD')
    foreach ($name in $expected) {
        if (@($lines | Where-Object { $_.StartsWith($name + '=') -and $_.Length -gt ($name.Length + 1) }).Count -ne 1) { throw 'El archivo existente esta incompleto. No se cambian sus valores.' }
    }
    Write-Host ('Credenciales de desarrollo existentes conservadas: ' + $envFile)
    exit 0
}
function New-RandomHex([int]$Length = 32) {
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    $bytes = New-Object byte[] $Length
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
}
$dbApp = New-RandomHex
$dbOwner = New-RandomHex
$mongoApp = New-RandomHex
$mongoOwner = New-RandomHex
$values = [ordered]@{
    DB_PASSWORD=$dbApp
    DB_OWNER_PASSWORD=$dbOwner
    MONGO_APP_PASSWORD=$mongoApp
    MONGO_OWNER_PASSWORD=$mongoOwner
    MONGO_URI=('mongodb://tienda_app:' + $mongoApp + '@mongo:27017/tienda?authSource=tienda')
    MONGO_OWNER_URI=('mongodb://tienda_owner:' + $mongoOwner + '@mongo:27017/tienda?authSource=admin')
    APP_ENCRYPTION_KEY=(New-RandomHex)
    SEED_ADMIN_PASSWORD=('DevAdmin-' + (New-RandomHex 8))
    SEED_CLIENT_PASSWORD=('DevCliente-' + (New-RandomHex 8))
}
$lines = @($values.GetEnumerator() | ForEach-Object { $_.Key + '=' + $_.Value })
[IO.File]::WriteAllLines($envFile, $lines, (New-Object Text.UTF8Encoding($false)))
Write-Host 'Credenciales independientes de desarrollo creadas. No se muestran valores.'
Write-Host ('Archivo privado, excluido de Git y de la imagen Docker: ' + $envFile)
Write-Host 'Cuentas de prueba: admin@tienda.test y cliente@tienda.test; sus claves estan en este archivo.'
