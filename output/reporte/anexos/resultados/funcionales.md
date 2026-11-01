# Pruebas funcionales realizadas en el navegador interno

Entorno: https://tienda-dev.test:9444/. Fecha de la sesion: 27 de octubre de 2026.
Se usaron las cuentas ficticias existentes; no se crearon usuarios ni se
cambiaron contrasenas. La clave local de semilla ya no coincide con la clave
efectiva de las cuentas; esto se verifico sin imprimir hashes ni contrasenas.
No se reejecutaron semillas ni migraciones.

## Pedido y sincronizacion

Como administrador, el pedido 2 estaba Confirmado. Se pulso Preparar pedido,
Marcar listo y Registrar entrega, verificando En preparacion, Listo para entrega
y Entregado tras cada accion. El pedido 1 ya estaba En preparacion al iniciar
la prueba y no fue modificado.

Como cliente, el pedido 2 aparece Entregado y su historial muestra reserva_creada,
pago_confirmado y tres eventos estado_cambiado sincronizados automaticamente.

Capturas: 01-pedido-entregado-admin.jpg y 04-historial-entrega-cliente.jpg.

## Permisos

El cliente no tiene enlaces de administracion. Al abrir directamente /usuarios,
/productos y /Ventas, el navegador vuelve a /dashboard. La lista Mis pedidos
del cliente omite los pedidos 3 y 4 del administrador. Abrir
/pedidos?pedido=4 como cliente muestra 404 Page Not Found; el pedido estaba
presente en la lista del administrador, por lo que se comprobo un objeto ajeno
existente, no solamente un identificador inexistente.

No se intentaron modificaciones por peticiones HTTP construidas fuera de la
interfaz. Estas comprobaciones no equivalen a una auditoria completa de todos
los endpoints ni a una prueba de penetracion.

Captura: 03-pedido-ajeno-bloqueado.jpg.

## Edicion limitada

Producto 090, precio 240.00, talla S con 20 disponibles al inicio:

1. Se agregaron tres unidades al carrito. Reservar fue rechazado con:
   "Edicion limitada: maximo dos unidades por cliente, sumando todas las tallas
   y pedidos". El carrito se conservo para corregir la cantidad.
2. Se redujo a dos unidades y Reservar creo el pedido 6 Pendiente de pago,
   total 480.00. La ficha paso a mostrar S=18 y M=20.
3. En otro carrito se agrego una unidad M del mismo producto. Reservar volvio
   a rechazarla por el limite acumulado entre tallas y pedidos.
4. Se cancelo el pedido temporal 6 sin pagarlo. La ficha volvio a S=20, M=20.
5. Se vacio el carrito desechable de la prueba y se verifico "Tu carrito esta vacio".

No se eliminaron pedidos ni se hicieron pagos reales. El pedido 6 cancelado
permanece como registro de prueba en desarrollo.

Captura principal: 02-edicion-limitada-rechazo.jpg (carrito con tres unidades S).

## Comprobacion de produccion

Consulta de Kubernetes: backend tienda-backend:0.1.4, 2 listas/2 deseadas;
frontend tienda-frontend:0.1.1, 2 listas/2 deseadas. CronJob
sincronizar-historial usa backend 0.1.4 y registra ultimo exito
2026-10-27T06:37:04Z. Evidencias: versiones-produccion.txt y cron-produccion.txt.
No se crearon compras ni se altero stock de produccion durante estas pruebas.

Verificacion posterior por SQL de solo lectura en desarrollo:
verificacion-final-dev.txt. No se guardaron credenciales ni datos privados.

## Alcance pendiente

Estas pruebas cierran edicion limitada, permisos por la interfaz y avance del
pedido. No completan por si solas el firewall desde otro equipo, una medicion
integra de RTO, la evaluacion de vulnerabilidades residuales, los cambios de
nombre/referencia publica ni el cierre de Git, diagramas y documento final.

Las capturas son imagenes reales del navegador interno, sin modificar el
contenido para presentarlo como resultado. El viewport del panel es reducido;
se desplazaron las paginas para encuadrar la evidencia, sin cambiar sus estilos.
