param(
    [Parameter(Mandatory=$true)][string]$CarpetaCifrada,
    [Parameter(Mandatory=$true)][string]$CarpetaDestino
)
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'Seguridad-Respaldos.ps1')
$source = (Resolve-Path -LiteralPath $CarpetaCifrada).Path
$destination = [IO.Path]::GetFullPath($CarpetaDestino)
if (Test-Path -LiteralPath $destination) { throw 'El destino ya existe. Elige una carpeta nueva.' }
$metadata = Get-Content -LiteralPath (Join-Path $source 'cifrado.json') -Raw | ConvertFrom-Json
if ($metadata.formato -ne 'tienda-dpapi-v1') { throw 'Formato de respaldo desconocido.' }
$cipher = Join-Path $source 'respaldo.dpapi'
if ((Get-FileHash -LiteralPath $cipher -Algorithm SHA256).Hash -ne $metadata.SHA256) { throw 'El archivo cifrado no coincide con su SHA256.' }
$decoded = [Security.Cryptography.ProtectedData]::Unprotect([IO.File]::ReadAllBytes($cipher), $null, [Security.Cryptography.DataProtectionScope]::CurrentUser)
if ((Get-HashBytes $decoded) -ne $metadata.zipSHA256) { throw 'El ZIP descifrado no coincide con su SHA256.' }
Set-CarpetaPrivada $destination
$zipPath = Join-Path $destination 'temporal.zip'
[IO.File]::WriteAllBytes($zipPath, $decoded)
[IO.Compression.ZipFile]::ExtractToDirectory($zipPath, $destination)
Remove-Item -LiteralPath $zipPath
$checks = @(Import-Csv -LiteralPath (Join-Path $destination 'hashes.csv'))
if ($checks.Count -ne 3 -or @($checks.Archivo | Select-Object -Unique).Count -ne 3) { throw 'Se esperaban exactamente tres archivos distintos en hashes.csv.' }
foreach ($entry in $checks) {
    if ($entry.Archivo -notin @('postgres.dump','mongo.archive.gz','uploads.tar.gz')) { throw 'Nombre inesperado en hashes.csv.' }
    if ((Get-FileHash -LiteralPath (Join-Path $destination $entry.Archivo) -Algorithm SHA256).Hash -ne $entry.SHA256) { throw ('Contenido alterado: ' + $entry.Archivo) }
    Write-Host ($entry.Archivo + ': SHA256 OK')
}
Write-Host ('RESPALDO DESCIFRADO Y VERIFICADO: ' + $destination)
Write-Host 'No se ha restaurado ni sobrescrito ninguna base de datos.'
