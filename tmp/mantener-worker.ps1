. (Join-Path $PSScriptRoot 'entorno-cierre.ps1')
$ErrorActionPreference='Continue'
$node='tienda-prod-m03';$restoreNS='tienda-restore-20261025-235828'
$raw=& minikube -p tienda-prod kubectl -- get pod mongo-restore -n $restoreNS -o json
if($LASTEXITCODE -ne 0){throw 'No se pudo conservar el Pod temporal de restauracion'}
$p=$raw|ConvertFrom-Json
$saved=@{apiVersion='v1';kind='Pod';metadata=@{name='mongo-restore';namespace=$restoreNS;labels=$p.metadata.labels};spec=$p.spec}
$savedPath=Join-Path $evidence 'pod-restauracion-conservado.json';$saved|ConvertTo-Json -Depth 40|Set-Content $savedPath
$traefik=(& minikube -p tienda-prod kubectl -- get pods -n kube-system -l app.kubernetes.io/name=traefik -o json|ConvertFrom-Json).items|Where-Object {$_.spec.nodeName -ne $node -and $_.status.phase -eq 'Running'}|Select-Object -First 1
if(-not $traefik){throw 'No hay entrada estable fuera del nodo'}
$forward=Start-Process -FilePath 'C:\Program Files\Kubernetes\Minikube\minikube.exe' -ArgumentList @('-p','tienda-prod','kubectl','--','port-forward','-n','kube-system',('pod/'+$traefik.metadata.name),'9445:8443','--address=127.0.0.1') -WindowStyle Hidden -RedirectStandardOutput (Join-Path $evidence 'forward-prueba.log') -RedirectStandardError (Join-Path $evidence 'forward-prueba.err.log') -PassThru
Start-Sleep -Seconds 2
$baseline=& curl.exe --silent --noproxy '*' --ssl-revoke-best-effort --connect-timeout 2 --max-time 4 --output NUL --write-out '%{http_code}' https://tskull-shirt.test:9445/health/ready
if($LASTEXITCODE -ne 0 -or $baseline -ne '200'){throw 'Sonda inicial no responde 200; no iniciar mantenimiento'}
$probe=Start-Job -ScriptBlock {
 $rows=@();$end=[DateTime]::UtcNow.AddSeconds(90)
 do{$code=& curl.exe --silent --noproxy '*' --ssl-revoke-best-effort --connect-timeout 2 --max-time 3 --output NUL --write-out '%{http_code}' https://tskull-shirt.test:9445/health/ready;$rows+=[pscustomobject]@{fechaUTC=[DateTime]::UtcNow.ToString('o');http=$code;salida=$LASTEXITCODE};Start-Sleep -Seconds 1}while([DateTime]::UtcNow -lt $end);$rows
}
try{
 & minikube -p tienda-prod kubectl -- get pods -A --field-selector spec.nodeName=$node -o wide|Set-Content (Join-Path $evidence 'worker-antes.txt')
 # El Pod suelto pertenece a una restauracion de prueba, no a produccion.
 # Conservar su especificacion y PVC; retirarlo antes del mantenimiento sin usar --force.
 & minikube -p tienda-prod kubectl -- delete pod mongo-restore -n $restoreNS --wait=true|Out-Null
 & minikube -p tienda-prod kubectl -- drain $node --ignore-daemonsets --delete-emptydir-data --timeout=120s *> (Join-Path $evidence 'worker-drain.txt')
 if($LASTEXITCODE -ne 0){throw 'Drain no completo; no se fuerza la eviccion'}
 & minikube -p tienda-prod kubectl -- get pods -A --field-selector spec.nodeName=$node -o wide|Set-Content (Join-Path $evidence 'worker-drenado.txt')
}finally{
 & minikube -p tienda-prod kubectl -- uncordon $node|Out-Null
 & minikube -p tienda-prod kubectl -- apply -f $savedPath|Out-Null
}
& minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=180s|Out-Null
& minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n tienda-prod --timeout=180s|Out-Null
& minikube -p tienda-prod kubectl -- wait --for=condition=ready pod/mongo-restore -n $restoreNS --timeout=180s|Out-Null
$probe|Wait-Job -Timeout 120|Out-Null
$rows=Receive-Job $probe;$rows|Export-Csv (Join-Path $evidence 'worker-sonda.csv') -NoTypeInformation
@{mantenimiento='drain completo sin selector de aplicacion y sin --force';entrada='Traefik en nodo distinto, TLS validado';correctas=@($rows|Where-Object http -eq '200').Count;fallos=@($rows|Where-Object http -ne '200').Count;replicas='rollout backend/frontend terminado';restauracionAnterior='Pod Mongo recreado con el mismo PVC';limitacion='Grafana de instancia unica puede quedar pendiente de su PVC durante el mantenimiento; no se afirma HA del monitoreo ni de las bases.'}|ConvertTo-Json|Set-Content (Join-Path $evidence 'worker-resultado.json')
Get-NetTCPConnection -LocalPort 9445 -State Listen -ErrorAction SilentlyContinue|ForEach-Object{$proc=Get-CimInstance Win32_Process -Filter "ProcessId=$($_.OwningProcess)";if($proc.CommandLine -match '9445:8443'){Stop-Process -Id $_.OwningProcess}}
Stop-Process -Id $forward.Id -ErrorAction SilentlyContinue
Write-Host 'Worker drenado y restaurado, evidencias registradas.'
