$ErrorActionPreference = 'Stop'
function Assert-Kubectl([string]$Mensaje) { if ($LASTEXITCODE -ne 0) { throw $Mensaje } }
$mkcert = Get-Command mkcert -ErrorAction SilentlyContinue
$mkcertExecutable = if ($mkcert) { $mkcert.Source } else { Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Links\mkcert.exe' }
if (-not (Test-Path -LiteralPath $mkcertExecutable)) { throw 'No se encontro mkcert; revisar su instalacion existente.' }
$tlsFolder = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\tls'))
New-Item -ItemType Directory -Force -Path $tlsFolder | Out-Null
$cert = Join-Path $tlsFolder 'postgres.crt'
$key = Join-Path $tlsFolder 'postgres.key'
if ((Test-Path -LiteralPath $cert) -xor (Test-Path -LiteralPath $key)) { throw 'Existe solo uno de los archivos TLS PostgreSQL. Revisar antes de continuar.' }
if (-not (Test-Path -LiteralPath $cert)) {
    & $mkcertExecutable -cert-file $cert -key-file $key postgres postgres.tienda-prod postgres.tienda-prod.svc postgres.tienda-prod.svc.cluster.local
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo generar el certificado PostgreSQL.' }
}
$caRoot = (& $mkcertExecutable -CAROOT | Out-String).Trim()
if ($LASTEXITCODE -ne 0) { throw 'No se pudo localizar la CA de mkcert.' }
$ca = Join-Path $caRoot 'rootCA.pem'
if (-not (Test-Path -LiteralPath $ca)) { throw 'No existe la CA publica.' }
Write-Host '1/4 Preparando certificado del servidor y CA publica para clientes...'
# Solo se copia rootCA.pem. La llave privada de la CA permanece en Windows.
& minikube -p tienda-prod kubectl -- create secret tls postgres-tls -n tienda-prod --cert=$cert --key=$key --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f -
Assert-Kubectl 'No se pudo preparar el Secret TLS.'
& minikube -p tienda-prod kubectl -- create configmap postgres-ca -n tienda-prod --from-file="ca.crt=$ca" --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f -
Assert-Kubectl 'No se pudo preparar la CA publica.'
$hba = Join-Path $PSScriptRoot 'postgres-pg_hba.conf'
& minikube -p tienda-prod kubectl -- create configmap postgres-tls-config -n tienda-prod --from-file="pg_hba.conf=$hba" --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f -
Assert-Kubectl 'No se pudo preparar la configuracion de conexiones.'
Write-Host '2/4 Activando TLS en PostgreSQL (reinicio de la unica instancia)...'
& minikube -p tienda-prod kubectl -- patch statefulset postgres -n tienda-prod --type=strategic --patch-file (Join-Path $PSScriptRoot 'postgres-tls.patch.yaml')
Assert-Kubectl 'No se pudo configurar PostgreSQL.'
& minikube -p tienda-prod kubectl -- rollout status statefulset/postgres -n tienda-prod --timeout=300s
Assert-Kubectl 'PostgreSQL no termino el reinicio. Revisar sus logs antes de continuar.'

$caVolume = @{name='postgres-ca';configMap=@{name='postgres-ca'}}
$caMount = @{name='postgres-ca';mountPath='/etc/postgres-ca';readOnly=$true}
function New-ClientSpec([string]$ContainerName) {
    return @{volumes=@($caVolume);containers=@(@{name=$ContainerName;volumeMounts=@($caMount);env=@(
        @{name='DB_SSLMODE';value='verify-full'},
        @{name='DB_SSLROOTCERT';value='/etc/postgres-ca/ca.crt'}
    )})}
}
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\tls-postgres'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
Write-Host '3/4 Configurando backend y tareas con verify-full y CA de confianza...'
$patch = @{spec=@{template=@{spec=(New-ClientSpec 'backend')}}}
$patchFile = Join-Path $evidencePath 'backend.patch.json'
$patch | ConvertTo-Json -Depth 15 | Set-Content -LiteralPath $patchFile -Encoding UTF8
& minikube -p tienda-prod kubectl -- patch deployment backend -n tienda-prod --type=strategic --patch-file $patchFile
Assert-Kubectl 'No se pudo configurar el cliente backend.'
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
Write-Host '4/4 Configurando el exporter PostgreSQL con validacion del certificado...'
$patch = @{spec=@{template=@{spec=@{volumes=@($caVolume);containers=@(@{name='exporter';volumeMounts=@($caMount);env=@(
    @{name='DATA_SOURCE_URI';value='postgres:5432/tienda?sslmode=verify-full&sslrootcert=/etc/postgres-ca/ca.crt'}
)})}}}}
$patchFile = Join-Path $evidencePath 'exporter.patch.json'
$patch | ConvertTo-Json -Depth 15 | Set-Content -LiteralPath $patchFile -Encoding UTF8
& minikube -p tienda-prod kubectl -- patch deployment postgres-exporter -n tienda-prod --type=strategic --patch-file $patchFile
Assert-Kubectl 'No se pudo configurar el exporter.'
& minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
Assert-Kubectl 'El backend no termino de actualizarse.'
& minikube -p tienda-prod kubectl -- rollout status deployment/postgres-exporter -n tienda-prod --timeout=180s
Assert-Kubectl 'El exporter no termino de actualizarse.'
Write-Host 'TLS POSTGRESQL CONFIGURADO. Ejecutar Comprobar-TlsPostgres.ps1 para verificarlo.'
