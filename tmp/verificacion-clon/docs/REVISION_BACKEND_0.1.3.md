# Backend 0.1.3: candidato para revision de seguridad

La imagen instalada sigue siendo 0.1.2. Los siguientes cambios requieren
construccion, analisis y validacion en desarrollo antes de promoverse.

## Informe de partida

Informe local output/evidencias/imagenes/backend-cves.txt de la imagen 0.1.2,
identificador mostrado por Scout 561c00f7414f: 276 paquetes, 201 hallazgos
(13 criticos, 12 altos, 10 medios, 155 bajos, 11 sin clasificar).
Son resultados brutos del escaner, no 201 fallos explotables demostrados.

- CVE-2023-32692: Scout la asigna a codeigniter/framework 3.1.13, pero el
  aviso del mantenedor especifica codeigniter4/framework anterior a 4.3.5.
  Se considera no aplicable a esta dependencia CI3 por diferencia de paquete
  y funcionalidad; el informe bruto se conserva sin ocultar el hallazgo.
  Fuente: https://github.com/codeigniter4/CodeIgniter4/security/advisories/GHSA-m6m8-6gq8-c9fj
- El resto de alertas CI3 permanece pendiente de evaluacion individual. No se
  descarta en bloque por haber encontrado una asignacion incorrecta. Cambiar
  el numero de version de Composer a 4 no migra una aplicacion CI3.
- El informe indica una correccion disponible para el hallazgo alto de OpenSSL
  CVE-2026-84782: 3.0.22-1~deb12u2. Su disponibilidad real en los repositorios
  y su presencia en la nueva imagen se confirmaran con la construccion y el
  segundo analisis; no se afirma que ya este corregido.

## Cambios preparados

El Dockerfile mantiene PHP 8.3 y Debian bookworm. La reconstruccion usa --pull
para refrescar la base y --no-cache para ejecutar la actualizacion de paquetes.
Se actualizan los paquetes instalados de esa misma distribucion y se eliminan
dependencias agregadas para compilar extensiones que ya no se necesitan.
Las bibliotecas enlazadas por las extensiones se marcan para conservarlas.
El patron de conservacion de bibliotecas sigue el usado en imagenes oficiales:
https://github.com/docker-library/wordpress/blob/master/latest/php8.3/apache/Dockerfile

Tras la limpieza, la construccion comprueba que carguen pdo_pgsql, pgsql,
mbstring, gd, zip y mongodb. Se mantiene la version fijada del driver MongoDB;
no se cambia de framework, version mayor de PHP ni esquema de datos.

Tambien se corrige un fallo observado directamente en Ropa::obtener_catalogo:
el texto de busqueda se interpolaba sin escape en los filtros ILIKE del conteo
y del listado. Ahora se escapan tanto el literal SQL como los comodines y se
declara ESCAPE '!'. Este arreglo de codigo no tiene por que reducir el conteo
de CVE del escaner: son comprobaciones distintas.

Prueba local tests/catalogo-seguridad.php: ejecuta el controlador real con una
BD sustituida por un capturador de consultas y el escape generico de CI3.
Verifica texto con apostrofos, comodines y cargas SQL en ambos filtros,
incluyendo que no se altere el texto buscado. No conecta a PostgreSQL ni
sustituye las pruebas de integracion del backend en desarrollo.

## Comprobacion pendiente del candidato

```powershell
docker build --pull --no-cache -f docker/php/Dockerfile --target backend -t tienda-backend:0.1.3 .
docker run --rm --network none --read-only tienda-backend:0.1.3 php -v
docker scout cves local://tienda-backend:0.1.3 --output C:\laragon\www\sistema\output\evidencias\imagenes\backend-0.1.3-cves.txt
```

Comparar el informe completo con 0.1.2 antes de actualizar manifiestos. Desplegar
primero en tienda-dev y validar login, catalogo, busquedas, cargas de imagen y
conexiones de datos. La promocion debe conservar el TLS de produccion en el
Deployment y los CronJobs. No sobrescribir la etiqueta 0.1.2.

## Resultado del candidato construido por el usuario

Construccion completada. Ejecucion sin red y con filesystem de solo lectura:
PHP CLI 8.3.36. El analisis backend-0.1.3-cves.txt identifica 90c4948c6bcd:
266 paquetes, 206 MB, 195 hallazgos (13 criticos, 11 altos, 7 medios,
153 bajos y 11 sin clasificar). Frente a 0.1.2 son 10 paquetes y 6 hallazgos
menos. Ninguna CVE nueva aparece en la comparacion de estos dos informes.

Dejan de aparecer CVE-2026-35189, CVE-2026-54872, CVE-2026-75805,
CVE-2026-75806, CVE-2026-77696 y CVE-2026-84782. OpenSSL figura ahora como
3.0.22-1~deb12u2 y no tiene hallazgos altos en este informe.
Los 13 hallazgos criticos brutos de CI3 permanecen. La correccion de busqueda
se valida con las pruebas de codigo, no mediante el numero de CVE.

Scout aviso de un archivo temporal bloqueado al limpiarlo, pero confirmo la
escritura del informe y este se pudo leer y comparar. No se ha eliminado
manualmente el archivo bloqueado.

El usuario desplego 0.1.3 en desarrollo y confirmo login, listado de productos y
las busquedas demo 100 (encuentra la prenda), O'Reilly (sin resultados ni errores)
y limpiar el filtro (vuelve el catalogo). La captura muestra la administracion
en tienda-dev.test:9444/productos. Esto no sustituye las pruebas funcionales
completas pendientes.

Se prepararon los manifiestos de aplicacion y CronJobs de produccion para
0.1.3 conservando DB_SSLMODE=verify-full y los montajes de ambas CA. El cambio
en los archivos aun no confirma el despliegue en el cluster: falta que el
usuario cargue la imagen, aplique y compruebe rollout y salud HTTPS.

Validacion posterior del usuario: rollout de produccion completado; consulta
del Deployment muestra tienda-backend:0.1.3, 2 replicas listas de 2 deseadas,
y /health/ready por HTTPS responde HTTP 200. Esto verifica arranque y readiness;
las pruebas funcionales completas siguen pendientes.
No se promete una imagen sin vulnerabilidades ni se declara aceptado el riesgo
pendiente.
