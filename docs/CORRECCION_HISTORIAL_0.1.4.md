# Correccion de sincronizacion con driver MongoDB 2.x

Produccion ejecuta PHP 8.3.36 y extension mongodb 2.1.0. El pedido 1 de la
prenda 099 talla M quedo confirmado, pero con historial pendiente. Una tarea
manual creada desde sincronizar-historial fallo con InvalidArgumentException.
El diagnostico sin escrituras del backend confirmo ping autenticado, evento 1
pendiente y construccion correcta de fecha, BulkWrite y WriteConcern.

La llamada de TiendaHistory::sync pasaba WriteConcern directamente como tercer
argumento de Manager::executeBulkWrite. Desde extension 2.0 esa forma ya no es
aceptada. Se sustituye por array('writeConcern' => $concern), manteniendo
majority y timeout 3000 ms. Fuente oficial:
https://www.php.net/manual/en/mongodb-driver-manager.executebulkwrite.php

No se cambia TLS, la coleccion ni su validador/indice unico. Se conserva el
upsert por evento_id con $setOnInsert y la marca enviado_en solo despues de
la escritura. No se vuelve a crear ni pagar el pedido.

Prueba local sin servicios externos:
```powershell
C:\xampp\php\php.exe -n tests/history-driver.php
```
El contrato simulado exige opciones array y verifica envio, reintento sin
duplicar y rollback/conservacion del pendiente tras una escritura fallida.
Se comprobo que la misma prueba falla con la llamada antigua y pasa con la
corregida. No sustituye la comprobacion real con el driver y MongoDB.

Se prepara candidato backend 0.1.4. prueba-historial-014.yaml toma la plantilla
de sincronizar-historial con la nueva imagen, conserva configuracion TLS,
Secret refs y etiqueta worker, y desactiva reintentos automaticos para observar
el primer resultado. Procesa hasta 100 eventos pendientes de la outbox usando
el flujo idempotente normal. Las tareas programadas y Deployments siguen en
0.1.3 hasta verificar esta ejecucion e instalar de forma persistente el arreglo.

No sobrescribir 0.1.3. La construccion puede usar cache de las extensiones ya
compiladas: el cambio es de codigo PHP, no de dependencias. El resultado de
Scout 0.1.3 no se atribuye a 0.1.4 sin volver a analizar esa imagen.

Resultado real aportado por el usuario: construccion 0.1.4 completada, imagen
cargada en tienda-prod, Job historial-compat-014 creado y salida
"Eventos sincronizados: 2". La escritura real con TLS y el flujo de outbox
terminaron correctamente para esos eventos pendientes.

Se actualizaron las referencias del backend, initContainer y ambos CronJobs a
0.1.4 en los archivos de desarrollo y produccion, conservando las CA y verify-full
de produccion. Falta que el usuario aplique esos archivos para instalar la
correccion permanentemente y confirme el historial visible del pedido 1.
El Job puntual no reemplaza por si solo los Deployments ni los CronJobs.
