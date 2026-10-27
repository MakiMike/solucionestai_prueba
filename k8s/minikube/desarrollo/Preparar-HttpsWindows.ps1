$ErrorActionPreference = 'Stop'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Ejecuta este archivo desde PowerShell como administrador: necesita agregar el nombre de desarrollo al archivo hosts.'
}
. (Join-Path $PSScriptRoot '..\Seguridad-Respaldos.ps1')
$folder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\tls\dev-private'))
Set-CarpetaPrivada $folder
$cert = Join-Path $folder 'tienda-dev.crt'
$key = Join-Path $folder 'tienda-dev.key'
$mkcert = Get-Command mkcert -ErrorAction SilentlyContinue
$executable = if ($mkcert) { $mkcert.Source } else { Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Links\mkcert.exe' }
if (-not (Test-Path -LiteralPath $executable)) { throw 'No se encontro la instalacion existente de mkcert.' }
if ((Test-Path -LiteralPath $cert) -xor (Test-Path -LiteralPath $key)) { throw 'Solo existe uno de los archivos del certificado. Revisar antes de continuar.' }
if (-not (Test-Path -LiteralPath $cert)) {
    & $executable -cert-file $cert -key-file $key tienda-dev.test
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el certificado de desarrollo.' }
}
$hostsPath = Join-Path $env:WINDIR 'System32\drivers\etc\hosts'
$found = $false
foreach ($line in [IO.File]::ReadAllLines($hostsPath)) {
    $text = ($line -split '#',2)[0].Trim()
    if (-not $text) { continue }
    $parts = $text -split '\s+'
    if ($parts.Count -gt 1 -and $parts[1..($parts.Count-1)] -contains 'tienda-dev.test') {
        if ($parts[0] -ne '127.0.0.1') { throw 'tienda-dev.test ya tiene otra IP en hosts. Revisar esa entrada.' }
        $found = $true
    }
}
if (-not $found) { [IO.File]::AppendAllText($hostsPath, "`r`n127.0.0.1 tienda-dev.test`r`n", [Text.Encoding]::ASCII) }
Clear-DnsClientCache
Write-Host 'Nombre local: tienda-dev.test -> 127.0.0.1'
Write-Host ('Certificado: ' + $cert)
Write-Host 'HTTPS DE WINDOWS PREPARADO. Falta crear el Secret y la entrada del cluster.'
Write-Host 'Este archivo no ejecuta Docker ni Minikube.'
