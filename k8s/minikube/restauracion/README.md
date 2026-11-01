# Aplicación sobre un respaldo recuperado

Prerrequisito: ejecutar `Restaurar-Prueba.ps1 -CarpetaRespaldo RUTA_REAL` desde
la raíz del clon y obtener `RESTAURACION VERIFICADA`. Usar el namespace que
imprime esa ejecución, no el identificador histórico del autor. Deben existir
postgres-restore, mongo-restore, fotos-restore, sus tres PVC y restore-credentials.

La plantilla no fija namespace: **siempre** aplicar con `-n $restoreNamespace`.
Usa backend 0.1.6 y frontend 0.1.1, construidos en el README principal.
No ejecuta migraciones, semillas ni CronJobs. No aplicarla en tienda-dev o tienda-prod.

```powershell
$restoreNamespace = Read-Host 'Namespace tienda-restore-... mostrado por Restaurar-Prueba.ps1'
if ($restoreNamespace -notmatch '^tienda-restore-[0-9]{8}-[0-9]{6}$') { throw 'Namespace de restauracion no valido' }
minikube -p tienda-prod kubectl -- get pods,pvc -n $restoreNamespace
minikube -p tienda-prod kubectl -- label pod postgres-restore app=postgres-restore -n $restoreNamespace --overwrite
minikube -p tienda-prod kubectl -- label pod mongo-restore app=mongo-restore -n $restoreNamespace --overwrite
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/restauracion/aplicacion-recuperada.yaml -n $restoreNamespace
minikube -p tienda-prod kubectl -- rollout status deployment/backend -n $restoreNamespace --timeout=300s
minikube -p tienda-prod kubectl -- rollout status deployment/frontend -n $restoreNamespace --timeout=300s
minikube -p tienda-prod kubectl -- port-forward service/frontend 9081:8080 -n $restoreNamespace --address=127.0.0.1
```

Si mongo-restore no existe, pero su PVC y Secret sí, antes de etiquetarlo:

```powershell
minikube -p tienda-prod kubectl -- apply -f k8s/minikube/restauracion/mongo-existente.yaml -n $restoreNamespace
minikube -p tienda-prod kubectl -- wait --for=condition=ready pod/mongo-restore -n $restoreNamespace --timeout=300s
```

No crear otro motor sobre el mismo PVC cuando el Pod ya funciona. Si 9081 está
ocupado, cerrar el port-forward anterior conocido; otro puerto requiere cambiar
también APP_BASE_URL de esta copia para evitar redirecciones al puerto anterior.

Abrir http://127.0.0.1:9081/ con una cuenta contenida en **ese respaldo**, no
con una contraseña del autor. Consultar catálogo y prenda con imagen recuperada;
no hacer compras para esta comprobación. El login escribe sesiones en la copia
SQL y las fotos se montan de solo lectura. Frontend y backend deben poder
acceder al PVC RWO en el mismo nodo.

Las políticas permiten frontend → backend, backend → bases de la copia y DNS;
aislar-restauracion impide acceder a las bases productivas. La aplicación temporal
usa restore_owner y el Secret generado: la URI contiene una variable, no una
contraseña literal. La clave también configura CI3 en esta copia; no copiar
ese esquema a producción.

Validar datos, relaciones, índices y fotos visibles en la aplicación; login
o Pods Ready no bastan. Es HTTP local aislado, no el TLS de producción.
Medir RTO desde el inicio del incidente hasta la validación final. Este paso
con bases ya recuperadas o una continuación no mide el tiempo total.
