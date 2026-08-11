<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio de sesion</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">

</head>



<body>

    <main class="login-shell">
        <section class="login-card">
            <div class="login-copy">
                <h1>Iniciar sesión</h1>
                <p class="subtitle">El estilo alternativo es para ti</p>
            </div>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert-error">
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <form
                class="login-form"
                method="POST"
                action="<?= base_url('login/submit'); ?>"
            >
                <div class="field-group">
                    <label for="email">Correo</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="password">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">
                    Iniciar sesión
                </button>
            </form>

                <div class="link-register">
                    <p>¿No tienes una cuenta? <a href="<?= base_url('register') ?>">Regístrate</a></p>
                </div>

        </section>
          
    </main>

</body>

</html>