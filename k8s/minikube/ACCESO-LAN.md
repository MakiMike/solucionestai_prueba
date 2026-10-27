# Acceso local y acceso de produccion desde LAN

Objetivo del laboratorio: HTTPS de produccion disponible desde la subred local;
desarrollo y herramientas de administracion privados. No publica la tienda en
Internet ni configura el router.

## Estado aplicado

Interfaz fisica activa observada: Ethernet, 192.168.100.3, gateway
192.168.100.1. La configuracion Wi-Fi 192.168.100.17 no estaba activa.

- Produccion: port-forward de Traefik conserva 127.0.0.1:9443 y un proceso
  independiente escucha en 192.168.100.3:9443.
- Desarrollo: el port-forward real continua solo en 127.0.0.1:9444.
- Grafana: solo 127.0.0.1:3000. Prometheus no estaba publicado en 9090.
- TiendaProd-Https-LAN: Allow TCP 9443, LocalAddress=192.168.100.3,
  RemoteAddress=LocalSubnet, InterfaceAlias=Ethernet, todos los perfiles.
- TiendaProd-Bloquear-LAN: Block TCP en la interfaz fisica, conserva 9444,
  3000, 9090, incorpora restauracion 9081 y puertos publicados actuales de
  los nodos Docker. Se retiro 9443 de esta regla: Block prevalece sobre Allow.

No se deshabilito Windows Firewall ni se cambiaron sus perfiles o politicas
generales. No se modificaron credenciales, reglas de Kubernetes, certificados,
dominios ni la configuracion de la aplicacion en este paso.

## Archivos y reversibilidad

Habilitar-ProdLAN.ps1 contiene solo operaciones Windows NetSecurity. Requiere
PowerShell como administrador. Ejecutarlo con los puertos de nodos actuales:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File C:\laragon\www\sistema\k8s\minikube\Habilitar-ProdLAN.ps1 -PuertosProtegidos "LISTA_DE_PUERTOS_ACTUALES"
```

Los puertos se obtienen con docker inspect, como se hizo al preparar el
laboratorio. El script siempre protege 3000,9081,9090,9444 y conserva los
puertos del bloqueo anterior, excluyendo 9443. No ejecuta Docker o Minikube.
Un fallo de aplicacion intenta restaurar el bloqueo de 9443.

Para volver al bloqueo de produccion desde LAN, como administrador:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File C:\laragon\www\sistema\k8s\minikube\Habilitar-ProdLAN.ps1 -Accion Revertir
```

El script usa output/evidencias/seguridad/firewall-antes-prod-lan.json para
restaurar los puertos anteriores y elimina solo la regla Allow de TiendaLab.
No elimina reglas de otros servicios. El bloqueo debe restaurarse antes de
retirar un acceso LAN si se desea mantenerlo cerrado durante la transicion.

## Pruebas

La computadora obtuvo HTTP 200 por HTTPS con certificado validado:

```powershell
curl.exe --silent --show-error --noproxy "*" --ssl-revoke-best-effort --resolve tienda-prod.test:9443:192.168.100.3 --output NUL --write-out "HTTP %{http_code}\n" https://tienda-prod.test:9443/health/ready
```

Esto prueba el servicio en la direccion LAN desde la propia PC, pero no por si
solo la admision de otro dispositivo por el firewall.

Para distinguir bloqueo de firewall de una escucha limitada a loopback, se
inicio Senuelo-Firewall.ps1: servidor HTTP temporal sin datos de la tienda en
192.168.100.3:9444, que se cierra automaticamente a los 600 segundos. Comprueba
que exista una regla Block activa antes de escuchar. Respondio HTTP 200 desde
la propia PC. El verdadero desarrollo permanece en 127.0.0.1 y no se expone.

Desde un telefono en el mismo Wi-Fi, sin VPN ni datos moviles:

1. Abrir https://192.168.100.3:9443/. Una respuesta TLS o aviso de certificado
   indica que el puerto es alcanzable; no demuestra confianza del certificado
   ni funcionamiento completo de la tienda con una URL IP.
2. Mientras el senuelo este activo, abrir http://192.168.100.3:9444/. Se espera
   bloqueo. Si aparece "SERVICIO DE PRUEBA SIN DATOS", revisar las reglas.
3. Registrar ambos resultados y cerrar el senuelo al terminar. No desactivar
   el firewall para la prueba.

No confundir una advertencia de certificado, error DNS o respuesta 404 del
Ingress con un puerto bloqueado. La regla actual de Ingress usa el host
tienda-prod.test y la URL base tambien. La navegacion completa desde el
telefono requiere resolver el nombre correcto y confiar en la CA del
laboratorio. Eso se abordara con el nombre final del sitio; no se debilito TLS
ni se sortearon advertencias del navegador para simular esa validacion.

## Reinicio

El acceso LAN es un port-forward de laboratorio, no un servicio permanente.
Despues de reiniciar, comprobar primero la IPv4 fisica vigente y actualizar
las reglas si cambia. Con el perfil iniciado y los puertos libres, se puede
usar una terminal de produccion con ambas direcciones:

```powershell
minikube -p tienda-prod kubectl -- port-forward -n kube-system service/traefik 9443:443 --address=127.0.0.1,192.168.100.3
```

No iniciarlo sobre direcciones que ya tienen otro port-forward en ese puerto.
Desarrollo y Grafana deben seguir usando --address=127.0.0.1. No modificar
la configuracion de cifrado de Secrets ni reconstruir los clusters para esto.

Resultado externo reportado por el usuario el 27 de octubre de 2026:
produccion en https://192.168.100.3:9443/ respondio "404 NOT FOUND" y
http://192.168.100.3:9444/ no cargo durante la prueba del servicio temporal.
Es un reporte del usuario, sin captura externa adjunta. El 404 confirma que
el puerto de produccion fue alcanzable; no valida la navegacion completa.
La IP y la interfaz deben comprobarse de nuevo al cambiar de red.
Evidencia local: output/evidencias/seguridad/verificacion-firewall-lan.json.
