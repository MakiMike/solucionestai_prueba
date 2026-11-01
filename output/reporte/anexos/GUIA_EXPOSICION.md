# TSkull-Shirt: guía de exposición y acordeón

Preparada el 1 de noviembre de 2026. Para los ambientes existentes de esta laptop; no es el instalador de un equipo nuevo. Ejecutar cada bloque por separado en PowerShell, desde `C:\laragon\www\sistema`. Los comandos de Docker/Minikube los ejecutas tú. No hace falta repetir todas las pruebas del expediente ante el profesor.

## 1. Qué explicar en un minuto

“Mi proyecto es una tienda de ropa con inventario por talla. PostgreSQL guarda usuarios, productos, stock y pedidos. MongoDB guarda el historial de eventos. Al reservar se descuenta stock; al cancelar o vencer el pedido se devuelve una sola vez. El pago es una simulación. Separé desarrollo y producción en dos clústeres Minikube. Producción tiene réplicas web para soportar la pérdida de un Pod, y respaldos para recuperar los datos en otra copia.”

Recorrido de una solicitud: navegador → HTTPS/Traefik → frontend Nginx → backend PHP → bases de datos. El frontend aquí sirve archivos y pasa las solicitudes PHP al backend; no es una aplicación SPA independiente. El navegador no consulta directamente las bases.

| Concepto | Explicación sencilla en este proyecto |
| --- | --- |
| Docker | Ejecuta los contenedores. Minikube usa Docker para sus nodos. |
| Imagen | Paquete de aplicación y dependencias, identificado por versión. No es el dato vivo de la base. |
| Contenedor | Proceso que se ejecuta a partir de una imagen. |
| Pod | Unidad que Kubernetes inicia, reemplaza y conecta. Puede tener varios contenedores. |
| Nodo | Lugar donde Kubernetes ejecuta Pods. Aquí es un contenedor de Minikube, no otra laptop. |
| Control plane | Decide dónde ejecutar cargas y mantiene el estado del clúster. En el laboratorio también puede alojar aplicación. |
| Worker | Nodo que ejecuta cargas. Prod tiene m02 y m03, además del control plane. |
| Deployment | Mantiene las réplicas web y permite actualizarlas o volver a una versión anterior. |
| StatefulSet | Mantiene identidad y volumen de PostgreSQL/MongoDB. Cada motor tiene una instancia. |
| Service | Dirección estable que encuentra los Pods que están preparados. |
| Ingress/Traefik | Entrada HTTPS que dirige el dominio al frontend. |
| PVC | Solicitud de almacenamiento persistente. Sobrevive al reemplazo de un Pod; no es un respaldo. |
| NFS/RWX | Comparte las fotos entre réplicas web de producción. Es un servicio único en este laboratorio. |
| HPA | Aumenta o reduce Pods según CPU. No crea nodos ni añade RAM a la laptop. |
| CronJob | Programa tareas: vencer reservas y sincronizar historial. |
| Outbox | Guarda en SQL un evento pendiente junto al pedido; luego lo copia a MongoDB sin duplicarlo. |
| NetworkPolicy / RBAC | La primera limita conexiones entre Pods; el segundo limita acciones sobre Kubernetes. |
| Prometheus / Grafana / Loki | Métricas / paneles / registros. Alloy recoge logs; Alertmanager gestiona alertas. |
| RPO / RTO | Antigüedad o pérdida de datos tolerada / tiempo necesario para recuperar el servicio. |

Límite que debes poder explicar: las réplicas toleran perder un Pod o mantenimiento controlado de un worker, pero todos los nodos viven en la misma laptop. No hay alta disponibilidad del equipo, de las bases ni de NFS.

## 2. Encender cada día

Abrir Docker Desktop y esperar a que el motor esté listo. No habilitar otro Kubernetes de Docker Desktop. Después:

```powershell
Set-Location C:\laragon\www\sistema
docker info --format '{{.ServerVersion}}'
minikube start -p tienda-prod --extra-config=apiserver.encryption-provider-config=/var/lib/minikube/certs/encryption-config.json
minikube start -p tienda-dev
minikube -p tienda-prod kubectl -- get nodes
minikube -p tienda-prod kubectl -- get deployments,statefulsets,pvc -n tienda-prod
minikube -p tienda-dev kubectl -- get deployments,statefulsets,pvc -n tienda-dev
```

