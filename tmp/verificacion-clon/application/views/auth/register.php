<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear cuenta</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
<?php $this->load->view('partials/csrf'); ?>
</head>

<body>
    <main class="login-shell">
        <section class="login-card">
            <div class="login-copy">
                <h1>Crear cuenta</h1>
                <p class="subtitle">Únete y se parte del estilo.</p>
            </div>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert-error">
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <form
                class="login-form"
                method="POST"
                action="<?= base_url('register/submit'); ?>"
            >
                <div class="field-group">
                    <label for="nombre">Nombre</label>
                    <input
                        id="nombre"
                        type="text"
                        name="nombre"
                        placeholder="Tu nombre"
                        autocomplete="name"
                        required
                    >
                </div>

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
                    <label for="telefono">Teléfono (Opcional)</label>
                    <input
                        id="telefono"
                        type="tel"
                        name="telefono"
                        placeholder="Tu número de teléfono"
                        autocomplete="tel"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        minlength="10"
                    >
                </div>

                <div class="field-group">
                    <label for="password">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="sexo">Sexo</label>
                    <select id="sexo" name="sexo" required>
                        <option value="">Selecciona tu sexo</option>
                        <option value="H">Masculino</option>
                        <option value="M">Femenino</option>
                        <option value="O">Preferir no decirlo</option>
                    </select>
                </div>

                <button type="submit" class="login-button">
                    Registrarme
                </button>
            </form>

            <div class="link-register">
                <p>¿Ya tienes una cuenta? <a href="<?= base_url('login'); ?>">Inicia sesión</a></p>
            </div>
        </section>
    </main>
</body>

</html>