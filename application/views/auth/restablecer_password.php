<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <main class="login-shell">
        <section class="login-card">
            <div class="login-copy">
                <h1>Restablecer Contraseña</h1>
                <p class="subtitle">Escribe tu nueva contraseña para la cuenta de <?= htmlspecialchars($usuario->us_correo); ?></p>
            </div>

            <div id="resetFeedback" style="display: none;"></div>

            <form id="resetForm" class="login-form" method="POST" action="<?= base_url('auth/guardar_nueva_password'); ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                <div class="field-group">
                    <label for="password">Nueva Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" id="btnSubmitReset" class="login-button">
                    <i class="fa-solid fa-lock"></i> Guardar Nueva Contraseña
                </button>
            </form>

            <div class="link-register">
                <p><a href="<?= base_url('login') ?>"><i class="fa-solid fa-arrow-left"></i> Volver a Iniciar Sesión</a></p>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const resetForm = document.getElementById('resetForm');
        const btnSubmitReset = document.getElementById('btnSubmitReset');
        const resetFeedback = document.getElementById('resetFeedback');

        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;

            if (p1 !== p2) {
                resetFeedback.className = 'alert-error';
                resetFeedback.textContent = 'La nueva contraseña y su confirmación no coinciden.';
                resetFeedback.style.display = 'block';
                return;
            }

            btnSubmitReset.disabled = true;
            btnSubmitReset.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            resetFeedback.style.display = 'none';

            const formData = new FormData(resetForm);

            fetch('<?= site_url("auth/guardar_nueva_password"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmitReset.disabled = false;
                btnSubmitReset.innerHTML = '<i class="fa-solid fa-lock"></i> Guardar Nueva Contraseña';

                if (data.success) {
                    resetFeedback.className = 'alert-success';
                    resetFeedback.innerHTML = `
                        <strong><i class="fa-solid fa-circle-check"></i> ¡Éxito!</strong><br>
                        ${data.message}<br><br>
                        <a href="<?= site_url('login'); ?>" style="display: inline-block; padding: 10px 18px; background: #6b3d80; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; text-transform: uppercase; font-size: 0.85rem;">
                            Iniciar Sesión Ahora
                        </a>
                    `;
                    resetFeedback.style.display = 'block';
                    resetForm.style.display = 'none';
                } else {
                    resetFeedback.className = 'alert-error';
                    resetFeedback.textContent = data.error || 'Ocurrió un error al guardar la contraseña.';
                    resetFeedback.style.display = 'block';
                }
            })
            .catch(err => {
                btnSubmitReset.disabled = false;
                btnSubmitReset.innerHTML = '<i class="fa-solid fa-lock"></i> Guardar Nueva Contraseña';
                console.error(err);
                resetFeedback.className = 'alert-error';
                resetFeedback.textContent = 'Error al procesar la solicitud.';
                resetFeedback.style.display = 'block';
            });
        });
    });
    </script>

</body>
</html>