Esperado: tres nodos prod y uno dev Ready; web prod 2/2 como mínimo, dev 1/1; bases 1/1 y PVC Bound. Si HPA dejó más réplicas puede tardar en bajar. No recrear perfiles, Secrets, semillas ni PVC. El archivo de cifrado ya existe en este perfil: conservar el parámetro de arranque.

Abrir tres terminales y dejar cada una ocupada con su comando:

```powershell
# Terminal A: producción local
minikube -p tienda-prod kubectl -- port-forward -n kube-system service/traefik 9443:443 --address=127.0.0.1
```

```powershell
# Terminal B: desarrollo local
minikube -p tienda-dev kubectl -- port-forward -n kube-system service/traefik 9444:443 --address=127.0.0.1
```

```powershell
# Terminal C: Grafana local
minikube -p tienda-prod kubectl -- port-forward -n monitoreo service/monitoreo-grafana 3000:80 --address=127.0.0.1
```

En otra terminal:

```powershell
curl.exe --silent --show-error --noproxy '*' --ssl-revoke-best-effort --output NUL --write-out 'Prod HTTP %{http_code}\n' https://tskull-shirt.test:9443/health/ready
curl.exe --silent --show-error --noproxy '*' --ssl-revoke-best-effort --output NUL --write-out 'Dev HTTP %{http_code}\n' https://tienda-dev.test:9444/health/ready
```

Links: producción https://tskull-shirt.test:9443/ ; desarrollo https://tienda-dev.test:9444/ ; Grafana http://127.0.0.1:3000/ . Usar las cuentas de prueba que ya tienes; no proyectar contraseñas ni el contenido de Secrets. Un puerto ocupado suele ser otro port-forward abierto: cerrar el conocido, no matar procesos al azar. Si se reemplaza el Pod de Traefik o Grafana, reabrir su terminal de port-forward.

## 3. Si cambia la IP en la escuela

Para exponer desde la laptop no cambia nada: los dominios locales apuntan a 127.0.0.1. Conservar los mismos tres comandos y URLs. La IP de casa no forma parte de ese acceso. Las redes internas de Minikube son diferentes de la IP Wi-Fi de Windows. No cambiar APP_BASE_URL ni reconstruir los clústeres al cambiar de salón.

```powershell
Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } | Select-Object InterfaceAlias,@{Name='IPv4';Expression={$_.IPv4Address.IPAddress}}
```

El teléfono es opcional. No debe ser requisito para demostrar la tienda. En una red escolar puede haber aislamiento entre clientes o bloqueo de puertos, aunque todo funcione en la laptop. Un error de certificado o HTTP 404 no demuestra bloqueo del firewall.

Si quieres teléfono en una red nueva: hace falta actualizar la IP permitida por APP_LAN_HOST y la regla de entrada, emitir un certificado que incluya esa IP, actualizar el Secret TLS y la regla Windows; luego escuchar en esa IP además de loopback. El certificado público de la misma CA ya confiada en el teléfono puede reutilizar esa confianza; nunca copiar la clave privada de la CA. No usar `--insecure` como demostración de TLS válido. Ver el procedimiento opcional al final.

## 4. Orden recomendado frente al profesor: 15–20 minutos

1. Mostrar prod/dev, nodos y volúmenes (2 minutos).
2. Hacer una reserva y cancelación, mostrando stock antes/después; abrir historial (4 minutos).
3. Eliminar un Pod backend mientras corre la sonda; mostrar reemplazo (3 minutos).
4. Mostrar permisos/red y monitoreo (3 minutos).
5. Mostrar respaldo/restauración y sus evidencias; si hay tiempo/recursos, restaurar una copia nueva (5–8 minutos o más).
6. Explicar actualización/rollback y HPA; ejecutar solo si el docente lo pide.

La duración de una restauración depende de imágenes en caché y recursos. No ejecutar carga, restauración y mantenimiento de worker al mismo tiempo.

## 5. Prueba principal: reserva, cancelación, vencimiento e historial

