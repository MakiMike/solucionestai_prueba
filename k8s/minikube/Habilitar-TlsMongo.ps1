$ErrorActionPreference = 'Stop'
function Assert-Kubectl([string]$Mensaje) { if ($LASTEXITCODE -ne 0) { throw $Mensaje } }
function Add-MongoTls([string]$Uri) {
    $parts = $Uri.Split('?', 2)
    if ($parts[0] -notmatch '^mongodb://') { throw 'URI MongoDB inesperada.' }
    $query = @()
    if ($parts.Count -gt 1) { $query = @($parts[1].Split('&') | Where-Object { $_ -and $_ -notmatch '^(tls|ssl|tlsCAFile|tlsAllowInvalidCertificates|tlsAllowInvalidHostnames|tlsInsecure)=' }) }
    $query += @('tls=true','tlsCAFile=%2Fetc%2Fmongo-ca%2Fca.crt','tlsAllowInvalidCertificates=false','tlsAllowInvalidHostnames=false')
    return $parts[0] + '?' + ($query -join '&')
}
function Set-SecretMongoTls([string]$SecretName, [string[]]$Keys) {
    $raw = & minikube -p tienda-prod kubectl -- get secret $SecretName -n tienda-prod -o json
    Assert-Kubectl ('No se pudo consultar el Secret ' + $SecretName)
    $secret = $raw | ConvertFrom-Json
    foreach ($keyName in $Keys) {
        if (-not $secret.data.$keyName) { throw ('Falta una clave esperada en ' + $SecretName) }
        $uri = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($secret.data.$keyName))
        $secret.data.$keyName = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes((Add-MongoTls $uri)))
    }
    # Replace conserva todas las claves y comprueba resourceVersion. No imprime valores.
    $replacement = @{apiVersion='v1';kind='Secret';metadata=@{name=$SecretName;namespace='tienda-prod';resourceVersion=$secret.metadata.resourceVersion};type=$secret.type;data=$secret.data}
    $replacement | ConvertTo-Json -Depth 12 | & minikube -p tienda-prod kubectl -- replace -f -
    Assert-Kubectl ('No se pudo actualizar el Secret ' + $SecretName)
    if ($SecretName -eq 'tienda-secrets') {
        $envFile = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\secrets.env'))
        if (Test-Path -LiteralPath $envFile) {
            $lines = [IO.File]::ReadAllLines($envFile)
            for ($index=0; $index -lt $lines.Length; $index++) {
                if ($lines[$index] -match '^(MONGO_URI|MONGO_OWNER_URI)=') {
                    $name = $Matches[1]
                    $value = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($secret.data.$name))
                    $lines[$index] = $name + '=' + $value
                }
            }
            [IO.File]::WriteAllLines($envFile, $lines, (New-Object Text.UTF8Encoding($false)))
        }
    }
}
$mkcert = Get-Command mkcert -ErrorAction SilentlyContinue
$mkcertExecutable = if ($mkcert) { $mkcert.Source } else { Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Links\mkcert.exe' }
if (-not (Test-Path -LiteralPath $mkcertExecutable)) { throw 'No se encontro la instalacion existente de mkcert.' }
$tlsFolder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\tls'))
New-Item -ItemType Directory -Force -Path $tlsFolder | Out-Null
$cert = Join-Path $tlsFolder 'mongo.crt'
$key = Join-Path $tlsFolder 'mongo.key'
if ((Test-Path -LiteralPath $cert) -xor (Test-Path -LiteralPath $key)) { throw 'Falta uno de los archivos TLS MongoDB. Revisar antes de continuar.' }
if (-not (Test-Path -LiteralPath $cert)) {
    & $mkcertExecutable -cert-file $cert -key-file $key mongo mongo.tienda-prod mongo.tienda-prod.svc mongo.tienda-prod.svc.cluster.local localhost 127.0.0.1
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo generar el certificado MongoDB.' }
}
$caRoot = (& $mkcertExecutable -CAROOT | Out-String).Trim()
if ($LASTEXITCODE -ne 0) { throw 'No se pudo localizar la CA.' }
$ca = Join-Path $caRoot 'rootCA.pem'
$pem = Join-Path $tlsFolder 'mongo-server.pem'
[IO.File]::WriteAllText($pem, ([IO.File]::ReadAllText($cert).Trim() + "`n" + [IO.File]::ReadAllText($key).Trim() + "`n"), (New-Object Text.UTF8Encoding($false)))
Write-Host '1/4 Preparando certificado del servidor y CA publica...'
& minikube -p tienda-prod kubectl -- create secret generic mongo-tls -n tienda-prod --from-file="server.pem=$pem" --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f -
Assert-Kubectl 'No se pudo preparar el certificado MongoDB.'
& minikube -p tienda-prod kubectl -- create configmap mongo-ca -n tienda-prod --from-file="ca.crt=$ca" --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f -
Assert-Kubectl 'No se pudo preparar la CA publica.'
Write-Host '2/4 Exigiendo TLS en MongoDB (reinicio de la unica instancia)...'
& minikube -p tienda-prod kubectl -- patch statefulset mongo -n tienda-prod --type=strategic --patch-file (Join-Path $PSScriptRoot 'mongo-tls.patch.yaml')
Assert-Kubectl 'No se pudo configurar MongoDB.'
& minikube -p tienda-prod kubectl -- rollout status statefulset/mongo -n tienda-prod --timeout=300s
Assert-Kubectl 'MongoDB no termino su reinicio. Revisar logs antes de continuar.'
Write-Host '3/4 Configurando clientes y exporter sin mostrar credenciales...'
Set-SecretMongoTls 'tienda-secrets' @('MONGO_URI','MONGO_OWNER_URI')
Set-SecretMongoTls 'mongo-metrics' @('uri')
$caVolume = @{name='mongo-ca';configMap=@{name='mongo-ca'}}
$caMount = @{name='mongo-ca';mountPath='/etc/mongo-ca';readOnly=$true}
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\tls-mongo'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
function New-ClientSpec([string]$ContainerName) {
    return @{volumes=@($caVolume);containers=@(@{name=$ContainerName;volumeMounts=@($caMount)})}
}
foreach ($entry in @(@{Name='backend';Container='backend'},@{Name='mongo-exporter';Container='exporter'})) {
    $patch = @{spec=@{template=@{spec=(New-ClientSpec $entry.Container)}}}
    $patchFile = Join-Path $evidencePath ($entry.Name + '.patch.json')
    $patch | ConvertTo-Json -Depth 15 | Set-Content -LiteralPath $patchFile -Encoding UTF8
    & minikube -p tienda-prod kubectl -- patch deployment $entry.Name -n tienda-prod --type=strategic --patch-file $patchFile
    Assert-Kubectl ('No se pudo configurar ' + $entry.Name)
}
foreach ($jobName in @('vencer-pedidos','sincronizar-historial')) {
    $job = & minikube -p tienda-prod kubectl -- get cronjob $jobName -n tienda-prod -o json
    Assert-Kubectl ('No se pudo consultar ' + $jobName)
    $jobObject = $job | ConvertFrom-Json
    $containerName = $jobObject.spec.jobTemplate.spec.template.spec.containers[0].name
    $patch = @{spec=@{jobTemplate=@{spec=@{template=@{spec=(New-ClientSpec $containerName)}}}}}
    $patchFile = Join-Path $evidencePath ($jobName + '.patch.json')
    $patch | ConvertTo-Json -Depth 15 | Set-Content -LiteralPath $patchFile -Encoding UTF8
    & minikube -p tienda-prod kubectl -- patch cronjob $jobName -n tienda-prod --type=strategic --patch-file $patchFile
    Assert-Kubectl ('No se pudo configurar ' + $jobName)
}
Write-Host '4/4 Esperando las replicas actualizadas...'
& minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
Assert-Kubectl 'El backend no termino de actualizarse.'
& minikube -p tienda-prod kubectl -- rollout status deployment/mongo-exporter -n tienda-prod --timeout=180s
Assert-Kubectl 'El exporter no termino de actualizarse.'
Write-Host 'TLS MONGODB CONFIGURADO. Ejecutar Comprobar-TlsMongo.ps1.'
