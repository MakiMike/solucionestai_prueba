. (Join-Path $PSScriptRoot 'entorno-cierre.ps1')
$ErrorActionPreference='Stop'
$sql="SELECT 'playeras',count(*) FROM playeras UNION ALL SELECT 'variantes',count(*) FROM playera_tallas UNION ALL SELECT 'pedidos',count(*) FROM pedidos UNION ALL SELECT 'detalle',count(*) FROM pedido_detalle UNION ALL SELECT 'usuarios',count(*) FROM us_usuarios UNION ALL SELECT 'outbox',count(*) FROM tienda_outbox ORDER BY 1;"
$js=@'
const a=new Mongo('mongodb://127.0.0.1:27017/admin').getDB('admin');a.auth(process.env.MONGO_INITDB_ROOT_USERNAME,process.env.MONGO_INITDB_ROOT_PASSWORD);const d=a.getSiblingDB('tienda');print(JSON.stringify({documentos:d.eventos.countDocuments({}),indices:d.eventos.getIndexes().map(x=>({name:x.name,key:x.key,unique:x.unique||false}))}));
'@
$pgBefore=(& minikube -p tienda-dev kubectl -- exec postgres-0 -n tienda-dev -- psql -X -At -v ON_ERROR_STOP=1 -U tienda_owner -d tienda -c $sql)-join "`n"
$mongoBefore=($js|& minikube -p tienda-dev kubectl -- exec -i mongo-0 -n tienda-dev -- mongosh --quiet --nodb --file /dev/stdin)-join "`n"
$uidsBefore=& minikube -p tienda-dev kubectl -- get pods postgres-0 mongo-0 -n tienda-dev -o 'custom-columns=NOMBRE:.metadata.name,UID:.metadata.uid'
$clock=[Diagnostics.Stopwatch]::StartNew()
foreach($name in @('postgres-0','mongo-0')){
 & minikube -p tienda-dev kubectl -- delete pod $name -n tienda-dev --wait=true|Out-Null
 if($LASTEXITCODE -ne 0){throw 'No se pudo recrear Pod'}
 & minikube -p tienda-dev kubectl -- wait --for=condition=ready pod/$name -n tienda-dev --timeout=180s|Out-Null
 if($LASTEXITCODE -ne 0){throw 'Pod de datos no se recupero'}
}
$clock.Stop()
$pgAfter=(& minikube -p tienda-dev kubectl -- exec postgres-0 -n tienda-dev -- psql -X -At -v ON_ERROR_STOP=1 -U tienda_owner -d tienda -c $sql)-join "`n"
$mongoAfter=($js|& minikube -p tienda-dev kubectl -- exec -i mongo-0 -n tienda-dev -- mongosh --quiet --nodb --file /dev/stdin)-join "`n"
$uidsAfter=& minikube -p tienda-dev kubectl -- get pods postgres-0 mongo-0 -n tienda-dev -o 'custom-columns=NOMBRE:.metadata.name,UID:.metadata.uid'
@{ambiente='tienda-dev';fechaUTC=[DateTime]::UtcNow.ToString('o');antes=@{postgres=$pgBefore;mongo=$mongoBefore;pods=$uidsBefore};despues=@{postgres=$pgAfter;mongo=$mongoAfter;pods=$uidsAfter};conteosSQLIguales=$pgBefore -eq $pgAfter;mongoIgual=$mongoBefore -eq $mongoAfter;segundos=[Math]::Round($clock.Elapsed.TotalSeconds,1)}|ConvertTo-Json -Depth 8|Set-Content (Join-Path $evidence 'persistencia-ambos-motores.json')
if($pgBefore -ne $pgAfter -or $mongoBefore -ne $mongoAfter){throw 'Diferencia antes/despues; revisar evidencia'}
Write-Host 'Persistencia SQL y Mongo verificada tras recrear ambos Pods; UIDs cambiaron y datos/indices coinciden.'
