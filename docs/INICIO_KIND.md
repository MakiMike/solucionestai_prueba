# Inicio con Docker Desktop kind y Kubernetes

Esta guía reemplaza el procedimiento anterior con K3s y máquinas virtuales. Se ejecuta manualmente en PowerShell sobre Windows, desde `C:\laragon\www\sistema`. Solo prepara los clústeres; no despliega la tienda ni sus bases de datos. No se han ejecutado estos pasos automáticamente.

Se crearán `tienda-dev` con un nodo y `tienda-prod` con un control plane y dos workers. Los nodos serán contenedores Docker y compartirán CPU, RAM y disco de una laptop. Docker Desktop utiliza su entorno Linux de WSL 2: no se administran cuatro máquinas virtuales, pero sí existe consumo de recursos de virtualización.

## 1 Revisar recursos y Docker Desktop

Abre Docker Desktop y espera a que el motor esté listo. Usa contenedores Linux y el backend WSL 2. No hace falta activar el Kubernetes integrado de Docker Desktop para usar kind; si está activo, consume recursos adicionales y es otro clúster distinto.

En PowerShell:

```powershell
wsl --version
docker version
docker info --format '{{.OSType}}'
Get-CimInstance Win32_ComputerSystem | Select-Object NumberOfLogicalProcessors,@{Name='RAM_GB';Expression={[math]::Round($_.TotalPhysicalMemory/1GB,1)}}
Get-PSDrive C | Select-Object Name,@{Name='Libre_GB';Expression={[math]::Round($_.Free/1GB,1)}}
```

Esperado: `docker version` muestra Client y Server, y el tipo es `linux`. Revisar también la unidad donde Docker almacena su disco, si no es C. No se garantiza que cuatro nodos y la aplicación quepan sin conocer RAM y disco libre. El arranque inicial necesita menos recursos que los motores y la observabilidad completos. No asignar toda la RAM a WSL ni borrar datos de Docker para liberar espacio sin revisarlos.

Captura 01: Docker Desktop con el motor activo y la opción WSL 2. Captura 02: versiones, tipo Linux, memoria y espacio disponible. No incluir información privada innecesaria.

## 2 Instalar kind y revisar kubectl

Si `kind version` no funciona, instalar la versión elegida:

```powershell
winget install --exact --id Kubernetes.kind --version 0.33.0
```

Cierra y abre PowerShell después de instalar. Si winget no ofrece esa versión, usa el binario de Windows de la publicación oficial v0.33.0, no otro paquete de nombre parecido. Docker Desktop normalmente incluye kubectl. Revisar:

```powershell
kind version
kubectl version --client
```

Los archivos fijan Kubernetes v1.35.8 por digest y Calico v3.33.0. El cliente kubectl debe ser compatible: para servidor 1.35, usar cliente 1.34, 1.35 o 1.36. Si el cliente instalado queda fuera de ese rango, ajustarlo antes de seguir. Ambas imágenes de nodos son idénticas para compartir su descarga, aunque los contenedores y datos de los clústeres sean separados.

Captura 03: versiones de kind y kubectl. Registrar también la versión de Docker Desktop.

## 3 Revisar los archivos de los clústeres

```powershell
Set-Location C:\laragon\www\sistema
Get-Content .\k8s\kind\development.yaml
Get-Content .\k8s\kind\production.yaml
kind get clusters
```

Si ya existen clústeres con esos nombres, no los borres ni repitas la creación: primero identifica qué contienen. Comprueba que los CIDR no se solapen con redes Docker, LAN o VPN. Desarrollo usa Pods `10.244.0.0/16` y Services `10.96.0.0/16`; producción `10.245.0.0/16` y `10.97.0.0/16`. Estos son rangos configurados para el laboratorio, no direcciones ya observadas.

Los archivos reservan los puertos locales 8443 y 9443 para una entrada HTTPS futura hacia NodePort 30443. Eso aún no publica ninguna página: falta instalar y configurar Traefik. Verifica que esos puertos estén libres:

```powershell
Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -in 8443,9443 }
```

Sin resultados significa que no se encontró un listener TCP en esos puertos en ese momento.

Captura 04: los dos archivos, sus roles y rangos distintos.

## 4 Preparar la red Calico

