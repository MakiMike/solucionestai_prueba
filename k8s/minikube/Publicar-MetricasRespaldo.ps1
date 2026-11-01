param([switch]$Fallo)
$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../../output/respaldos-cifrados'))
$last = 0
foreach ($file in @(Get-ChildItem -LiteralPath $root -Filter cifrado.json -Recurse -File -ErrorAction SilentlyContinue)) {
    $meta = Get-Content -LiteralPath $file.FullName -Raw | ConvertFrom-Json
    if ($meta.formato -eq 'tienda-dpapi-v1' -and (Test-Path -LiteralPath (Join-Path $file.DirectoryName 'respaldo.dpapi'))) {
        $stamp = [DateTimeOffset]::Parse($meta.fechaUTC).ToUnixTimeSeconds()
        if ($stamp -gt $last) { $last = $stamp }
    }
}
$ok = if ($Fallo -or $last -eq 0) { 0 } else { 1 }
$text = "tienda_backup_last_success_timestamp_seconds $last`ntienda_backup_last_run_success $ok`n"
$manifest = @{apiVersion='v1';kind='ConfigMap';metadata=@{name='tienda-backup-metrics';namespace='monitoreo'};data=@{'backup.prom'=$text}}
$cmd = Get-Command minikube -ErrorAction SilentlyContinue
$exe = if ($cmd) { $cmd.Source } else { Join-Path $env:ProgramFiles 'Kubernetes/Minikube/minikube.exe' }
if (-not (Test-Path -LiteralPath $exe)) { throw 'Minikube no encontrado para publicar metricas.' }
$manifest | ConvertTo-Json -Depth 8 | & $exe -p tienda-prod kubectl -- apply -f -
if ($LASTEXITCODE -ne 0) { throw 'No se publicaron las metricas del respaldo.' }
Write-Host 'Metricas publicadas: antiguedad y resultado; sin datos ni claves del respaldo.'
