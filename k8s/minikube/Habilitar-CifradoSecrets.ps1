$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'Seguridad-Respaldos.ps1')
function Assert-Command([string]$Message) { if ($LASTEXITCODE -ne 0) { throw $Message } }
$remoteConfig = '/var/lib/minikube/certs/encryption-config.json'
$privateFolder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\tls\secrets-etcd'))
Set-CarpetaPrivada $privateFolder
$protectedFile = Join-Path $privateFolder 'encryption-config.dpapi'
$clearFile = Join-Path $privateFolder 'encryption-config.json'
$remoteHash = @(& minikube -p tienda-prod ssh -- "if sudo test -f $remoteConfig; then sudo sha256sum $remoteConfig | cut -d ' ' -f 1; else echo ausente; fi")
Assert-Command 'No se pudo comprobar la configuracion existente del nodo.'
$remoteHash = ($remoteHash -join '').Trim()
Write-Host '1/4 Preparando clave de 256 bits y copia cifrada ligada al usuario de Windows...'
if (Test-Path -LiteralPath $protectedFile) {
    $configBytes = [Security.Cryptography.ProtectedData]::Unprotect([IO.File]::ReadAllBytes($protectedFile), $null, [Security.Cryptography.DataProtectionScope]::CurrentUser)
} else {
    if ($remoteHash -ne 'ausente') { throw 'Ya existe cifrado en el nodo pero falta su copia local. No se genera otra clave.' }
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    $keyBytes = New-Object byte[] 32
    $rng.GetBytes($keyBytes)
    $rng.Dispose()
    $configuration = @{apiVersion='apiserver.config.k8s.io/v1';kind='EncryptionConfiguration';resources=@(@{
        resources=@('secrets');providers=@(
            @{secretbox=@{keys=@(@{name='tienda-key1';secret=[Convert]::ToBase64String($keyBytes)})}},
            @{identity=@{}}
        )
    })}
    $configBytes = [Text.Encoding]::UTF8.GetBytes(($configuration | ConvertTo-Json -Depth 15))
    $protected = [Security.Cryptography.ProtectedData]::Protect($configBytes, $null, [Security.Cryptography.DataProtectionScope]::CurrentUser)
    [IO.File]::WriteAllBytes($protectedFile, $protected)
}
$configuration = [Text.Encoding]::UTF8.GetString($configBytes) | ConvertFrom-Json
if ($configuration.kind -ne 'EncryptionConfiguration' -or -not $configuration.resources[0].providers[0].secretbox) { throw 'Configuracion local de cifrado inesperada.' }
$hash = (Get-HashBytes $configBytes).ToLowerInvariant()
if ($remoteHash -ne 'ausente' -and $remoteHash -ne $hash) { throw 'La clave/configuracion del nodo no coincide con la copia protegida. No se sobrescribe.' }
if ($remoteHash -eq 'ausente') {
    [IO.File]::WriteAllBytes($clearFile, $configBytes)
    try {
        # Crear el destino de transferencia con permisos privados antes de copiar.
        & minikube -p tienda-prod ssh -- 'umask 077; touch /tmp/tienda-encryption-config.json; chmod 600 /tmp/tienda-encryption-config.json'
        Assert-Command 'No se pudo preparar la transferencia privada.'
        Push-Location $privateFolder
        try { & minikube -p tienda-prod cp './encryption-config.json' 'tienda-prod:/tmp/tienda-encryption-config.json' }
        finally { Pop-Location }
        Assert-Command 'No se pudo transferir la configuracion.'
        & minikube -p tienda-prod ssh -- 'sudo install -o root -g root -m 600 /tmp/tienda-encryption-config.json /var/lib/minikube/certs/encryption-config.json && rm -f /tmp/tienda-encryption-config.json'
        Assert-Command 'No se pudo instalar la configuracion protegida en el nodo.'
    } finally {
        if (Test-Path -LiteralPath $clearFile) { Remove-Item -LiteralPath $clearFile -Force }
    }
}
Write-Host '2/4 Activando cifrado en la API de Kubernetes...'
# Normalizar LF antes de transferir el script de Linux.
$helperFile = Join-Path $privateFolder 'activar-cifrado-api.sh'
$helper = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'activar-cifrado-api.sh')).Replace("`r`n", "`n")
[IO.File]::WriteAllText($helperFile, $helper, (New-Object Text.UTF8Encoding($false)))
Push-Location $privateFolder
try { & minikube -p tienda-prod cp './activar-cifrado-api.sh' 'tienda-prod:/tmp/tienda-activar-cifrado.sh' }
finally { Pop-Location }
Assert-Command 'No se pudo transferir el procedimiento de activacion.'
& minikube -p tienda-prod ssh -- 'sudo sh /tmp/tienda-activar-cifrado.sh'
Assert-Command 'No se pudo activar el cifrado en la API.'
Write-Host 'Esperando la API y su configuracion de cifrado (hasta 4 minutos)...'
$ready = $false
for ($attempt=0; $attempt -lt 40; $attempt++) {
    Start-Sleep -Seconds 3
    # El reinicio puede tardar varios segundos en comenzar: comprobar el static Pod real.
    try {
        $podRaw = & minikube -p tienda-prod kubectl -- get pod kube-apiserver-tienda-prod -n kube-system --request-timeout=3s -o json 2>$null
        if ($LASTEXITCODE -ne 0) { continue }
        $apiPod = $podRaw | ConvertFrom-Json
        $hasFlag = @($apiPod.spec.containers[0].command) -contains ('--encryption-provider-config=' + $remoteConfig)
        $healthy = @($apiPod.status.conditions | Where-Object { $_.type -eq 'Ready' -and $_.status -eq 'True' }).Count -gt 0
        if ($hasFlag -and $healthy) { $ready=$true; break }
    } catch { continue }
}
if (-not $ready) { throw 'La API no confirmo su nueva configuracion. Conservar la clave y revisar; no quitar la configuracion de cifrado.' }
Write-Host '3/4 Reescribiendo los Secrets existentes sin cambiar sus valores...'
$rewritten = $false
for ($attempt=0; $attempt -lt 3; $attempt++) {
    $secrets = & minikube -p tienda-prod kubectl -- get secrets --all-namespaces -o json
    Assert-Command 'No se pudieron leer los Secrets mediante la API.'
    $secrets | & minikube -p tienda-prod kubectl -- replace -f - | Out-Null
    if ($LASTEXITCODE -eq 0) { $rewritten=$true; break }
    Start-Sleep -Seconds 2
}
if (-not $rewritten) { throw 'Hubo conflictos al reescribir Secrets. Repetir este script conserva la misma clave.' }
Write-Host '4/4 Comprobando almacenamiento cifrado y lectura autorizada...'
& (Join-Path $PSScriptRoot 'Comprobar-CifradoSecrets.ps1')
Write-Host ('Copia protegida de la clave: ' + $protectedFile)
Write-Host 'Conservar el perfil DPAPI de Windows y esta copia. No borrar la clave del nodo.'