El CNI predeterminado de kind se desactiva para instalar Calico y poder aplicar NetworkPolicy después. El siguiente archivo solo descarga el manifiesto oficial fijado y genera una copia con el CIDR de cada clúster; no lo instala:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\k8s\kind\Preparar-Calico.ps1
```

La opción Bypass se limita a ese proceso; no modifica permanentemente la política de ejecución de Windows. Revisar las copias antes de aplicarlas. El helper no contiene credenciales ni inicia Docker.

Captura 05: salida de preparación y hashes de los dos manifiestos.

## 5 Crear desarrollo e instalar su red

```powershell
$env:KIND_EXPERIMENTAL_PROVIDER='docker'
kind create cluster --name tienda-dev --config .\k8s\kind\development.yaml
kubectl --context kind-tienda-dev get nodes
```

Al no haber instalado todavía el CNI, `NotReady` inicialmente es esperado. Instalar la red y esperar:

```powershell
kubectl --context kind-tienda-dev apply --server-side -f .\k8s\kind\calico-development.yaml
kubectl --context kind-tienda-dev -n kube-system rollout status daemonset/calico-node --timeout=300s
kubectl --context kind-tienda-dev wait --for=condition=Ready nodes --all --timeout=300s
kubectl --context kind-tienda-dev get nodes -o wide
kubectl --context kind-tienda-dev get pods -A
```

Esperado: un nodo `tienda-dev-control-plane` en Ready y los componentes de red/DNS estabilizados. En este clúster de un nodo también se ejecutarán las cargas de desarrollo.

Captura 06: creación finalizada y nodo Ready. Captura 07: Pods de Calico y CoreDNS ejecutándose. Conservar también cualquier fallo y su posterior corrección; no presentarlo como un resultado exitoso.

## 6 Crear producción de laboratorio

```powershell
kind create cluster --name tienda-prod --config .\k8s\kind\production.yaml
kubectl --context kind-tienda-prod apply --server-side -f .\k8s\kind\calico-production.yaml
kubectl --context kind-tienda-prod -n kube-system rollout status daemonset/calico-node --timeout=300s
kubectl --context kind-tienda-prod wait --for=condition=Ready nodes --all --timeout=300s
kubectl --context kind-tienda-prod get nodes -o wide
kubectl --context kind-tienda-prod get pods -A
```

Esperado: `tienda-prod-control-plane`, `tienda-prod-worker` y `tienda-prod-worker2` en Ready. Los workers pueden mostrar `<none>` en ROLES; eso no significa que estén desconectados. Esta topología simula varios nodos, pero no tolera perder la laptop.

Captura 08: los tres nodos productivos Ready. Captura 09: componentes del sistema de producción.

## 7 Demostrar los dos clústeres y detenerse

```powershell
kind get clusters
kubectl config get-contexts
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}'
kubectl --context kind-tienda-dev get nodes
kubectl --context kind-tienda-prod get nodes
docker stats --no-stream
```

Captura 10: los dos contextos y los cuatro contenedores de nodos. Captura 11: consumo de recursos al terminar. No capturar el contenido de kubeconfig ni tokens. Los nodos pueden compartir la red Docker llamada kind; las API y credenciales siguen separadas, pero eso no constituye aislamiento físico ni de red hostil entre ambientes.

Aquí termina esta etapa: dos clústeres creados, red instalada y nodos Ready. No aplicar todavía `k8s/overlays` ni el Job de migración. Traefik, certificados, política de entrada, volumen compartido, motores y aplicación se configurarán en las siguientes etapas. La entrada futura será `https://tienda-dev.test:8443` y `https://tienda-prod.test:9443`; no debe funcionar todavía.

## Si hay errores

- Solo aparece Client en Docker: iniciar Docker Desktop y comprobar el motor Linux.
- kind no se reconoce: abrir otra terminal y revisar la instalación/PATH.
- Puerto ocupado: corregir el puerto en el archivo antes de crear, sin detener otros servicios a ciegas.
- NotReady después de Calico: revisar `kubectl --context CONTEXTO get pods -A` y `kubectl --context CONTEXTO describe node NOMBRE`. Guardar el mensaje exacto.
- Descarga de imágenes o tiempo de espera: comprobar Internet y recursos. No borrar el clúster automáticamente como primer intento.

Fuentes consultadas el 24/10/2026: https://kind.sigs.k8s.io/docs/user/quick-start/ ; https://github.com/kubernetes-sigs/kind/releases/tag/v0.33.0 ; https://kind.sigs.k8s.io/docs/user/configuration/ ; https://docs.tigera.io/calico/latest/getting-started/kubernetes/kind ; https://docs.tigera.io/calico/latest/getting-started/kubernetes/requirements ; https://docs.docker.com/desktop/features/wsl/ .
