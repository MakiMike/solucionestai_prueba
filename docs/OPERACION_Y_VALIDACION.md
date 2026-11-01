# Operación y validación de cierre

Corte de mediciones: 31/10/2026. Consolidación: 01/11/2026.
La [guía de exposición](GUIA_EXPOSICION.md) contiene el acordeón ejecutable y la explicación del sistema.

| Comprobación | Resultado conservado |
| --- | --- |
| Negocio, stock, idempotencia y roles | 22 comprobaciones correctas, esquema aislado |
| HTTP/CRUD de usuarios, productos y pedidos | 26 comprobaciones correctas, datos temporales retirados |
| Privilegios de datos | 7 comprobaciones correctas, rechazos SQL/Mongo esperados |
| Ingress y egress por separado | 8 casos coincidentes contra servicios sanos |
| Persistencia dev | Recreación de PostgreSQL y MongoDB; conteos e índices conservados |
| Outbox | Fallo de conexión aislado, recuperación real y replay sin duplicar |
| Restauración nueva aislada | 151.89 s a salud/login; 391.9 s hasta catálogo e historial autenticados |
| Antigüedad del respaldo | 7.76 s en ese ensayo puntual, sin garantía diaria ni snapshot atómico |
| Mantenimiento completo de m03 | 81 HTTP 200 y cero fallos; drain y uncordon, sin --force |
| Alertas operativas | Cinco categorías validadas por Promtool con series sintéticas; respaldo activado/resuelto real |
| Logs | Loki consultó registros reales de frontend/backend y Traefik recogidos por Alloy |

Evidencias originales privadas: `output/evidencias/cierre-final-20261031/`. Los respaldos y las credenciales no se publican. El ensayo HPA histórico se documenta por separado; no se presenta comparación causal de rendimiento con/sin HPA. La reconstrucción completa en recursos limpios quedó exenta según la autorización del profesor comunicada por el alumno, por insuficiencia de RAM/disco. No se representa como ejecutada.

## Cinco alertas: qué hacer

- **Aplicación sin disponibilidad:** revisar réplicas Ready, eventos, probes, imágenes, PVC y dependencias; comprobar HTTPS. No borrar datos.
- **Nodo no disponible:** revisar Docker/Minikube, CNI y recursos del host; identificar Pods afectados. Un solo control plane o motor no tiene failover.
- **Errores HTTP 5xx:** correlacionar Traefik, Nginx y PHP en Loki; revisar endpoints, conexión a bases y despliegues recientes. Un túnel port-forward roto no es por sí solo caída del clúster.
- **Saturación:** revisar CPU/memoria respecto a límites, HPA y cuotas. No aumentar réplicas sin RAM disponible. Identificar carga antes de reiniciar.
- **Respaldo vencido/fallido:** revisar tarea Windows, Docker, contexto, espacio, TLS y logs; ejecutar respaldo verificado y publicar métricas. No restablecer manualmente la métrica a éxito para ocultar un fallo.

## Provisionar los componentes nuevos

Después de kube-prometheus-stack y exporters, aplicar `observabilidad.yaml`, `alertas-operacion.yaml` y `dashboard-operacion.yaml`. Loki usa un PVC de 1 GiB y retención de 24 horas. Alloy lee Pods/logs con permisos acotados y no monta el host ni lee Secrets. Grafana descubre datasource `loki-tienda` y panel `tskull-operacion`; el panel espera datasource Prometheus UID `prometheus`.

`observabilidad.yaml` contiene la ConfigMap inicial del respaldo a cero. Cada vez que se aplique, ejecutar `Publicar-MetricasRespaldo.ps1` para publicar el último respaldo cifrado válido existente. Si no existe, la alerta es esperable. El publicador no revela claves ni contenido de respaldos.

La configuración Traefik habilita métricas y ServiceMonitor. Un upgrade puede reemplazar el Pod y cortar el port-forward: reabrirlo. Observabilidad tiene componentes únicos; no se garantiza continuidad de Grafana durante mantenimiento del nodo de su volumen.

## Límites

No hay alta disponibilidad de bases, NFS ni laptop. DPAPI está ligado al usuario de Windows y el respaldo local no protege de pérdida física del equipo. Persisten CVE de las imágenes según los escaneos conservados. Las pruebas automatizadas no son una auditoría de cada endpoint. No hay CI/CD. No se atribuye a cada réplica una solicitud de negocio con una traza dedicada.
