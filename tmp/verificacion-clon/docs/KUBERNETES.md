# Tienda de ropa alternativa

Actualización del 24/10/2026: el laboratorio usa Docker Desktop + kind sobre una sola laptop. Para crear los clústeres sigue primero [INICIO_KIND.md](INICIO_KIND.md). Esta guía corresponde a las etapas posteriores de aplicación y datos; no debe ejecutarse durante el arranque inicial. Se descarta el procedimiento anterior con K3s y cuatro máquinas virtuales.

La aplicación conserva su catálogo, carrito, usuarios, tallas, colores y ventas. La compra ahora crea una reserva de 30 minutos. El cliente registra un pago simulado y el sistema confirma el pedido sin aprobación del administrador. Al terminar el plazo, el pedido vence y se libera la reserva. El administrador prepara y entrega pedidos confirmados.

Las ediciones limitadas conservan el máximo de dos unidades por cliente, acumulado entre tallas y pedidos vigentes o entregados. Se identifican desde el formulario de productos. No requieren aprobación administrativa para comprarlas.

## Archivos principales

- `application/services/TiendaService.php`: transacciones, bloqueo de existencias, límites, vencimiento, pago, cancelación y estados.
- `application/controllers/Pedidos.php` y `application/views/pedidos.php`: consulta, pago simulado, cuenta regresiva e historial.
- `application/services/TiendaHistory.php`: lectura de MongoDB y sincronización outbox idempotente.
- `database/migrations`: esquema inicial y migraciones incrementales sin borrar tablas existentes.
- `bin/tienda.php`: tareas de migración, semilla, vencimiento y sincronización.
- `docker`: imágenes de Nginx y PHP-FPM y creación de cuentas para los motores.
- `k8s/overlays`: configuración independiente para desarrollo y producción de laboratorio.

## Reglas y consistencia

`pta_stock` representa unidades disponibles. El carrito no las reserva. Crear el pedido descuenta la reserva; pagar no vuelve a descontarla. Cancelar o vencer devuelve las unidades una sola vez. Los pagos fuera de plazo se rechazan aunque el proceso programado aún no haya ejecutado la liberación. La cuenta regresiva es informativa: la decisión usa el reloj de PostgreSQL.

Los precios, nombres y tallas se copian al detalle al comprar. Los importes se calculan con precios de servidor y cantidades enteras, sin confiar en totales del navegador. El máximo de dos unidades se serializa por usuario y las existencias se bloquean por variante. Una clave de compra evita repetir pedidos ante reintentos.

PostgreSQL es la fuente de verdad. Cada cambio y su evento se guardan en una sola transacción. MongoDB contiene el historial visible. Si falla, la compra sigue guardada, la interfaz indica sincronización pendiente y el evento se reintenta. El índice único de `evento_id` y el upsert evitan duplicados si un proceso termina después de escribir en MongoDB y antes de marcar el envío en PostgreSQL.

Flujo: `pendiente_pago → confirmado → en_preparacion → listo_entrega → entregado`. Desde pendiente también se permite `cancelado` o `vencido`. El carrito es anterior al pedido. Los pedidos antiguos `pagado` se migran a `confirmado`; otros estados históricos se conservan.

## Instalación local existente

La configuración anterior de PostgreSQL se conserva en `application/config/database.local.php`, ignorado por Git y por Docker. Las variables de entorno tienen prioridad. Las sesiones se guardan en `ci_sessions`, compartida por todas las réplicas. La instalación usa CodeIgniter 3.1.13 desde `vendor`; `system` se conserva como referencia del proyecto original. PHP objetivo de las imágenes: 8.3.

Desde la carpeta del proyecto, con PostgreSQL disponible:

```powershell
php bin/backup-local.php
php bin/tienda.php migrate
php tests/domain.php
php tests/http.php
```

Las pruebas crean y eliminan únicamente esquemas de nombre aleatorio `tienda_test_*` y `tienda_http_*` en la base configurada. Requieren permiso para crear esquemas. El servidor HTTP temporal usa el puerto 8097 y cuentas ficticias. No ejecutar la semilla sobre datos personales para reemplazarlos.

