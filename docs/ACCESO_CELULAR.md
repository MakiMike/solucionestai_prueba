# Acceso HTTPS desde el telefono

Acceso LAN reactivado a peticion del usuario para navegar la tienda completa.
Red verificada: Ethernet, 192.168.100.3. URL del telefono:
https://192.168.100.3:9443/. El nombre local sigue siendo
https://tskull-shirt.test:9443/. Desarrollo y Grafana escuchan solo en 127.0.0.1.

El telefono debe estar en la misma red. Transferir por USB el certificado
publico output/evidencias/acceso-celular/TSkull-Laboratorio-CA.crt e instalarlo
como certificado CA de confianza en el telefono personal. No transferir claves
privadas. No se omite la validacion de certificados en las comprobaciones.
La instalacion de la CA y la prueba externa las realiza el usuario.

Produccion y desarrollo usan backend 0.1.6, frontend 0.1.1. El cambio de backend
admite un alias IPv4 HTTPS solo si HTTP_HOST coincide exactamente con
APP_LAN_HOST. Otros Host conservan APP_BASE_URL; X-Forwarded-Host no determina
la direccion. Diez pruebas verifican los casos permitidos y rechazados.

Configuracion del cluster: proveedor kubernetesCRD habilitado en Traefik;
TLSStore default en tienda-prod referencia tienda-tls (entrada-lan-tls.yaml).
IngressRoute tienda-lan usa Host exacto para la IP actual y frontend:8080.
Ruta y parche APP_LAN_HOST estan guardados en output/evidencias/acceso-celular.
El certificado incluye ambos nombres locales y la IP actual. El firewall
TiendaProd-Https-LAN permite 9443 solo desde LocalSubnet en Ethernet y esa IP;
los otros puertos del laboratorio siguen bloqueados desde LAN.

La escucha de produccion se abrio con:

```powershell
minikube -p tienda-prod kubectl -- port-forward -n kube-system service/traefik 9443:443 --address=127.0.0.1,192.168.100.3
```

Al cambiar de red no reutilizar esta IP: hay que actualizar certificado,
ruta, APP_LAN_HOST, firewall y escucha. No existe una automatizacion permanente.
No ejecutar otro port-forward sobre un puerto que ya este ocupado.

Verificaciones desde la PC: HTTPS /health/ready por IP y nombre local HTTP 200;
login y CSS por IP HTTP 200; formulario y recursos usan la IP al entrar por IP;
desarrollo /health/ready HTTP 200; Grafana /login HTTP 200. Tres nodos Ready,
backend y frontend de produccion 2/2, bases de datos 1/1. Al iniciar fue necesario
reiniciar el DaemonSet calico-node por errores de credenciales CNI antiguas;
su despliegue termino correctamente. El 31/10/2026 el usuario confirmo desde el telefono que produccion permite navegar y desarrollo no conecta. Es una confirmacion manual, sin captura nueva adjunta.
