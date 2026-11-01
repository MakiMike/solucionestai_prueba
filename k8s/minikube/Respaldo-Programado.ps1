param([ValidateRange(1,90)][int]$Conservar = 7)
$ErrorActionPreference = 'Stop'
trap {
    try { & (Join-Path $PSScriptRoot 'Publicar-MetricasRespaldo.ps1') -Fallo } catch { Write-Warning 'No se pudo publicar el fallo; la alerta de antiguedad sigue siendo el control de respaldo.' }
    $errorFolder = Join-Path $env:LOCALAPPDATA 'TiendaProd'
    New-Item -ItemType Directory -Force -Path $errorFolder | Out-Null
    $errorMessage = (Get-Date -Format o) + ' ' + $_.Exception.Message + [Environment]::NewLine + $_.ScriptStackTrace
    $errorMessage | Add-Content -LiteralPath (Join-Path $errorFolder 'respaldo-error.log') -Encoding UTF8
    Write-Error $errorMessage -ErrorAction Continue
    exit 1
}
. (Join-Path $PSScriptRoot 'Seguridad-Respaldos.ps1')
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\respaldos-cifrados'))
Set-CarpetaPrivada $root
$lockPath = Join-Path $root 'ejecucion.lock'
try { $lock = [IO.File]::Open($lockPath, 'OpenOrCreate', 'ReadWrite', 'None') }
catch { throw 'Ya hay otra ejecucion del respaldo, o no se puede abrir su bloqueo.' }
try {
    $runId = Get-Date -Format 'yyyyMMdd-HHmmssfff'
    $runFolder = Join-Path $root $runId
    Set-CarpetaPrivada $runFolder
    $staging = Join-Path $runFolder 'trabajo'
    $logPath = Join-Path $runFolder 'ejecucion.log'
    Write-Host '1/4 Generando respaldos en carpeta temporal con acceso restringido...'
    # Proceso hijo: el codigo de salida diferencia errores de avisos de tar.
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'Respaldar-Bases.ps1') -CarpetaDestino $staging 2>&1 | ForEach-Object {
        $_.ToString() | Add-Content -LiteralPath $logPath -Encoding UTF8
    }
    $backupExit = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference
    if ($backupExit -ne 0) { throw ('Fallo el respaldo. Revisar: ' + $logPath) }
    if (-not (Test-Path -LiteralPath (Join-Path $staging 'manifest.json'))) { throw 'El respaldo no genero su manifiesto.' }
    Write-Host '2/4 Comprimiendo y cifrando con DPAPI del usuario de Windows...'
    $zipPath = Join-Path $runFolder 'temporal.zip'
    [IO.Compression.ZipFile]::CreateFromDirectory($staging, $zipPath)
    $cipherPath = Join-Path $runFolder 'respaldo.dpapi'
    $zipHash = Protect-ArchivoRespaldo $zipPath $cipherPath
    $cipherHash = (Get-FileHash -LiteralPath $cipherPath -Algorithm SHA256).Hash
    $metadata = [ordered]@{
        formato='tienda-dpapi-v1';perfil='tienda-prod';fechaUTC=[DateTime]::UtcNow.ToString('o')
        usuarioSID=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value
        cifrado='Windows DPAPI CurrentUser';SHA256=$cipherHash;zipSHA256=$zipHash
        bytes=(Get-Item -LiteralPath $cipherPath).Length;retencionCopias=$Conservar
        alcance='Copia fuera de los PVC, en la misma laptop. Requiere conservar el perfil y las claves DPAPI del usuario; no demuestra recuperacion tras perder el equipo.'
    }
    $metadata | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $runFolder 'cifrado.json') -Encoding UTF8
    Write-Host '3/4 Descifrado de comprobacion y SHA256 correctos. Retirando temporales...'
    # Borrado limitado a dos rutas literales creadas en esta ejecucion.
    $resolvedRun = (Resolve-Path -LiteralPath $runFolder).Path.TrimEnd('\')
    foreach ($temporary in @($zipPath, $staging)) {
        $resolvedTemporary = (Resolve-Path -LiteralPath $temporary).Path
        if (-not $resolvedTemporary.StartsWith($resolvedRun + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Ruta temporal fuera de la ejecucion.' }
        Remove-Item -LiteralPath $resolvedTemporary -Recurse -Force
    }
    Write-Host ('4/4 Retencion: conservar las ultimas ' + $Conservar + ' copias cifradas completas...')
    $completed = @(Get-ChildItem -LiteralPath $root -Directory | Where-Object {
        $_.Name -match '^\d{8}-\d{9}$' -and
        (Test-Path -LiteralPath (Join-Path $_.FullName 'cifrado.json')) -and
        (Test-Path -LiteralPath (Join-Path $_.FullName 'respaldo.dpapi')) -and
        -not (Test-Path -LiteralPath (Join-Path $_.FullName 'trabajo'))
    } | Sort-Object Name -Descending)
    foreach ($old in @($completed | Select-Object -Skip $Conservar)) {
        if ($old.Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'No se eliminara una carpeta enlazada.' }
        $resolvedOld = (Resolve-Path -LiteralPath $old.FullName).Path
        if ([IO.Path]::GetDirectoryName($resolvedOld) -ne $root) { throw 'Ruta de retencion fuera del destino.' }
        $oldMeta = Get-Content -LiteralPath (Join-Path $resolvedOld 'cifrado.json') -Raw | ConvertFrom-Json
        if ($oldMeta.formato -ne 'tienda-dpapi-v1' -or $oldMeta.usuarioSID -ne $metadata.usuarioSID) { throw 'Copia antigua de formato o usuario diferente; no se elimina.' }
        Remove-Item -LiteralPath $resolvedOld -Recurse -Force
    }
    Write-Host 'RESPALDO CIFRADO VERIFICADO.'
    Write-Host ('Carpeta: ' + $runFolder)
    Write-Host ('Bytes: ' + $metadata.bytes)
    Write-Host ('SHA256: ' + $cipherHash)
    Write-Host 'Los respaldos manuales anteriores se conservan.'
    try { & (Join-Path $PSScriptRoot 'Publicar-MetricasRespaldo.ps1') } catch { Write-Warning 'Respaldo correcto, pero no se actualizaron sus metricas; revisar monitoreo.' }
} finally { $lock.Dispose() }