La instalación Laragon usa por defecto `http://localhost/sistema/`. Para otro dominio configura `APP_BASE_URL`. En local, mientras no se use Docker, se requiere la extensión PHP `mongodb` para consultar y sincronizar el historial. Sin ella, los pedidos funcionan y muestran el historial temporalmente no disponible. Ejecutar `php bin/tienda.php expire` periódicamente es necesario para liberar reservas sin visitas a la tienda.

Los respaldos locales y archivos privados están bloqueados por `.htaccess`; Apache debe tener `mod_rewrite` y permitir las reglas del proyecto. Nginx incorpora su bloqueo equivalente. Los archivos de imágenes ya cargados en `uploads` deben copiarse al volumen persistente si se migra el catálogo existente; no se incorporan a la imagen del contenedor.

## Prueba con Docker Compose

1. Iniciar Docker Desktop y verificar `docker version`.
2. Copiar `.env.example` a `.env`. Completar las contraseñas de aplicación y además `DB_OWNER_PASSWORD`, `MONGO_OWNER_PASSWORD`, `MONGO_APP_PASSWORD`, `SEED_ADMIN_PASSWORD` y `SEED_CLIENT_PASSWORD`. Usar contraseñas aleatorias distintas; codificar caracteres especiales en las URI de MongoDB. Definir también `MONGO_OWNER_URI` con el usuario `tienda_owner` y `authSource=admin`.
3. Construir y levantar los motores:

```powershell
docker compose build
docker compose up -d postgres mongo
```

4. Preparar los datos con la cuenta de migración, sin ejecutar estas tareas en cada réplica:

```powershell
docker compose run --rm -e DB_USERNAME=tienda_owner -e DB_PASSWORD=$env:DB_OWNER_PASSWORD backend php bin/tienda.php migrate
docker compose run --rm -e DB_USERNAME=tienda_owner -e DB_PASSWORD=$env:DB_OWNER_PASSWORD -e SEED_ADMIN_PASSWORD=$env:SEED_ADMIN_PASSWORD -e SEED_CLIENT_PASSWORD=$env:SEED_CLIENT_PASSWORD backend php bin/tienda.php seed
docker compose run --rm -e DB_USERNAME=tienda_owner -e DB_PASSWORD=$env:DB_OWNER_PASSWORD backend php bin/tienda.php grant
docker compose run --rm -e MONGO_URI=$env:MONGO_OWNER_URI backend php bin/tienda.php mongo-init
docker compose up -d backend frontend worker
```

Las variables usadas con `$env:` deben estar cargadas también en PowerShell; Compose lee `.env` para su configuración, pero no exporta sus valores al shell. No copiar contraseñas reales a estos comandos ni al repositorio.

Abrir `http://localhost:8080/`. Las cuentas de semilla son `admin@tienda.test` y `cliente@tienda.test`, con las contraseñas elegidas. La semilla crea 100 prendas y 500 variantes; repetirla no restablece existencias. Los motores no publican puertos al host. El worker revisa vencimientos y eventos cada 15 segundos.

## Preparar Kubernetes

Se necesitan dos clústeres independientes, no solo dos namespaces. Los archivos no crean máquinas ni instalan el clúster. Desarrollo requiere al menos un nodo; producción un control plane y dos workers. Verificar sus contextos explícitamente antes de aplicar recursos.

Prerrequisitos por clúster: un CNI que aplique NetworkPolicy, DNS, StorageClass persistente, Traefik como controlador de Ingress en `kube-system`, mapeo local de puertos de kind hacia el NodePort 30443 de Traefik y certificado TLS local válido. La política de entrada espera la etiqueta `app.kubernetes.io/name=traefik`; ajustarla si la instalación usa otra etiqueta o namespace. Producción requiere Metrics Server y una StorageClass `shared-rwx` que proporcione volúmenes compartidos reales para las imágenes de prendas. No basta cambiar el nombre de una clase RWO.

Construir las imágenes y cargarlas en cada clúster o publicarlas en un registro accesible. Antes de producción sustituir las etiquetas por digests verificados. Kustomize permite configurar `images` para apuntar al registro elegido.

