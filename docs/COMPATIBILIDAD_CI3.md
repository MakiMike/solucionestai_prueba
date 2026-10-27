# CodeIgniter 3.1.13 y PHP 8.2/8.3

CI3 inyecta dependencias con propiedades dinamicas. Desde PHP 8.2, crearlas en
clases que no las permiten expresamente genera E_DEPRECATED. En desarrollo esos
avisos se imprimen antes de las cabeceras y afectan cookies y sesiones.

bin/compat-codeigniter.php agrega el atributo nativo AllowDynamicProperties a
las bases CI_URI, CI_Router, CI_Controller, CI_Loader, CI_Model y CI_DB_driver.
El atributo se hereda en los controladores, modelos y drivers derivados. No
se cambia error_reporting ni se ocultan todos los avisos de PHP.

El procedimiento comprueba CI_VERSION 3.1.13 y se detiene si cambia la version
o no encuentra una declaracion esperada. Es idempotente. Composer lo ejecuta
despues de install/update; el Dockerfile lo ejecuta explicitamente porque la
etapa de dependencias usa --no-scripts. Los cambios de vendor no se versionan:
se versiona el procedimiento que los reproduce.

La imagen candidata del backend es tienda-backend:0.1.2. Validar primero en
desarrollo y luego promover la misma imagen a produccion. No reaplicar el Job
de migracion ya completado solamente para actualizar la imagen de la aplicacion.

Fuente del atributo y su herencia:
https://www.php.net/manual/en/class.allowdynamicproperties.php

La prueba local aislada de arranque reprodujo los avisos anteriores y verifico
despues el arranque con E_ALL, la carga de un modelo/vista y los parametros
dinamicos del driver PostgreSQL, sin abrir una conexion de base de datos.
La validacion de navegador con PHP 8.3 corresponde a la imagen en desarrollo.
