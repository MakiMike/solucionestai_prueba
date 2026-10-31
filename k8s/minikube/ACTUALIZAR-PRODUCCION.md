# Actualizacion del entorno existente

Este procedimiento parte de tienda-prod ya instalado, con TLS PostgreSQL y
MongoDB verificado y los certificados y Secrets existentes. No es un instalador
desde cero. No vuelve a ejecutar migraciones ni semillas.

Estado declarado para la aplicacion:

- Backend e initContainer preparar-fotos: tienda-backend:0.1.6.
- Frontend: tienda-frontend:0.1.1.
- CronJobs vencer-pedidos y sincronizar-historial: tienda-backend:0.1.6.
- Backend y CronJobs: PostgreSQL verify-full con postgres-ca; mongo-ca montada
  para las URI TLS conservadas en tienda-secrets.
- Exporters: postgres-ca con verify-full y mongo-ca para la URI de mongo-metrics.

Las credenciales no se incorporan a estos manifiestos. Los ConfigMaps postgres-ca
y mongo-ca y los Secrets de servicio deben existir antes de aplicarlos.

Ejecutar manualmente desde C:\laragon\www\sistema:

```powershell
minikube -p tienda-prod kubectl -- apply --dry-run=server -f k8s/minikube/aplicacion.yaml -f k8s/minikube/tareas-programadas.yaml -f k8s/minikube/metricas-postgres.yaml -f k8s/minikube/metricas-mongo.yaml
```

Si la validacion termina sin errores:

```powershell
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/aplicacion.yaml -f k8s/minikube/tareas-programadas.yaml -f k8s/minikube/metricas-postgres.yaml -f k8s/minikube/metricas-mongo.yaml
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-prod --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/postgres-exporter -n tienda-prod --timeout=180s
minikube -p tienda-prod kubectl -- rollout status deployment/mongo-exporter -n tienda-prod --timeout=180s
```

Las proximas ejecuciones de los CronJobs usaran 0.1.6; los Jobs ya creados
conservan su plantilla anterior. Un exporter Ready solo demuestra que atiende
su puerto: comprobar despues pg_up y mongodb_up en Prometheus/Grafana.

## Alcance de los archivos antiguos de instalacion

bases-datos.yaml y los Jobs iniciales describen la fase anterior a habilitar TLS.
No aplicar toda esta carpeta sobre produccion: contiene pruebas de bloqueo,
carga y restauracion, ademas de configuraciones de distintas fases.
El estado TLS de los servidores se conserva mediante postgres-tls.patch.yaml y
mongo-tls.patch.yaml y sus ConfigMaps/Secrets, preparados anteriormente. Este
procedimiento no aplica ni modifica los StatefulSets.

Una reconstruccion desde cero aun requiere documentar y validar el orden
completo de instalacion y activacion TLS. No se ha demostrado con este paso.
Este procedimiento no configura tags ni despliegue automatico desde Git.
