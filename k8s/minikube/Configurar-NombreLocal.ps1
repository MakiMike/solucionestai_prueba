$ErrorActionPreference = 'Stop'
$hostsPath = Join-Path $env:WINDIR 'System32\drivers\etc\hosts'
$resultPath = Join-Path $PSScriptRoot '..\..\output\evidencias\https\resultado-hosts.txt'
try {
    $lines = [IO.File]::ReadAllLines($hostsPath)
    $hasCorrectEntry = $false
    foreach ($line in $lines) {
        $content = ($line -split '#', 2)[0].Trim()
        if (!$content) { continue }
        $parts = $content -split '\s+'
        if ($parts.Count -gt 1 -and $parts[1..($parts.Count - 1)] -contains 'tienda-prod.test') {
            if ($parts[0] -ne '127.0.0.1') {
                throw 'Existe una entrada distinta para tienda-prod.test; revisarla antes de modificarla.'
            }
            $hasCorrectEntry = $true
        }
    }
    if (!$hasCorrectEntry) {
        [IO.File]::AppendAllText($hostsPath, "`r`n127.0.0.1 tienda-prod.test`r`n", [Text.Encoding]::ASCII)
    }
    Clear-DnsClientCache
    [IO.File]::WriteAllText($resultPath, 'OK: tienda-prod.test apunta a 127.0.0.1.')
} catch {
    [IO.File]::WriteAllText($resultPath, ('ERROR: ' + $_.Exception.Message))
    exit 1
}
