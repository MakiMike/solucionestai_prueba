<?php
// Solo datos sintéticos. Nunca se ejecuta automáticamente al iniciar una réplica.
if (!getenv('SEED_ADMIN_PASSWORD') || !getenv('SEED_CLIENT_PASSWORD')) throw new RuntimeException('Define las contraseñas sintéticas mediante variables de entorno.');
$db->beginTransaction();
try {
    foreach (array(array('admin@tienda.test','Administrador de prueba','A',getenv('SEED_ADMIN_PASSWORD')),array('cliente@tienda.test','Cliente de prueba','U',getenv('SEED_CLIENT_PASSWORD'))) as $u) {
        $q = $db->prepare('INSERT INTO us_usuarios(us_nombre,us_correo,us_rol,us_contrasenia) VALUES (?,?,?,?) ON CONFLICT(us_correo) DO NOTHING');
        $q->execute(array($u[1],$u[0],$u[2],password_hash($u[3],PASSWORD_DEFAULT)));
    }
    foreach (array('S','M','L','XL','2XL') as $i=>$name) {
        $q=$db->prepare('INSERT INTO tallas(tal_nombre,tal_orden) VALUES (?,?) ON CONFLICT(tal_nombre) DO NOTHING'); $q->execute(array($name,$i));
    }
    for ($i=1;$i<=100;$i++) {
        $name='Colección alternativa demo '.str_pad($i,3,'0',STR_PAD_LEFT);
        $q=$db->prepare('SELECT pla_id FROM playeras WHERE pla_nombre=?'); $q->execute(array($name));
        $id=$q->fetchColumn();
        if (!$id) {
            $q=$db->prepare('INSERT INTO playeras(pla_nombre,pla_descripcion,pla_genero,pla_precio,pla_edicion_limitada) VALUES (?,?,?,?,?) RETURNING pla_id');
            $q->execute(array($name,'Prenda sintética de estilo alternativo para pruebas de catálogo.', $i%2?'H':'M', 150+$i, $i%10===0?'true':'false')); $id=$q->fetchColumn();
        }
        foreach ($db->query('SELECT tal_id FROM tallas') as $t) {
            $q=$db->prepare('INSERT INTO playera_tallas(pla_id,tal_id,pta_stock) VALUES (?,?,20) ON CONFLICT(pla_id,tal_id) DO NOTHING'); $q->execute(array($id,$t['tal_id']));
        }
    }
    $db->commit(); echo 'Semilla lista: 100 prendas demo y cuentas ficticias. No se restablecen existencias existentes.' . PHP_EOL;
} catch (Throwable $e) { $db->rollBack(); throw $e; }
