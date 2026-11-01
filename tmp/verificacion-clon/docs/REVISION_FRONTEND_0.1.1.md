# Frontend 0.1.1: candidato de actualizacion

Imagen de partida tienda-frontend:0.1.0, identificador Scout 8be70e38684f,
87 paquetes y 191 hallazgos: 20 criticos, 71 altos, 59 medios, 15 bajos,
26 sin clasificar. Informe original: output/evidencias/imagenes/frontend-cves.txt.

El informe indica versiones corregidas para numerosos hallazgos en curl,
OpenSSL, Nginx y otras bibliotecas Alpine. Se conserva Nginx unprivileged
1.28-alpine y se prepara una reconstruccion con base refrescada y actualizacion
de paquetes de los repositorios configurados, sin cambiar manualmente de rama
Alpine ni agregar repositorios edge. No se afirma que todas las correcciones
indicadas esten disponibles en esos repositorios hasta medir el candidato.

El Dockerfile usa root solamente para apk upgrade durante la construccion y
regresa a USER 101 antes de ejecutar la imagen. El puerto 8080, configuracion
FastCGI y archivos estaticos permanecen iguales. No se cambian manifiestos ni
la version desplegada hasta revisar el nuevo analisis y probar en desarrollo.

```powershell
docker build --pull --no-cache -f docker/php/Dockerfile --target frontend -t tienda-frontend:0.1.1 .
docker run --rm --network none --read-only tienda-frontend:0.1.1 nginx -v
docker scout cves local://tienda-frontend:0.1.1 --output C:\laragon\www\sistema\output\evidencias\imagenes\frontend-0.1.1-cves.txt
```

Esta actualizacion de frontend se compila con --target frontend; no reconstruye
ni reemplaza tienda-backend:0.1.3. No sobrescribir las etiquetas anteriores.

Fuentes para la estrategia de actualizacion y usuario sin privilegios:
https://wiki.alpinelinux.org/wiki/Alpine_Package_Keeper
https://github.com/nginx/docker-nginx-unprivileged

## Resultado de la construccion y analisis

El usuario construyo 0.1.1 y genero frontend-0.1.1-cves.txt. Scout identifica
4c300cccd3e0, 86 paquetes, 33 MB y 31 hallazgos en 4 paquetes: 4 criticos,
9 altos, 13 medios y 5 bajos. Frente a 0.1.0 desaparecen 160 hallazgos del
conteo bruto; los criticos bajan de 20 a 4 y los altos de 71 a 9.

Nginx sigue reportandose como 1.28.2-r1, con 4 criticos y 8 altos. La
actualizacion de paquetes no ha corregido esos hallazgos. La imagen no se
considera libre de vulnerabilidades y su riesgo residual no se ha aceptado.
Queda evaluar la actualizacion del paquete/base Nginx, ademas de revisar el
resto de hallazgos. No se han suprimido alertas del informe.

El usuario aplico el manifiesto de desarrollo y aporto captura de la pantalla
de productos en tienda-dev.test:9444, con datos y estilos cargados. Esto no
prueba por si solo todas las funciones ni confirma una imagen sin vulnerabilidades.

Se preparo el manifiesto de produccion con frontend 0.1.1. La carga de imagen,
aplicacion, rollout y comprobacion HTTPS en produccion siguen pendientes de
ejecucion por el usuario. Backend se mantiene en 0.1.3 en ambos entornos. La
actualizacion reduce los hallazgos sin resolver los 4 criticos y 9 altos residuales;
no constituye una aceptacion formal de ese riesgo ni una publicacion en Internet.
