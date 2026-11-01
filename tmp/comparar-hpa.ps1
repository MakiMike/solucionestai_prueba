. (Join-Path $PSScriptRoot 'entorno-cierre.ps1')
$ErrorActionPreference='Stop'
function Kube { if($MyInvocation.ExpectingInput){$input | & minikube -p tienda-prod kubectl -- @args}else{& minikube -p tienda-prod kubectl -- @args}; if($LASTEXITCODE -ne 0){throw 'Comando Kubernetes fallo en prueba HPA'} }
$hpa=Kube get hpa backend -n tienda-prod -o json | ConvertFrom-Json
$restore=@{apiVersion=$hpa.apiVersion;kind='HorizontalPodAutoscaler';metadata=@{name='backend';namespace='tienda-prod'};spec=$hpa.spec}
$restorePath=Join-Path $evidence 'hpa-original.json'
$restore|ConvertTo-Json -Depth 30|Set-Content $restorePath
$parts=(Get-Content k8s/minikube/carga-hpa.yaml -Raw)-split '(?m)^---\s*$'
$policyPath=Join-Path $evidence 'politicas-carga.yaml'
($parts|Select-Object -First 2)-join "`n---`n"|Set-Content $policyPath
$samples=New-Object Collections.Generic.List[object]
try{
 Kube apply -f $policyPath | Out-Null
 Kube create configmap carga-comparativa -n tienda-prod --from-file=load.php=tests/carga-comparativa.php --dry-run=client -o json | & minikube -p tienda-prod kubectl -- apply -f - | Out-Null
 foreach($phase in @('sin-hpa','con-hpa')){
  if($phase -eq 'sin-hpa'){Kube delete hpa backend -n tienda-prod|Out-Null;Kube scale deployment/backend -n tienda-prod --replicas=2|Out-Null}else{Kube apply -f $restorePath|Out-Null}
  Kube rollout status deployment/backend -n tienda-prod --timeout=180s|Out-Null
  $name='comparacion-'+$phase
  $container=@{name='carga';image='tienda-backend:0.1.6';imagePullPolicy='IfNotPresent';command=@('php','/load/load.php','300','24');resources=@{requests=@{cpu='100m';memory='192Mi'};limits=@{cpu='1';memory='768Mi'}};securityContext=@{runAsNonRoot=$true;runAsUser=33;allowPrivilegeEscalation=$false;readOnlyRootFilesystem=$true;capabilities=@{drop=@('ALL')}};volumeMounts=@(@{name='script';mountPath='/load';readOnly=$true},@{name='tmp';mountPath='/tmp'})}
  $job=@{apiVersion='batch/v1';kind='Job';metadata=@{name=$name;namespace='tienda-prod'};spec=@{backoffLimit=0;activeDeadlineSeconds=420;template=@{metadata=@{labels=@{app='prueba-carga'}};spec=@{serviceAccountName='tienda';automountServiceAccountToken=$false;restartPolicy='Never';securityContext=@{fsGroup=33;seccompProfile=@{type='RuntimeDefault'}};containers=@($container);volumes=@(@{name='script';configMap=@{name='carga-comparativa'}},@{name='tmp';emptyDir=@{}})}}}}
  $job|ConvertTo-Json -Depth 30|Kube apply -f -|Out-Null
  $deadline=[DateTime]::UtcNow.AddSeconds(450)
  do{
   $deploy=Kube get deployment backend -n tienda-prod -o json|ConvertFrom-Json
   $samples.Add([pscustomobject]@{fechaUTC=[DateTime]::UtcNow.ToString('o');fase=$phase;deseadas=$deploy.spec.replicas;listas=$deploy.status.readyReplicas;actuales=$deploy.status.replicas})
   $samples|Export-Csv (Join-Path $evidence 'hpa-series.csv') -NoTypeInformation
   Kube top pods -n tienda-prod -l app=backend | Add-Content (Join-Path $evidence ('recursos-'+$phase+'.txt'))
   $state=Kube get job $name -n tienda-prod -o json|ConvertFrom-Json
   if($state.status.failed){throw 'Carga fallo'}
   if($state.status.succeeded){break}
   Start-Sleep -Seconds 15
  }while([DateTime]::UtcNow -lt $deadline)
  Kube logs job/$name -n tienda-prod | Set-Content (Join-Path $evidence ($phase+'.json'))
  if(-not $state.status.succeeded){throw 'La carga no termino a tiempo'}
 }
 $until=[DateTime]::UtcNow.AddSeconds(420)
 do{
  $deploy=Kube get deployment backend -n tienda-prod -o json|ConvertFrom-Json
  $samples.Add([pscustomobject]@{fechaUTC=[DateTime]::UtcNow.ToString('o');fase='recuperacion';deseadas=$deploy.spec.replicas;listas=$deploy.status.readyReplicas;actuales=$deploy.status.replicas})
  $samples|Export-Csv (Join-Path $evidence 'hpa-series.csv') -NoTypeInformation
  if($deploy.spec.replicas -eq 2 -and $deploy.status.readyReplicas -eq 2){break}
  Start-Sleep -Seconds 15
 }while([DateTime]::UtcNow -lt $until)
 Write-Host 'Comparacion y series finalizadas.'
}finally{
 & minikube -p tienda-prod kubectl -- apply -f $restorePath|Out-Null
 & minikube -p tienda-prod kubectl -- delete -f $policyPath --ignore-not-found|Out-Null
}
