# TSkull-Shirt — instalación y operación con Minikube

Tienda de ropa alternativa en PHP/CodeIgniter, Nginx, PostgreSQL y MongoDB.
Permite consultar prendas por talla, reservar inventario, simular un pago,
cancelar o vencer pedidos y consultar su historial. PostgreSQL es la fuente
de verdad; una outbox sincroniza eventos hacia MongoDB sin duplicarlos.

Este README es el manual principal para **clonar, construir y levantar los
dos ambientes en otra computadora**. Los comandos son para Windows 11,
Windows PowerShell 5.1 y Docker Desktop con WSL 2 y contenedores Linux.
Ejecutarlos desde la raíz del clon, en el orden indicado; comprobar cada fase
antes de continuar. No se necesita Laragon, XAMPP ni PHP instalado en el host.

El laboratorio existente sí fue probado; la ejecución integral de este manual
desde un clon y clústeres completamente nuevos todavía no se ha ensayado (E14).
Las verificaciones estáticas del repositorio no sustituyen esa prueba.
No ejecutar las fases de instalación inicial sobre perfiles que ya tengan datos.
Para perfiles existentes ir a [encendido y apagado](#12-encendido-y-apagado).

## 1. Requisitos y versiones de referencia

| Componente | Versión usada / función |
| --- | --- |
| Windows 11 + WSL 2 | Virtualización habilitada; Linux containers |
| Docker Desktop | Motor Docker 29.0.1 en el laboratorio |
| Git | Clonar y versionar |
| Minikube | 1.39.0; Kubernetes v1.37.0; containerd |
| CNI | Calico; 3.32.2 observado |
| Helm | Instalar los charts con las versiones fijadas abajo |
| mkcert | CA y certificados propios de la computadora |
| Backend / frontend | `tienda-backend:0.1.6` / `tienda-frontend:0.1.1` |
| PostgreSQL / MongoDB | 17.6-bookworm / 8.0.14 |
| Traefik | Chart 42.1.3 |
| Monitoreo | kube-prometheus-stack 92.13.1 |
| CSI NFS | v4.13.0, manifiestos incluidos |

Instalar Git, Docker Desktop, Minikube, Helm y mkcert desde sus distribuciones
oficiales y agregarlos a PATH. Abrir Docker Desktop y esperar a que su motor
esté listo. No activar el Kubernetes adicional de Docker Desktop: se usarán
los perfiles de Minikube. Reservar al menos 12 GiB para Docker/WSL y espacio
libre suficiente para imágenes, cuatro nodos y volúmenes; se recomienda un
host con 24 GiB RAM. Si falta capacidad, no reducir producción a un único nodo
y afirmar que conserva la topología de la práctica.

```powershell
git --version
docker info
minikube version
helm version --short
mkcert -version
```

Se espera Docker accesible y las herramientas encontradas. Si Docker no
responde, corregir WSL/motor antes de crear perfiles. Las etiquetas de imágenes
base del Dockerfile no están fijadas por digest: reconstruir después puede
incorporar parches distintos; guardar el digest de cada imagen de entrega.

## 2. Clonar y construir las imágenes

```powershell
git clone https://github.com/MakiMike/solucionestai_prueba.git
Set-Location solucionestai_prueba
git switch dev
docker build --target backend -f docker/php/Dockerfile -t tienda-backend:0.1.6 .
docker build --target frontend -f docker/php/Dockerfile -t tienda-frontend:0.1.1 .
docker run --rm --network none --read-only tienda-backend:0.1.6 php -v
docker run --rm --network none --read-only tienda-backend:0.1.6 php -m
```

El Dockerfile contiene ambos targets. Instala Composer desde `composer.lock`
y aplica la compatibilidad de CodeIgniter con PHP 8.3. No copiar `vendor/`
desde otra máquina. Esperado: construcción exitosa y listado de módulos con
pdo_pgsql, pgsql, mongodb, mbstring, gd y zip.
El catálogo inicial tiene 100 prendas ficticias, cinco tallas y 500 variantes;
las fotos nuevas se cargan desde la aplicación y se guardan en el PVC.

## 3. Crear los dos clústeres nuevos

```powershell
minikube start -p tienda-dev --driver=docker --container-runtime=containerd --nodes=1 --cni=calico --cpus=2 --memory=3072 --kubernetes-version=v1.37.0
minikube start -p tienda-prod --driver=docker --container-runtime=containerd --nodes=3 --cni=calico --cpus=2 --memory=3072 --kubernetes-version=v1.37.0
minikube -p tienda-dev addons enable metrics-server
minikube -p tienda-dev addons enable csi-hostpath-driver
minikube -p tienda-prod addons enable metrics-server
minikube -p tienda-prod addons enable csi-hostpath-driver
minikube -p tienda-dev image load tienda-backend:0.1.6 tienda-frontend:0.1.1
minikube -p tienda-prod image load tienda-backend:0.1.6 tienda-frontend:0.1.1
minikube -p tienda-dev kubectl -- get nodes
minikube -p tienda-prod kubectl -- get nodes
```

Esperado: un nodo Ready en dev y tres en prod. Los perfiles generan contextos,
redes y datos distintos. Usar siempre `-p` y `-n`: no depender del contexto activo.
El aviso de `volumesnapshots` del addon no impide usar PVC; snapshots no forman
parte de este procedimiento. Si algún nodo está NotReady, revisar Pods de
`kube-system` y eventos; no borrar perfiles o PVC para resolverlo.

## 4. Desarrollo: configuración, datos y aplicación

```powershell
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/plataforma.yaml
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/desarrollo/Preparar-Credenciales.ps1
minikube -p tienda-dev kubectl -- create secret generic tienda-secrets -n tienda-dev --from-env-file=k8s/tls/dev-private/secrets.env
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/bases-datos.yaml
minikube -p tienda-dev kubectl -- rollout status statefulset/postgres -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- rollout status statefulset/mongo -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/inicializar-tienda.yaml
minikube -p tienda-dev kubectl -- wait --for=condition=complete job/tienda-migrate -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- logs job/tienda-migrate -n tienda-dev
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/aplicacion.yaml -f k8s/minikube/desarrollo/tareas-programadas.yaml
minikube -p tienda-dev kubectl -- rollout status deployment/backend -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- rollout status deployment/frontend -n tienda-dev --timeout=300s
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/verificar-bases.yaml
minikube -p tienda-dev kubectl -- wait --for=condition=complete job/verificar-bases -n tienda-dev --timeout=180s
minikube -p tienda-dev kubectl -- logs job/verificar-bases -n tienda-dev
```

Esperado: migraciones 001–004, semilla e índices preparados; verificación con
100 prendas, cinco tallas, 500 variantes y dos usuarios. El generador crea
claves propias y conserva las existentes; no es un procedimiento de rotación.
No se publican esas claves. Para iniciar sesión usar `admin@tienda.test` o
`cliente@tienda.test` y consultar **privadamente** el valor correspondiente
`SEED_ADMIN_PASSWORD` / `SEED_CLIENT_PASSWORD` del archivo generado.

El Job se aplica una vez en un ambiente vacío. No borrar un Job completado
para resembrar datos existentes. Si falla, revisar sus logs antes de reintentar.
Desarrollo usa un backend, un frontend y almacenamiento RWO en un solo nodo.

## 5. Producción: plataforma y datos iniciales

```powershell
minikube -p tienda-prod kubectl -- create namespace tienda-prod
minikube -p tienda-prod kubectl -- label namespace tienda-prod pod-security.kubernetes.io/enforce=restricted pod-security.kubernetes.io/audit=restricted pod-security.kubernetes.io/warn=restricted
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/configuracion.yaml -f k8s/minikube/seguridad-red.yaml -f k8s/minikube/permisos-operacion.yaml -f k8s/minikube/cuotas-recursos.yaml
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Preparar-Credenciales.ps1
minikube -p tienda-prod kubectl -- create secret generic tienda-secrets -n tienda-prod --from-env-file=k8s/tls/prod-private/secrets.env
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/csi-nfs-v4.13.0/
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/servidor-nfs.yaml
minikube -p tienda-prod kubectl -- rollout status deployment/nfs-server -n tienda-storage --timeout=300s
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/volumen-fotos.yaml
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/bases-datos.yaml
minikube -p tienda-prod kubectl -- rollout status statefulset/postgres -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status statefulset/mongo -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/inicializar-tienda.yaml
minikube -p tienda-prod kubectl -- wait --for=condition=complete job/tienda-migrate -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- logs job/tienda-migrate -n tienda-prod
minikube -p tienda-prod kubectl -- get pvc -n tienda-prod
```

Esperado: bases 1/1, Job completado y PVC Bound. `inicializar-tienda.yaml`
ejecuta migraciones, semilla, permisos SQL e inicialización Mongo: no hace falta
volver a aplicar `inicializar-mongo.yaml`. El namespace `tienda-storage` tiene
una excepción privilegiada para el servidor NFS; la aplicación permanece Restricted.
Los datos de producción no se copian desde desarrollo. El inicio de los motores
se realiza con credenciales, dentro de la red privada, antes de exigir TLS.
**No publicar todavía el puerto web.**

## 6. TLS de los motores antes de desplegar clientes de producción

Este orden evita depender de un backend o exporter que todavía no existe.
Los scripts `Habilitar-Tls*.ps1` sirven para actualizar una instalación con
clientes existentes; para una instalación nueva usar los pasos explícitos aquí.

Preparar la CA en la computadora y los certificados de servidor:

```powershell
mkcert -install
New-Item -ItemType Directory -Force k8s/tls | Out-Null
mkcert -cert-file k8s/tls/postgres.crt -key-file k8s/tls/postgres.key postgres postgres.tienda-prod postgres.tienda-prod.svc postgres.tienda-prod.svc.cluster.local
mkcert -cert-file k8s/tls/mongo.crt -key-file k8s/tls/mongo.key mongo mongo.tienda-prod mongo.tienda-prod.svc mongo.tienda-prod.svc.cluster.local localhost 127.0.0.1
$ca = Join-Path ((mkcert -CAROOT | Out-String).Trim()) 'rootCA.pem'
$cert = [IO.File]::ReadAllText((Join-Path $PWD 'k8s/tls/mongo.crt')).Trim()
$key = [IO.File]::ReadAllText((Join-Path $PWD 'k8s/tls/mongo.key')).Trim()
[IO.File]::WriteAllText((Join-Path $PWD 'k8s/tls/mongo-server.pem'), $cert + "`n" + $key + "`n", (New-Object Text.UTF8Encoding($false)))
minikube -p tienda-prod kubectl -- create secret tls postgres-tls -n tienda-prod --cert=k8s/tls/postgres.crt --key=k8s/tls/postgres.key
minikube -p tienda-prod kubectl -- create configmap postgres-ca -n tienda-prod --from-file="ca.crt=$ca"
minikube -p tienda-prod kubectl -- create configmap postgres-tls-config -n tienda-prod --from-file=pg_hba.conf=k8s/minikube/postgres-pg_hba.conf
minikube -p tienda-prod kubectl -- create secret generic mongo-tls -n tienda-prod --from-file=server.pem=k8s/tls/mongo-server.pem
minikube -p tienda-prod kubectl -- create configmap mongo-ca -n tienda-prod --from-file="ca.crt=$ca"
minikube -p tienda-prod kubectl -- patch statefulset postgres -n tienda-prod --type=strategic --patch-file k8s/minikube/postgres-tls.patch.yaml
minikube -p tienda-prod kubectl -- patch statefulset mongo -n tienda-prod --type=strategic --patch-file k8s/minikube/mongo-tls.patch.yaml
minikube -p tienda-prod kubectl -- rollout status statefulset/postgres -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status statefulset/mongo -n tienda-prod --timeout=300s
```

Actualizar las dos URI locales de producción para exigir TLS y verificar CA y
nombre. Este bloque local solo se ejecuta una vez en esta instalación inicial:

```powershell
$envFile = Join-Path $PWD 'k8s/tls/prod-private/secrets.env'
$lines = [IO.File]::ReadAllLines($envFile) | ForEach-Object {
    if ($_ -match '^(MONGO_URI|MONGO_OWNER_URI)=') {
        if ($_ -match '[?&]tls=') { throw 'Las URI ya tienen TLS; no duplicar parametros.' }
        $_ + '&tls=true&tlsCAFile=%2Fetc%2Fmongo-ca%2Fca.crt&tlsAllowInvalidCertificates=false&tlsAllowInvalidHostnames=false'
    } else { $_ }
}
[IO.File]::WriteAllLines($envFile, [string[]]$lines, (New-Object Text.UTF8Encoding($false)))
minikube -p tienda-prod kubectl -- create secret generic tienda-secrets -n tienda-prod --from-env-file=k8s/tls/prod-private/secrets.env --dry-run=client -o json | minikube -p tienda-prod kubectl -- apply -f -
```

No imprimir el archivo ni el JSON de Secret. `postgres-ca` y `mongo-ca` contienen
solo CA pública; las claves de servidor están en Secrets y `k8s/tls/` permanece
fuera de Git. No compartir la clave privada de la CA (`rootCA-key.pem`).

## 7. Producción: aplicación, monitoreo y controles

```powershell
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/aplicacion.yaml -f k8s/minikube/tareas-programadas.yaml -f k8s/minikube/escalamiento.yaml
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n tienda-prod --timeout=300s
helm repo add traefik https://traefik.github.io/charts
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update
helm upgrade --install monitoreo prometheus-community/kube-prometheus-stack --version 92.13.1 --kube-context tienda-prod -n monitoreo --create-namespace -f k8s/minikube/monitoreo-values.yaml --wait --timeout 10m
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Preparar-MetricasPostgres.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Preparar-MetricasMongo.ps1
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/metricas-postgres.yaml -f k8s/minikube/metricas-mongo.yaml -f k8s/minikube/alertas-bases.yaml
minikube -p tienda-prod kubectl -- rollout status deployment/postgres-exporter -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/mongo-exporter -n tienda-prod --timeout=300s
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Comprobar-TlsPostgres.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Comprobar-TlsMongo.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Comprobar-Permisos.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Comprobar-Red.ps1
```

Esperado: backend/frontend 2/2, exporters 1/1, HPA con CPU disponible y mínimo
dos, TLS verificado con nombre/CA correctos y rechazos esperados. En Grafana
consultar `pg_up` y `mongodb_up`: ambos deben ser 1. Un exporter Running con
valor 0 no demuestra conexión correcta. Los CronJobs sincronizan historial y
liberan reservas vencidas; una réplica web no ejecuta migraciones al arrancar.

## 8. HTTPS web y nombres locales

Instalar Traefik en ambos contextos:

```powershell
helm repo add traefik https://traefik.github.io/charts
helm repo update
helm upgrade --install traefik traefik/traefik --version 42.1.3 --kube-context tienda-dev -n kube-system -f k8s/minikube/desarrollo/traefik-values.yaml --wait
helm upgrade --install traefik traefik/traefik --version 42.1.3 --kube-context tienda-prod -n kube-system -f k8s/minikube/traefik-values.yaml --wait
```

En PowerShell **como administrador**, desde la raíz del clon, preparar hosts
y certificados. Solo estas operaciones locales requieren esa elevación:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/desarrollo/Preparar-HttpsWindows.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Preparar-NombreTienda.ps1
mkcert -cert-file k8s/tls/tienda-prod.crt -key-file k8s/tls/tienda-prod.key tskull-shirt.test tienda-prod.test
```

De nuevo en la terminal normal:

```powershell
minikube -p tienda-dev kubectl -- create secret tls tienda-tls -n tienda-dev --cert=k8s/tls/dev-private/tienda-dev.crt --key=k8s/tls/dev-private/tienda-dev.key
minikube -p tienda-prod kubectl -- create secret tls tienda-tls -n tienda-prod --cert=k8s/tls/tienda-prod.crt --key=k8s/tls/tienda-prod.key
minikube -p tienda-dev kubectl -- apply -f k8s/minikube/desarrollo/entrada-https.yaml
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/entrada-https.yaml
```

Dejar **una terminal abierta por cada comando**:

```powershell
minikube -p tienda-prod kubectl -- port-forward -n kube-system service/traefik 9443:443 --address=127.0.0.1
```

```powershell
minikube -p tienda-dev kubectl -- port-forward -n kube-system service/traefik 9444:443 --address=127.0.0.1
```

```powershell
minikube -p tienda-prod kubectl -- port-forward -n monitoreo service/monitoreo-grafana 3000:80 --address=127.0.0.1
```

| Ambiente | URL |
| --- | --- |
| Producción | https://tskull-shirt.test:9443/ |
| Desarrollo | https://tienda-dev.test:9444/ |
| Grafana | http://127.0.0.1:3000/ |

Las credenciales de tienda son las generadas para **cada** ambiente; no se
incluyen contraseñas comunes. Para Grafana consultar privadamente el campo
`admin-password` del Secret `monitoreo-grafana` en `monitoreo`, decodificado
desde base64; no guardarlo en una captura ni en Git.

```powershell
curl.exe --silent --show-error --noproxy "*" --ssl-revoke-best-effort --output NUL --write-out "Prod HTTP %{http_code}\n" https://tskull-shirt.test:9443/health/ready
curl.exe --silent --show-error --noproxy "*" --ssl-revoke-best-effort --output NUL --write-out "Dev HTTP %{http_code}\n" https://tienda-dev.test:9444/health/ready
```

Se espera HTTP 200, sin `-k`. Probar login, reservar una unidad, cancelar y
comprobar retorno del stock; luego confirmar otro pedido y consultar historial.
La CA debe ser confiable para el usuario que ejecuta navegador/curl.

## 9. Secrets cifrados, firewall y acceso LAN opcional

En producción, después de una instalación inicial sana:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Habilitar-CifradoSecrets.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Comprobar-CifradoSecrets.ps1
```

El primer script configura secretbox, reinicia el API server y reescribe Secrets.
Comprobar el prefijo cifrado en etcd y lectura autorizada por la API. Conservar
la copia DPAPI y la clave del nodo fuera de Git. DPAPI está ligado al usuario de
Windows: no es un respaldo portable de la clave para otra computadora. Si falla
una transferencia, revisar el error y permisos; no generar otra clave para un
clúster ya cifrado ni borrar su configuración para que arranque.

La instalación predeterminada solo escucha en loopback: dev, prod y Grafana
no quedan publicados en LAN. El acceso del teléfono es opcional y depende de
la IP e interfaz propias, certificado y regla de firewall, no de la IP del autor.
Consultar [acceso celular](docs/ACCESO_CELULAR.md) y
[firewall LAN](k8s/minikube/ACCESO-LAN.md). La prueba del autor de 31/10/2026
permitió prod y bloqueó dev; no es una prueba de otra computadora o red.
No abrir `0.0.0.0` indiscriminadamente ni exponer bases, dev o Grafana.

## 10. Respaldos y recuperación

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Respaldar-Bases.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Respaldo-Programado.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Programar-Respaldos.ps1 -Hora 20:00
```

Verificar `Get-ScheduledTaskInfo -TaskName TiendaProd-RespaldoDiario`: resultado
0 tras una ejecución. Requiere sesión iniciada, Docker y Minikube disponibles;
conserva siete copias cifradas completas. Los archivos quedan en `output/`,
fuera de Git y de PVC, pero en el mismo host: falta una copia externa para
protegerse ante pérdida de la computadora.

Para restaurar, reemplazar `RUTA_DEL_RESPALDO` por una carpeta real creada por
el script, no por un respaldo del autor. Se crea un namespace de prueba:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Restaurar-Prueba.ps1 -CarpetaRespaldo "RUTA_DEL_RESPALDO"
```

Verificar SHA256, conteos, índices y archivos; seguir
[restauración de la aplicación](k8s/minikube/restauracion/README.md).
No restaurar sobre producción para obtener evidencia. Objetivos RPO 24 h y
RTO 30 min. En la copia aislada nueva del 31/10/2026 se midieron 391.9 s hasta
validar catálogo e historial autenticados; antigüedad puntual del respaldo 7.76 s.
No representa pérdida física del host ni garantiza ejecución diaria. Los 7.1 s
de la continuación histórica no son el tiempo total de recuperación.

## 11. Actualización, rollback y pruebas

Leer [actualización de producción](k8s/minikube/ACTUALIZAR-PRODUCCION.md).
Construir una etiqueta nueva, cargarla primero en dev, editar los manifiestos
de aplicación y CronJobs, aplicar y comprobar función. Promover la misma
imagen a prod. Anotar imagen y revisión antes de cambiar; `rollout undo`
revierte la plantilla, no datos ni migraciones. No reutilizar una etiqueta
publicada para distinto contenido. Guardar commit, etiqueta y digest.

Los archivos de `tests/` se ejecutan con PHP de la imagen mediante un volumen
de solo lectura, porque `.dockerignore` excluye tests de la imagen productiva:

```powershell
docker run --rm --network none --read-only --mount "type=bind,source=$($PWD.Path),target=/src,readonly" --workdir /src --entrypoint php tienda-backend:0.1.6 tests/base-url-lan.php
```

Esperado: 10 casos OK, incluyendo rechazo de Host no permitido. Las pruebas
funcionales de stock, roles, pedidos e historial se realizan también en la UI.
`carga-hpa.yaml` genera carga interna; `Medir-Catalogo.ps1` mide lectura de
catálogo autenticada. No confundir throughput de una sonda con compras reales.
Las salidas de pruebas se guardan en `output/evidencias/`, sin secretos.

## 12. Encendido y apagado

Después de reiniciar Windows, abrir Docker Desktop y ejecutar:

```powershell
minikube start -p tienda-prod --extra-config=apiserver.encryption-provider-config=/var/lib/minikube/certs/encryption-config.json
minikube start -p tienda-dev
minikube -p tienda-prod kubectl -- get nodes
minikube -p tienda-prod kubectl -- get deployments,statefulsets,pvc -n tienda-prod
minikube -p tienda-dev kubectl -- get deployments,statefulsets,pvc -n tienda-dev
```

El parámetro de cifrado se usa **solo después** de la fase 9; en un perfil que
todavía no tiene el archivo de cifrado, arrancar sin ese parámetro. Volver a
abrir los tres port-forward de la fase 8; no volver a crear Secrets, semillas
o PVC. Un puerto ocupado suele ser otro port-forward ya abierto.

Para apagar: Ctrl+C en cada port-forward y después:

```powershell
minikube stop -p tienda-dev
minikube stop -p tienda-prod
```

Cerrar Docker Desktop y apagar Windows. No usar `minikube delete`:
eliminar un perfil o PVC puede destruir sus datos.

## 13. Diagnóstico y límites conocidos

| Síntoma | Revisar y comprobar |
| --- | --- |
| YAML no encontrado | Ejecutar desde raíz del clon; no desde la carpeta del usuario |
| PVC Pending | StorageClass, Pods CSI, NFS, DNS y eventos del claim |
| Backend no Ready | Logs y eventos; Secret/CA; motores disponibles; no eliminar PVC |
| HTTP 404 por IP | Regla Host/IngressRoute y proveedor CRD; no asumir bloqueo de red |
| pg_up / mongodb_up = 0 | Credenciales de exporter, TLS, CA/nombre y NetworkPolicy |
| Historial pendiente | Job sincronizador, imagen 0.1.6, Mongo TLS y evento outbox |
| Secret Forbidden con observador | Rechazo esperado del mínimo privilegio |
| Error ACL/DPAPI | Windows PowerShell 5.1, directorio NTFS local, usuario propietario |
| Imágenes con CVE | Analizar y actualizar dependencias; un build correcto no las elimina |

No hay HA de motores, NFS ni control plane; las réplicas web no sustituyen
esa redundancia. Se añadieron Loki/Alloy, panel de operación y cinco categorías
de alertas. Promtool validó las reglas con series sintéticas; respaldo se activó
y resolvió realmente. No se presenta comparación causal con/sin HPA ni traza
dedicada por réplica. La reconstrucción limpia integral no se ejecutó por falta
de recursos; el profesor autorizó documentar esa limitación según el alumno.

### Observabilidad de cierre y exposición

Después de instalar la pila y exporters:

```powershell
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/observabilidad.yaml -f k8s/minikube/alertas-operacion.yaml -f k8s/minikube/dashboard-operacion.yaml
powershell -NoProfile -ExecutionPolicy Bypass -File k8s/minikube/Publicar-MetricasRespaldo.ps1
```

La ConfigMap inicial del respaldo se reinicia al aplicar observabilidad: volver
a publicar sus métricas. Sin copia cifrada verificada, la alerta es esperable.
Traefik usa los valores de métricas/ServiceMonitor en `traefik-values.yaml`;
actualizarlos con el mismo release/chart descrito en la instalación. Reabrir
su port-forward si el Pod cambia. No publicar Loki en LAN.

Resultados, límites y respuesta a alertas: [operación y validación](docs/OPERACION_Y_VALIDACION.md).
Acordeón para encender/apagar, cambio de red, explicación y demostración:
[guía de exposición](docs/GUIA_EXPOSICION.md).

## 14. Organización y entrega

- `application/`, `bin/`, `public/`, `assets/`: aplicación y comandos de negocio.
- `docker/php/Dockerfile`, `docker/nginx/`: construcción y servidor web.
- `composer.json`, `composer.lock`: dependencias versionadas.
- `database/`: migraciones y datos de demostración; no dumps privados.
- `k8s/minikube/`: producción, red, almacenamiento, monitoreo y operación.
- `k8s/minikube/desarrollo/`: desarrollo independiente.
- `tests/`: pruebas del proyecto.
- `docs/diagramas/`: diagramas SVG editables; `docs/datos/`: diccionarios.
- `docs/`: notas técnicas. Las guías `kind`/overlays antiguas no son este flujo.

La sección 11 y D05 del PDF de la práctica exigen Dockerfiles, dependencias,
manifiestos, scripts, migraciones, pruebas y manual, excluyendo claves,
kubeconfig privilegiados y dumps sensibles. `.gitignore` conserva fuentes y
plantillas `*.example`; excluye credenciales, claves, respaldos, uploads nuevos,
artefactos y salidas locales. Las imágenes demo previamente versionadas son
material histórico; no añadir fotos o datos reales de usuarios.

Informe editable: https://docs.google.com/document/d/17YDej1HMnlXsJHKykOZAEY82FZ3U3RFRGWobJNoULwg/edit

Las credenciales, la CA privada, los volúmenes y los respaldos del autor **no
se necesitan ni se distribuyen** para instalar: cada clon genera los suyos.