Hacerla en DEV para no confundir pedidos entre ambientes. Anotar prenda, talla y stock inicial. Añadir una unidad al carrito: todavía no reserva. Crear pedido: stock disminuye una unidad. Cancelarlo: stock vuelve al inicial; refrescar no lo suma otra vez. Para pago, crear otro pedido y usar pago simulado; avanzar etapas con administrador. Cliente no debe entrar a administración ni a pedidos ajenos.

El historial se sincroniza por tarea. Para lanzarla ahora en dev:

```powershell
$jobHistorial = 'revision-historial-' + (Get-Date -Format 'yyyyMMddHHmmss')
minikube -p tienda-dev kubectl -- create job $jobHistorial --from=cronjob/sincronizar-historial -n tienda-dev
minikube -p tienda-dev kubectl -- wait --for=condition=complete job/$jobHistorial -n tienda-dev --timeout=180s
minikube -p tienda-dev kubectl -- logs job/$jobHistorial -n tienda-dev
```

Refrescar el pedido: eventos visibles. Repetir con otro nombre de Job no debe duplicar eventos. Cero eventos sincronizados también es correcto si el CronJob ya los procesó.

Vencimiento: esperar el plazo real de 30 minutos y la siguiente tarea, o adelantar deliberadamente solo un pedido DEV pendiente. Es una simulación explícita, no un fallo de reloj:

```powershell
[int]$pedidoRevision = Read-Host 'ID del pedido DEV pendiente que acabas de crear'
minikube -p tienda-dev kubectl -- exec postgres-0 -n tienda-dev -- psql -X -v ON_ERROR_STOP=1 -U tienda_owner -d tienda -c "UPDATE pedidos SET ped_vence_en=clock_timestamp()-interval '1 second' WHERE ped_id=$pedidoRevision AND ped_estado='pendiente_pago' RETURNING ped_id,ped_estado;"
$jobVencer = 'revision-vencer-' + (Get-Date -Format 'yyyyMMddHHmmss')
minikube -p tienda-dev kubectl -- create job $jobVencer --from=cronjob/vencer-pedidos -n tienda-dev
minikube -p tienda-dev kubectl -- wait --for=condition=complete job/$jobVencer -n tienda-dev --timeout=180s
minikube -p tienda-dev kubectl -- logs job/$jobVencer -n tienda-dev
```

Esperado: UPDATE 1, pedido Vencido y stock recuperado. UPDATE 0 significa ID/ambiente/estado equivocado o pedido ya procesado; no ejecutar un UPDATE sin condición para forzarlo.

## 6. Caída de un Pod y recuperación automática

Primero comprobar backend al menos 2/2 en prod. Esta prueba elimina un proceso reemplazable, no borra datos.

```powershell
minikube -p tienda-prod kubectl -- get deployment backend -n tienda-prod
minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend -o wide
```

En una terminal correr la sonda durante 90 segundos:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Sonda-Disponibilidad.ps1 -Segundos 90
```

Mientras está corriendo, en otra:

```powershell
$podRevision = minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend --field-selector=status.phase=Running -o 'jsonpath={.items[0].metadata.name}'
Write-Host "Se reemplazará un solo Pod: $podRevision"
if (-not $podRevision) { throw 'No hay Pod seleccionado' }
minikube -p tienda-prod kubectl -- delete pod $podRevision -n tienda-prod
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- get pods -n tienda-prod -l app=backend -o wide
```

Mostrar nuevo nombre/edad y réplicas listas. Esperado: el Deployment crea reemplazo y la otra réplica atiende. Registrar cualquier fallo real de la sonda; no garantizar cero errores para toda repetición. Histórico: 81 HTTP 200 y cero fallos en 90.3 s.

## 7. Persistencia: reemplazar una base en DEV

Hacer un motor a la vez. Habrá interrupción breve de esa dependencia porque tiene una instancia. No ejecutar en prod para esta demostración. El PVC debe conservarse.

```powershell
minikube -p tienda-dev kubectl -- exec postgres-0 -n tienda-dev -- psql -X -U tienda_owner -d tienda -c 'SELECT count(*) AS prendas FROM playeras; SELECT count(*) AS pedidos FROM pedidos;'
minikube -p tienda-dev kubectl -- get pvc -n tienda-dev
minikube -p tienda-dev kubectl -- delete pod postgres-0 -n tienda-dev
minikube -p tienda-dev kubectl -- rollout status statefulset/postgres -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- exec postgres-0 -n tienda-dev -- psql -X -U tienda_owner -d tienda -c 'SELECT count(*) AS prendas FROM playeras; SELECT count(*) AS pedidos FROM pedidos;'
```

Esperado: mismos conteos, mismo PVC y aplicación recuperada. Para Mongo, abrir primero un historial conocido y tomar evidencia de sus eventos; después:

```powershell
minikube -p tienda-dev kubectl -- delete pod mongo-0 -n tienda-dev
minikube -p tienda-dev kubectl -- rollout status statefulset/mongo -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- get pods,pvc -n tienda-dev
```

Refrescar el mismo historial: conserva eventos. Si cambian por una tarea concurrente, explicar la diferencia; no declarar pérdida solo porque el conteo aumentó.

## 8. Seguridad y observabilidad: comandos cortos

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Comprobar-Permisos.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Comprobar-Red.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Comprobar-TlsPostgres.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Comprobar-TlsMongo.ps1
```

