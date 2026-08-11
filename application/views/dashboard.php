<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Dashboard</title>

</head>

<body>

    <h1>
        Bienvenido <?= htmlspecialchars($us_nombre); ?>
    </h1>

    <p>
        Correo:
        <?= htmlspecialchars($us_correo); ?>
    </p>

    <a href="<?= base_url('logout'); ?>">
        Cerrar sesión
    </a>

</body>

</html>