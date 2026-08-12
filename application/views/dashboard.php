<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Dashboard</title>

</head>

<body>

   <?php if($us_rol == 'U'): $tipo = 'Usuario'; else: $tipo = 'Administrador'; endif; ?>

    <h1>
        Bienvenido <?= htmlspecialchars($tipo); ?> <?= htmlspecialchars($us_nombre); ?>
    </h1>

    <p>
        Correo:
        <?= htmlspecialchars($us_correo); ?>
    </p>

    <a href="<?= base_url('usuarios'); ?>">
        Ver usuarios
    </a>

    <a href="<?= base_url('logout'); ?>">
        Cerrar sesión
    </a>

</body>

</html>