Esperado: permisos coinciden; backend accede a bases y frontend no, con DNS funcionando; TLS valida CA/nombre y rechaza conexión insegura. El frontend no necesita consultar Mongo directamente.

```powershell
minikube -p tienda-prod kubectl -- auth can-i list pods -n tienda-prod --as=system:serviceaccount:tienda-prod:tienda-observador
minikube -p tienda-prod kubectl -- auth can-i get secrets -n tienda-prod --as=system:serviceaccount:tienda-prod:tienda-observador
minikube -p tienda-prod kubectl -- get prometheusrule -A
minikube -p tienda-prod kubectl -- get pods -n monitoreo
minikube -p tienda-prod kubectl -- top pods -n tienda-prod
```

Esperado: yes para listar Pods, no para Secrets. En Grafana abrir el panel “TSkull-Shirt — operacion” o explorar Prometheus con `pg_up` y `mongodb_up`: ambos deben dar 1. En Explore seleccionar “Logs TSkull-Shirt” y consultar `{namespace="tienda-prod"}`. El panel provisionado requiere su datasource; si no carga, revisar configuración y port-forward, no inventar datos.

Mostrar alertas sin provocar cinco incidentes: reglas de aplicación, nodo, 5xx, saturación y respaldo; sus condiciones se comprobaron con Promtool. En un ensayo real se activó/resolvió respaldo vencido; las alertas de ambas bases se probaron restringiendo exporters. Un exporter Running no basta: hay que ver `pg_up`/`mongodb_up`.

## 9. Respaldo y restauración aislada ante el profesor

No borrar prod para demostrar recuperación. Crear respaldo nuevo, seleccionar exactamente la carpeta que imprime y restaurar en un namespace nuevo. Mantener Docker/Minikube disponibles y espacio/RAM suficientes; si faltan, mostrar la evidencia guardada y explicar el límite.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Respaldar-Bases.ps1
$rutaRespaldo = Read-Host 'Pega la carpeta RESPALDO GUARDADO EN de esta ejecución'
Get-ChildItem -LiteralPath $rutaRespaldo
$relojRevision = [Diagnostics.Stopwatch]::StartNew()
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Restaurar-Prueba.ps1 -CarpetaRespaldo $rutaRespaldo
```

Esperado: SHA256 correctos, motores restaurados, índices/conteos/fotos verificados. Copiar el namespace nuevo impreso; no usar un namespace histórico por costumbre:

```powershell
$restoreNamespace = Read-Host 'Namespace tienda-restore-... de esta ejecución'
if ($restoreNamespace -notmatch '^tienda-restore-[0-9]{8}-[0-9]{6}$') { throw 'Namespace no válido' }
minikube -p tienda-prod kubectl -- get pods,pvc -n $restoreNamespace
minikube -p tienda-prod kubectl -- label pod postgres-restore app=postgres-restore -n $restoreNamespace --overwrite
minikube -p tienda-prod kubectl -- label pod mongo-restore app=mongo-restore -n $restoreNamespace --overwrite
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/restauracion/aplicacion-recuperada.yaml -n $restoreNamespace
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n $restoreNamespace --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n $restoreNamespace --timeout=300s
minikube -p tienda-prod kubectl -- port-forward service/frontend 9081:8080 -n $restoreNamespace --address=127.0.0.1
```

Abrir http://127.0.0.1:9081/ . Entrar con cuenta contenida en ese respaldo. Revisar catálogo, imagen conocida e historial del pedido que tenía eventos. Esta copia usa HTTP solo local; la tienda productiva conserva HTTPS. En la terminal donde quedó el reloj, tras verificar la función:

```powershell
$relojRevision.Stop()
Write-Host "Tiempo hasta validación funcional: $([math]::Round($relojRevision.Elapsed.TotalSeconds,1)) segundos"
```

Medir desde antes de restaurar hasta terminar la validación, no solo el último comando. Histórico final: 391.9 s hasta catálogo autenticado e historial; respaldo con antigüedad 7.76 s al inicio del ensayo. No garantiza RPO de 24 h con laptop apagada. No eliminar namespaces ni PVC al terminar la presentación sin revisar exactamente qué copia son.

Respaldo cifrado/programación, si lo pide:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Respaldo-Programado.ps1
Get-ScheduledTask -TaskName TiendaProd-RespaldoDiario | Select-Object TaskName,State
Get-ScheduledTaskInfo -TaskName TiendaProd-RespaldoDiario | Select-Object LastRunTime,LastTaskResult,NextRunTime
```

