<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Inicio de sesion</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">

    <style type="text/css">

    </style>

</head>



<body>

    <h1>Iniciar Sesión</h1>

    <?php if ($this->session->flashdata('error')): ?>

        <p style="color:red;">
            <?= $this->session->flashdata('error'); ?>
        </p>

    <?php endif; ?>


    <form
        method="POST"
        action="<?= base_url('login/submit'); ?>"
    >

        <div>

            <label>
                Correo
            </label>

            <input
                type="email"
                name="email"
                required
            >

        </div>


        <div>

            <label>
                Contraseña
            </label>

            <input
                type="password"
                name="password"
                required
            >

        </div>


        <button type="submit">
            Iniciar sesión
        </button>

    </form>

</body>

</html>