# Nombre de la tienda y presentacion de pedidos

El nombre local de produccion es https://tskull-shirt.test:9443/.
El certificado incluye tambien tienda-prod.test y el Ingress conserva ese
host como alias compatible. La URL base de produccion utiliza el nuevo nombre.
Los nombres de cluster y namespace siguen siendo tienda-prod para conservar
los recursos, datos, automatizaciones y evidencias existentes.

Preparar-NombreTienda.ps1 agrega solamente el nombre local al archivo hosts
de Windows y requiere administrador. El certificado y su clave permanecen
en k8s/tls, excluido de Git. El Secret TLS se aplica con Minikube.

Este cambio no configura DNS en el telefono ni instala alli la CA del
laboratorio. Para navegar desde otro equipo se debe resolver el nombre hacia
la IP fisica vigente de esta computadora y confiar en la CA publica del
laboratorio. Nunca se comparte la clave privada de la CA. Las reglas de LAN
deben corresponder a la interfaz y direccion actuales; consultar ACCESO-LAN.md.

La imagen backend 0.1.5 conserva el funcionamiento de 0.1.4 y cambia la
presentacion de pedidos. Los clientes ven "Pedido" y su estado, sin el ID
global en encabezados. Buscan por estado; el administrador conserva los
numeros y la busqueda por numero o estado. No se renumeran registros ni se
modifican relaciones, inventario, eventos o autorizacion de pedidos.
Los identificadores tecnicos permanecen en los enlaces y formularios: no
se presentan como folios publicos ni constituyen una medida de seguridad.

Validacion: sintaxis PHP, pruebas existentes de historial y busqueda de
catalogo; comprobacion en navegador de cliente y administrador en desarrollo.
El cliente muestra los pedidos sin numero y el administrador conserva #1 a
#6. Se reutilizaron cuentas y pedidos existentes, sin realizar compras nuevas.
Captura: output/evidencias/cierre-20261027/pedidos-cliente.jpg.

Produccion actualizada a 0.1.5: dos replicas listas; frontend conserva 0.1.1
con dos replicas listas; PostgreSQL y MongoDB listos; tres nodos Ready.
La nueva URL devolvio HTTP 200 con certificado validado. En el navegador
se inicio sesion como cliente, se abrio Mis pedidos y se busco "confirmado";
el pedido existente aparecio sin numero en el encabezado. Captura final:
output/evidencias/cierre-20261027/produccion-pedidos-cliente.jpg.

Al retomar, la interfaz activa cambio a Wi-Fi 10.31.97.30. Los accesos de
esta sesion escuchan solo en 127.0.0.1: produccion 9443 y desarrollo 9444.
El acceso desde telefono en esta nueva red queda pendiente; el resultado
anterior de LAN no debe atribuirse a esta direccion nueva.
