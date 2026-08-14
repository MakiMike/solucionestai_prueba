<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth/login';
$route['login'] = 'auth/login';
$route['login/submit'] = 'auth/attempt_login';

$route['register'] = 'auth/register';
$route['register/submit'] = 'auth/create_user';

$route['menu'] = 'layouts/menu';

$route['dashboard'] = 'dashboard/index';

$route['usuarios'] = 'usuarios/index';
$route['usuarios/obtener_usuario'] = 'usuarios/obtener_usuario';


$route['productos'] = 'productos/index';

$route['ventas'] = 'ventas/index';

$route['logout'] = 'auth/logout';

// Rutas de Ropa y Catalogo
$route['Ropa/hombre'] = 'ropa/hombre';
$route['Ropa/mujer'] = 'ropa/mujer';
$route['ropa/hombre'] = 'ropa/hombre';
$route['ropa/mujer'] = 'ropa/mujer';
$route['ProductosAll'] = 'ropa/todos';
$route['ropa/todos'] = 'ropa/todos';
$route['ropa/obtener_catalogo'] = 'ropa/obtener_catalogo';
$route['ropa/detalle/(:num)'] = 'ropa/detalle/$1';

// Rutas de Carrito
$route['Carrito'] = 'carrito/index';
$route['carrito'] = 'carrito/index';
$route['carrito/agregar'] = 'carrito/agregar';
$route['carrito/actualizar'] = 'carrito/actualizar';
$route['carrito/eliminar'] = 'carrito/eliminar';
$route['carrito/vaciar'] = 'carrito/vaciar';
$route['carrito/procesar'] = 'carrito/procesar';

// Rutas de Perfil
$route['Perfil'] = 'perfil/index';
$route['perfil'] = 'perfil/index';
$route['perfil/actualizar'] = 'perfil/actualizar';

// Rutas de Recuperación de Contraseña
$route['recuperar_password'] = 'auth/recuperar_password';
$route['auth/procesar_recuperacion'] = 'auth/procesar_recuperacion';
$route['restablecer_password/(:any)'] = 'auth/restablecer_password/$1';
$route['auth/guardar_nueva_password'] = 'auth/guardar_nueva_password';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