Un respaldo manual no demuestra ejecución automática diaria; LastTaskResult 0 indica éxito de la última ejecución. DPAPI requiere este usuario de Windows; no es recuperación portable ante pérdida total de la laptop.

## 10. HPA y escalamiento manual, si lo pide

Para una revisión breve, mostrar configuración y evidencia histórica de 2→4→6 y descenso. No hace falta repetir un benchmark. El crecimiento depende de carga y capacidad; el descenso espera una ventana de estabilización.

```powershell
minikube -p tienda-prod kubectl -- get hpa -n tienda-prod
minikube -p tienda-prod kubectl -- describe hpa backend -n tienda-prod
```

Para generar carga opcional durante seis minutos, en dos terminales:

```powershell
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/carga-hpa.yaml
minikube -p tienda-prod kubectl -- logs -f job/carga-hpa -n tienda-prod --pod-running-timeout=120s
```

```powershell
minikube -p tienda-prod kubectl -- get hpa -n tienda-prod --watch
```

Al terminar, Ctrl+C en watch y retirar solo la carga:

```powershell
minikube -p tienda-prod kubectl -- delete -f k8s/minikube/carga-hpa.yaml
minikube -p tienda-prod kubectl -- get hpa -n tienda-prod
```

El Job usa la imagen histórica frontend:0.1.0; debe estar cargada para repetir exactamente ese manifiesto. Si no está, no improvisar durante la revisión: usar la evidencia o preparar previamente una revisión del manifiesto con imagen disponible. Nunca borrar el HPA para “hacerlo crecer”. No afirmar una mejora de latencia por HPA a partir de la sola cantidad de réplicas.

En DEV el escalamiento manual breve es:

```powershell
minikube -p tienda-dev kubectl -- scale deployment/backend --replicas=2 -n tienda-dev
minikube -p tienda-dev kubectl -- rollout status deployment/backend -n tienda-dev --timeout=180s
minikube -p tienda-dev kubectl -- get deployment backend -n tienda-dev
minikube -p tienda-dev kubectl -- scale deployment/backend --replicas=1 -n tienda-dev
minikube -p tienda-dev kubectl -- rollout status deployment/backend -n tienda-dev --timeout=180s
```

## 11. Actualización y rollback, sin perder datos

Antes de cambiar nada, guardar la revisión actual en esa misma terminal. El historial de esta laptop debe contener una versión anterior conocida y compatible. No volver automáticamente a la revisión 6 histórica.

```powershell
$revisionBuena = (minikube -p tienda-prod kubectl -- get deployment backend -n tienda-prod -o json | ConvertFrom-Json).metadata.annotations.'deployment.kubernetes.io/revision'
Write-Host "Revisión buena: $revisionBuena"
minikube -p tienda-prod kubectl -- rollout history deployment/backend -n tienda-prod
minikube -p tienda-prod kubectl -- get deployment backend -n tienda-prod -o 'custom-columns=NOMBRE:.metadata.name,IMAGEN:.spec.template.spec.containers[0].image,LISTAS:.status.readyReplicas'
```

