$ErrorActionPreference = 'Stop'
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Ejecuta PowerShell como administrador para agregar el nombre al archivo hosts.'
}
$hostsPath = Join-Path $env:WINDIR 'System32\drivers\etc\hosts'
$found = $false
foreach ($line in [IO.File]::ReadAllLines($hostsPath)) {
    $clean = ($line -split '#', 2)[0].Trim()
    if (-not $clean) { continue }
    $parts = $clean -split '\s+'
    if ($parts.Count -gt 1 -and $parts[1..($parts.Count-1)] -contains 'tskull-shirt.test') {
        if ($parts[0] -ne '127.0.0.1') { throw 'tskull-shirt.test ya tiene otra IP en hosts; revisar antes de continuar.' }
        $found = $true
    }
}
if (-not $found) { [IO.File]::AppendAllText($hostsPath, "`r`n127.0.0.1 tskull-shirt.test`r`n", [Text.Encoding]::ASCII) }
Clear-DnsClientCache
Write-Host 'Nombre local preparado: https://tskull-shirt.test:9443/'