```powershell
docker build -f docker/php/Dockerfile --target backend -t tienda-backend:0.1.0 .
docker build -f docker/php/Dockerfile --target frontend -t tienda-frontend:0.1.0 .
kubectl kustomize k8s/overlays/development
kubectl kustomize k8s/overlays/production
```

## Desplegar desarrollo

Sustituir `CONTEXTO_DESARROLLO` por el contexto real. Crear `k8s/secrets.env` a partir de su ejemplo con valores propios del ambiente. Los dos ambientes no deben compartir este archivo ni credenciales. Crear los secretos antes de que las cargas intenten arrancar; se puede aplicar primero únicamente el namespace.

```powershell
kubectl --context CONTEXTO_DESARROLLO apply -f k8s/overlays/development/namespace.yaml
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev create secret generic tienda-secrets --from-env-file=k8s/secrets.env
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev create secret tls tienda-tls --cert=k8s/tls/dev.crt --key=k8s/tls/dev.key
kubectl --context CONTEXTO_DESARROLLO apply -k k8s/overlays/development
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev rollout status statefulset/postgres
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev rollout status statefulset/mongo
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev apply -f k8s/migration-job.yaml
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev wait --for=condition=complete job/tienda-migrate --timeout=180s
kubectl --context CONTEXTO_DESARROLLO -n tienda-dev get pods,pvc,ingress,cronjobs
```

Configurar `tienda-dev.test` hacia 127.0.0.1 y confiar en su CA; el acceso previsto es https://tienda-dev.test:8443/. Instalar Traefik como NodePort 30443 antes de esperar acceso web. Verificar login, compra, pago y vencimiento. El cron de liberación se ejecuta cada minuto: el stock puede tardar hasta el siguiente ciclo en reaparecer en el catálogo. Pagar después del instante exacto de vencimiento siempre se rechaza.

## Producción de laboratorio

Repetir el procedimiento sobre el otro contexto, usando namespace `tienda-prod`, certificados de `tienda-prod.test` y acceso https://tienda-prod.test:9443/, secretos distintos y `k8s/overlays/production`. El mismo Job se aplica con `-n tienda-prod`. La superposición configura dos réplicas iniciales por capa, distribución entre nodos, presupuesto de interrupciones y HPA del backend de 2 a 6. Las bases mantienen una instancia: no se afirma alta disponibilidad de los motores. Los PVC y las credenciales están separados por clúster.

Esta configuración es una base para continuar la práctica, no una certificación de todos sus requisitos. Antes de presentar producción deben completarse y medirse: TLS y verificación de identidad hacia ambos motores, cifrado de secretos en reposo, cuentas RBAC de operadores, cuotas, límites de intentos de login, métricas y logs centralizados, paneles y alertas, backups programados cifrados, restauración aislada con RPO/RTO, pruebas de carga y fallos, revisión de vulnerabilidades, promoción por digest y rollback. El manifiesto actual usa conexiones internas a las bases sin TLS obligatorio, por lo que aún no satisface ese punto de producción. Las bibliotecas heredadas de exportación Excel/PDF requieren revisar compatibilidad y seguridad antes de usarse en producción.

## Validaciones realizadas y pendientes

Se verifican las reglas con PostgreSQL real y el flujo HTTP con CodeIgniter actualizado. `kubectl kustomize` valida que ambas superposiciones se puedan componer; no verifica disponibilidad de las imágenes, CSI, admisión ni conectividad del clúster. La construcción de imágenes, el arranque de los motores en contenedores y la recuperación real de MongoDB deben probarse cuando Docker esté disponible. No se atribuyen resultados de despliegue a manifiestos que no se han aplicado.

Referencias técnicas: [CronJobs de Kubernetes](https://kubernetes.io/docs/concepts/workloads/controllers/cron-jobs/), [sesiones de CodeIgniter](https://codeigniter.com/userguide3/libraries/sessions.html), [upsert con el driver PHP de MongoDB](https://www.php.net/manual/en/mongodb-driver-bulkwrite.update.php).
