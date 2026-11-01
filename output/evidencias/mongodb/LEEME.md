# Incidencia: conexión de MongoDB durante la inicialización

Ambiente: tienda-prod (Minikube). Verificación realizada el 25/10/2026, hora local.

## Síntoma
El Job tienda-migrate terminó con ConnectionTimeoutException después de completar la semilla y los permisos de PostgreSQL. MongoDB estaba Running, su EndpointSlice apuntaba al Pod y el ping local devolvía ok: 1. La comprobación TCP desde otro Pod también fue correcta.

## Diagnóstico
La prueba con el nombre completo del servicio y con su IP mostró Failed to resolve con un fragmento de credencial incluido delante del host. Los caracteres reservados de la contraseña estaban sin codificar dentro de la URI. No era un fallo de DNS del clúster.

## Corrección aplicada
Se reconstruyeron MONGO_URI y MONGO_OWNER_URI desde sus contraseñas correspondientes usando Uri.EscapeDataString exclusivamente en el componente de contraseña. Se conservaron las contraseñas y los permisos. Se actualizó tienda-secrets y se recreó el Pod de diagnóstico para cargar los valores nuevos.

## Verificación
El Pod diagnostico-mongo confirmó host, puerto, usuario, authSource y coincidencia de contraseña; DNS y TCP correctos; Conexion autenticada: OK.
Se ejecutó solo la inicialización pendiente de MongoDB mediante el Job tienda-mongo-init: Colección e índices preparados y Complete 1/1. No se volvió a cargar el catálogo. El Job tienda-migrate anterior se conserva como Failed por trazabilidad.

## Archivos de evidencia
- error-uri-sanitizado.txt: salida del Pod anterior, con fragmento de credencial oculto.
- conexion-corregida.txt: comprobación autenticada posterior.
- inicializacion-mongo.txt: resultado de preparación de colección e índices.
- estado-jobs.txt: estado final de los Jobs.
- 01-error.html y 02-solucion.html: vistas legibles de los registros anteriores.

Las páginas HTML son vistas de registros reales, no capturas de la terminal original. Las capturas de pantalla no se obtuvieron porque la revisión automática rechazó inspeccionar una ventana ajena a las evidencias y la política del navegador bloqueó abrir archivos locales. No se insertó contenido en los documentos de Google Docs.
