param(
    [ValidateRange(15, 300)]
    [int]$Segundos = 90
)
$ErrorActionPreference = 'Stop'
$outputFolder = Join-Path $PSScriptRoot '..\..\output\evidencias\disponibilidad'
New-Item -ItemType Directory -Force $outputFolder | Out-Null
$logPath = Join-Path $outputFolder ('sonda-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt')
$okCount = 0
$errorCount = 0
$timer = [Diagnostics.Stopwatch]::StartNew()
Write-Host 'Consultando HTTPS cada segundo. Elimina un solo Pod backend desde otra terminal.'
while ($timer.Elapsed.TotalSeconds -lt $Segundos) {
    # Mantiene la validación TLS. La CA local de mkcert no publica revocación.
    # --silent evita que Windows PowerShell 5 convierta stderr nativo en
    # una excepción; el fallo se registra mediante el código de salida.
    $code = & curl.exe --silent --noproxy '*' --ssl-revoke-best-effort --connect-timeout 2 --max-time 4 --output NUL --write-out '%{http_code}' 'https://tienda-prod.test:9443/health/ready' 2>$null
    $requestExitCode = $LASTEXITCODE
    $code = [string]$code
    if ($requestExitCode -eq 0 -and $code -eq '200') { $okCount++ } else { $errorCount++ }
    $line = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz') + ' HTTP=' + $code + ' salida=' + $requestExitCode
    $line | Tee-Object -FilePath $logPath -Append
    Start-Sleep -Seconds 1
}
$timer.Stop()
$summary = 'Resultado observado: ' + $okCount + ' respuestas 200; ' + $errorCount + ' fallos; duración ' + [math]::Round($timer.Elapsed.TotalSeconds, 1) + ' segundos.'
$summary | Tee-Object -FilePath $logPath -Append
Write-Host ('Evidencia guardada en: ' + $logPath)
if ($errorCount -gt 0) { Write-Host 'Se detectaron fallos. Conservar el registro para analizar el impacto.' }
