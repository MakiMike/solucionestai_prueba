$ErrorActionPreference = 'Stop'
$checks = @(
    @{Cuenta='tienda-observador';Verbo='list';Recurso='pods';Extra=@();Esperado='yes'},
    @{Cuenta='tienda-observador';Verbo='patch';Recurso='deployment/backend';Extra=@('--subresource=scale');Esperado='no'},
    @{Cuenta='tienda-operador';Verbo='list';Recurso='pods';Extra=@();Esperado='yes'},
    @{Cuenta='tienda-operador';Verbo='patch';Recurso='deployment/backend';Extra=@('--subresource=scale');Esperado='yes'},
    @{Cuenta='tienda-operador';Verbo='patch';Recurso='deployment/frontend';Extra=@('--subresource=scale');Esperado='yes'},
    @{Cuenta='tienda-operador';Verbo='patch';Recurso='deployment/backend';Extra=@();Esperado='no'},
    @{Cuenta='tienda-operador';Verbo='patch';Recurso='statefulset/postgres';Extra=@();Esperado='no'},
    @{Cuenta='tienda-operador';Verbo='create';Recurso='pods';Extra=@('--subresource=exec');Esperado='no'},
    @{Cuenta='tienda-operador';Verbo='create';Recurso='rolebindings';Extra=@();Esperado='no'},
    @{Cuenta='tienda-operador';Verbo='list';Recurso='pods';Extra=@();Namespace='monitoreo';Esperado='no'}
)
foreach ($account in @('tienda-observador','tienda-operador')) {
    $checks += @{Cuenta=$account;Verbo='get';Recurso='secret/tienda-secrets';Extra=@();Esperado='no'}
}
$rows = foreach ($check in $checks) {
    $identity = 'system:serviceaccount:tienda-prod:' + $check.Cuenta
    $extra = $check.Extra
    $targetNamespace = 'tienda-prod'
    if ($check.Namespace) { $targetNamespace = $check.Namespace }
    $answer = @(& minikube -p tienda-prod kubectl -- auth can-i $check.Verbo $check.Recurso -n $targetNamespace --as=$identity @extra)
    $exitCode = $LASTEXITCODE
    $actual = ($answer -join '').Trim()
    if ($exitCode -notin @(0,1) -or $actual -notin @('yes','no')) { throw 'No se pudo consultar la autorizacion.' }
    [pscustomobject]@{Cuenta=$check.Cuenta;Namespace=$targetNamespace;Accion=($check.Verbo+' '+$check.Recurso+' '+($extra -join ' ')).Trim();Esperado=$check.Esperado;Resultado=$actual;Correcto=($actual -eq $check.Esperado)}
}
$rows | Format-Table -AutoSize | Out-Host
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\seguridad'))
New-Item -ItemType Directory -Force $evidencePath | Out-Null
$evidenceFile = Join-Path $evidencePath ('rbac-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.csv')
$rows | Export-Csv -LiteralPath $evidenceFile -NoTypeInformation -Encoding UTF8
if (@($rows | Where-Object { -not $_.Correcto }).Count -gt 0) { throw ('Hay permisos diferentes a los esperados. Revisar ' + $evidenceFile) }
Write-Host 'PERMISOS VERIFICADOS: todas las respuestas coinciden con lo esperado.'
Write-Host ('Evidencia: ' + $evidenceFile)
Write-Host 'Se consulto la autorizacion; no se cambiaron replicas ni se leyeron secretos.'
