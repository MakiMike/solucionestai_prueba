. (Join-Path $PSScriptRoot 'entorno-cierre.ps1')
$ErrorActionPreference='Continue'
& powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Respaldar-Bases.ps1 *> (Join-Path $evidence 'respaldo-nuevo.txt')
if($LASTEXITCODE -ne 0){throw 'Respaldo fallo'}
$backup=Get-ChildItem output/respaldos -Directory|Sort-Object Name -Descending|Select-Object -First 1
$manifest=Get-Content (Join-Path $backup.FullName 'manifest.json') -Raw|ConvertFrom-Json
$start=[DateTime]::UtcNow;$watch=[Diagnostics.Stopwatch]::StartNew()
& powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Restaurar-Prueba.ps1 -CarpetaRespaldo $backup.FullName *> (Join-Path $evidence 'restauracion-nueva.txt')
if($LASTEXITCODE -ne 0){throw 'Restauracion fallo; conservar intento para RTO'}
$restoreResult=Get-ChildItem output/evidencias/restauracion -Directory|Sort-Object Name -Descending|Select-Object -First 1
$result=Get-Content (Join-Path $restoreResult.FullName 'resultado.json') -Raw|ConvertFrom-Json
$ns=$result.namespace
& minikube -p tienda-prod kubectl -- label pod postgres-restore app=postgres-restore -n $ns --overwrite|Out-Null
& minikube -p tienda-prod kubectl -- label pod mongo-restore app=mongo-restore -n $ns --overwrite|Out-Null
& minikube -p tienda-prod kubectl -- apply -f k8s/minikube/restauracion/aplicacion-recuperada.yaml -n $ns|Out-Null
if($LASTEXITCODE -ne 0){throw 'No se pudo iniciar aplicacion restaurada'}
& minikube -p tienda-prod kubectl -- rollout status deployment/backend -n $ns --timeout=300s *> (Join-Path $evidence 'restore-backend-rollout.txt')
if($LASTEXITCODE -ne 0){throw 'Aplicacion restaurada no Ready'}
& minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n $ns --timeout=300s *> (Join-Path $evidence 'restore-frontend-rollout.txt')
if($LASTEXITCODE -ne 0){throw 'Frontend restaurado no Ready'}
$code=& minikube -p tienda-prod kubectl -- exec deployment/frontend -n $ns -- wget -q -O - http://127.0.0.1:8080/health/ready
if($LASTEXITCODE -ne 0 -or ($code -join '') -notmatch '"status":"ok"'){throw 'Sonda restaurada fallo'}
$login=& minikube -p tienda-prod kubectl -- exec deployment/frontend -n $ns -- wget -q -O - http://127.0.0.1:8080/login
if($LASTEXITCODE -ne 0 -or ($login -join '') -notmatch 'Iniciar sesi'){throw 'Login restaurado no disponible'}
$watch.Stop()
@{inicioIncidenteSimuladoUTC=$start.ToString('o');finValidacionUTC=[DateTime]::UtcNow.ToString('o');RTOsegundos=[Math]::Round($watch.Elapsed.TotalSeconds,2);namespace=$ns;carpetaRespaldo=$backup.FullName;manifest=$manifest;salud='HTTP interno 200 y status ok';interfaz='Login disponible; conteos, indices y fotos SHA256 verificados';alcance='Restauracion aislada desde cero y arranque de aplicacion; no se detuvo produccion ni se simulo perdida de la laptop.'}|ConvertTo-Json -Depth 12|Set-Content (Join-Path $evidence 'rto-completo.json')
Write-Host ('RTO medido hasta salud y login: '+[Math]::Round($watch.Elapsed.TotalSeconds,2)+' s; namespace '+$ns)
& powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Respaldo-Programado.ps1 *> (Join-Path $evidence 'respaldo-cifrado-nuevo.txt')
if($LASTEXITCODE -ne 0){throw 'Respaldo cifrado fallo'}