Para demostrar el mecanismo sin introducir una versión antigua insegura, reiniciar el Deployment con su misma imagen:

```powershell
minikube -p tienda-prod kubectl -- rollout restart deployment/backend -n tienda-prod
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout undo deployment/backend -n tienda-prod --to-revision=$revisionBuena
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
```

Esto demuestra revisión/reversión de plantilla, no cambio de versión de código. La actualización real 0.1.1→0.1.2 y rollback ya tiene evidencia histórica. Git guarda las fuentes; Docker construye; cargar/aplicar despliega. Rollback no deshace migraciones ni datos. Si se cambia imagen de verdad, actualizar también preparar-fotos y los CronJobs y alinear los manifiestos después.

## 12. Worker: mostrar evidencia o hacer mantenimiento preparado

El ensayo completo ya se registró con cero fallos, pero es más costoso y puede mover monitoreo. No ejecutar a ciegas durante la presentación: revisar Pods del nodo, PDB, volúmenes locales y Pods sueltos de restauración. Un drain que se niega está protegiendo cargas: no añadir --force ni desactivar PDB para obtener una captura.

```powershell
minikube -p tienda-prod kubectl -- get pods -A --field-selector=spec.nodeName=tienda-prod-m03 -o wide
minikube -p tienda-prod kubectl -- get pdb -A
```

Solo tras preparar las cargas y una entrada de sonda estable en otro nodo:

```powershell
minikube -p tienda-prod kubectl -- cordon tienda-prod-m03
minikube -p tienda-prod kubectl -- drain tienda-prod-m03 --ignore-daemonsets --delete-emptydir-data --timeout=180s
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n tienda-prod --timeout=300s
# Ejecutar también si drain falla, para no dejar el nodo cerrado:
minikube -p tienda-prod kubectl -- uncordon tienda-prod-m03
minikube -p tienda-prod kubectl -- get nodes
```

--delete-emptydir-data elimina almacenamiento temporal de Pods evacuados, no PVC. Los motores con discos locales pueden impedir reubicación; no es prueba de HA de bases. Si Traefik del port-forward se reemplaza, el túnel local se corta aunque otro Traefik siga sano: reabrirlo y distinguir túnel de servicio. Para la revisión corta, preferir la caída de un Pod del apartado 6.

## 13. Apagar y lista de cada día

Antes de apagar: terminar Jobs de carga propios, dejar nodos sin cordon y cerrar con Ctrl+C cada port-forward conocido. Luego:

```powershell
minikube stop -p tienda-dev
minikube stop -p tienda-prod
```

Cerrar Docker Desktop desde su menú y apagar Windows. No usar minikube delete ni borrar PVC. Si una tarea de respaldo vence mientras está apagada, no puede respaldar: comprobarla y ejecutar un respaldo al volver si corresponde.

Al encender: Docker listo → arrancar perfiles con parámetro de cifrado → nodos/cargas/PVC sanos → abrir tres túneles → comprobar HTTP 200 → entrar a la tienda. Al cambiar de red, mantener acceso local y revisar LAN solo si realmente se utilizará el teléfono.

## 14. Respuestas rápidas para el maestro

| Pregunta | Respuesta |
| --- | --- |
| ¿Qué pasa si cae un backend? | El Service usa los preparados y el Deployment repone el que falta. Hay otra réplica. |
| ¿Qué pasa si cae PostgreSQL? | Se afecta la compra; su Pod se repone con el PVC. Tiene una instancia, por lo que no prometo continuidad del motor. |
| ¿Qué pasa si falla el historial? | El pedido y evento quedan en SQL; el sincronizador reintenta Mongo sin duplicar. |
| ¿Por qué dos bases? | SQL para relaciones/transacciones de stock; Mongo para eventos consultables del historial. |
| ¿PVC equivale a backup? | No. Un borrado/corrupción puede persistir; el backup permite recuperar una copia anterior. |
| ¿Prod es Internet? | No: es el ambiente de producción de laboratorio, local o LAN autorizada. |
| ¿Hay tres computadoras? | No: tres nodos contenedorizados en una laptop. |
| ¿Qué parte no se reconstruyó limpia? | La instalación completa desde otro clon en clústeres nuevos por falta de recursos; el profesor autorizó documentar esa limitación. |
| ¿Está libre de vulnerabilidades? | No. Se redujeron hallazgos, pero los escaneos conservan riesgos por corregir. |

