# Aplicacion sobre el respaldo recuperado

Prerequisito: namespace tienda-restore-20261025-235828 con postgres-restore,
mongo-restore, fotos-restore y sus tres PVC recuperados. mongo-existente.yaml
recrea solamente el Pod MongoDB, con el PVC mongo-datos y el Secret
restore-credentials ya existentes. Debe aplicarse con -n al namespace de prueba.

aplicacion-recuperada.yaml pertenece exclusivamente a ese namespace. Contiene
una replica de backend 0.1.2 y frontend 0.1.0, sus servicios y los servicios de
las bases. No crea credenciales, PVC ni tareas de sincronizacion/expiracion, y
no ejecuta migraciones o semillas. No aplica recursos en tienda-prod.

Antes de aplicarlo, etiquetar los dos Pods existentes:

```powershell
minikube -p tienda-prod kubectl -- label pod postgres-restore app=postgres-restore -n tienda-restore-20261025-235828 --overwrite
minikube -p tienda-prod kubectl -- label pod mongo-restore app=mongo-restore -n tienda-restore-20261025-235828 --overwrite
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/restauracion/aplicacion-recuperada.yaml
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n tienda-restore-20261025-235828 --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n tienda-restore-20261025-235828 --timeout=300s
minikube -p tienda-prod kubectl -- port-forward service/frontend 9081:8080 -n tienda-restore-20261025-235828 --address=127.0.0.1
```

Abrir http://127.0.0.1:9081/, iniciar sesion con una cuenta contenida en el
respaldo y consultar el catalogo y una prenda con imagen recuperada. No realizar
compras ni modificaciones para esta comprobacion. El login escribe sesiones en
la copia de PostgreSQL. Las imagenes se montan solo lectura desde el subdirectorio
fotos del PVC fotos-datos; ambos Deployments deben poder acceder a ese volumen RWO
en el nodo al que pertenece. Se conserva el Pod fotos-restore.

Los servicios resuelven solamente los Pods etiquetados de este namespace. Las
reglas permiten frontend -> backend, backend -> motores restaurados y DNS de
CoreDNS. Se mantiene la politica aislar-restauracion; no se permite salida hacia
los motores de produccion. Port-forward se enlaza solo a 127.0.0.1.

Es una comprobacion aislada por HTTP local; no replica el TLS de produccion.
Usa restore_owner y la contrasena independiente del Secret restore-credentials,
creados durante la restauracion. Esa misma clave se reutiliza para la configuracion
de cifrado de CI3 en esta copia temporal; no es la clave de la aplicacion productiva.
No copiar esta configuracion a desarrollo o produccion.

La captura valida requiere mostrar datos e imagenes recuperados en la aplicacion;
Pods Ready o una pantalla de login por si solos no demuestran esa recuperacion.
Este paso no mide un RTO completo: el respaldo y las bases ya estaban restaurados.