## 15. Teléfono opcional cuando cambia la red

Preparar antes de la revisión, no mientras corre una prueba de fallo. Conserva la tienda local por nombre. Este bloque modifica únicamente el acceso LAN de prod; dev/Grafana siguen privados. Requiere mkcert de la misma CA, PowerShell administrador para firewall y la red autorizada. Si hay varias interfaces, seleccionar la IPv4 correcta explícitamente.

```powershell
$ipLAN = Read-Host 'IPv4 actual de Windows en la red del teléfono'
$ipValidada = $null
if (-not [Net.IPAddress]::TryParse($ipLAN,[ref]$ipValidada) -or $ipValidada.AddressFamily -ne [Net.Sockets.AddressFamily]::InterNetwork) { throw 'IPv4 no válida' }
New-Item -ItemType Directory -Force k8s/tls/lan-dinamica | Out-Null
mkcert -cert-file k8s/tls/lan-dinamica/tienda.crt -key-file k8s/tls/lan-dinamica/tienda.key tskull-shirt.test tienda-prod.test $ipLAN
minikube -p tienda-prod kubectl -- create secret tls tienda-tls -n tienda-prod --cert=k8s/tls/lan-dinamica/tienda.crt --key=k8s/tls/lan-dinamica/tienda.key --dry-run=client -o yaml | minikube -p tienda-prod kubectl -- apply -f -
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/entrada-lan-tls.yaml
minikube -p tienda-prod kubectl -- set env deployment/backend "APP_LAN_HOST=${ipLAN}:9443" -n tienda-prod
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
```

La ruta por IP también debe existir. Crear/actualizar un Ingress específico de laboratorio, con host omitido y misma entrada TLS:

```powershell
$entradaLAN = @{
  apiVersion='networking.k8s.io/v1'; kind='Ingress'
  metadata=@{name='tienda-lan-revision';namespace='tienda-prod';annotations=@{'traefik.ingress.kubernetes.io/router.entrypoints'='websecure';'traefik.ingress.kubernetes.io/router.tls'='true'}}
  spec=@{ingressClassName='traefik';rules=@(@{http=@{paths=@(@{path='/';pathType='Prefix';backend=@{service=@{name='frontend';port=@{number=8080}}}})}})}
}
$entradaLAN | ConvertTo-Json -Depth 15 | minikube -p tienda-prod kubectl -- apply -f -
```

En PowerShell administrador, obtener puertos Docker actuales y actualizar firewall con el script de Windows:

```powershell
$puertosLAN = @(3000,9081,9090,9444)
docker inspect tienda-prod tienda-prod-m02 tienda-prod-m03 --format '{{json .NetworkSettings.Ports}}' | ForEach-Object {
  $mapaLAN = $_ | ConvertFrom-Json
  foreach ($entrada in $mapaLAN.PSObject.Properties) {
    foreach ($conexion in $entrada.Value) { if ($conexion.HostPort) { $puertosLAN += [int]$conexion.HostPort } }
  }
}
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\minikube\Habilitar-ProdLAN.ps1 -PuertosProtegidos (($puertosLAN | Sort-Object -Unique) -join ',')
```

Cerrar primero el túnel prod 9443 anterior con Ctrl+C. En la terminal que mantiene $ipLAN:

```powershell
minikube -p tienda-prod kubectl -- port-forward -n kube-system service/traefik 9443:443 "--address=127.0.0.1,$ipLAN"
```

Teléfono, mismo Wi-Fi y sin VPN/datos móviles: abrir `https://IP_ACTUAL:9443/`. Debe confiar en la CA pública del laboratorio. Dev en `https://IP_ACTUAL:9444/` no debe responder; su túnel sigue solo en loopback. Si la escuela aísla clientes, usar la laptop; no desactivar firewall ni modificar la infraestructura escolar. Los cambios LAN hechos con set env deben incorporarse al manifiesto si se quieren conservar después de aplicar otra configuración. No compartir rootCA-key.pem ni subir k8s/tls a Git.
