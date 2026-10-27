<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php $this->load->view('partials/csrf'); ?>
</head>

<body>

    <main class="login-shell">
        <section class="login-card">
            <div class="login-copy">
                <h1>Recuperar Contraseña</h1>
                <p class="subtitle">Ingresa tu correo registrado para generar un enlace de restablecimiento (Deberia mandar el link a un correo pero solo se muestra aqui por temas de demostracion y pruebas)</p>
            </div>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert-error">
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert-success">
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>

            <div id="recoveryFeedback" style="display: none;"></div>

            <form id="recoveryForm" class="login-form" method="POST" action="<?= base_url('auth/procesar_recuperacion'); ?>">
                <div class="field-group">
                    <label for="email">Correo Electrónico Registrado</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <button type="submit" id="btnSubmitRecovery" class="login-button">
                    <i class="fa-solid fa-paper-plane"></i> Generar Enlace de Recuperación
                </button>
            </form>

            <div class="link-register">
                <p><a href="<?= base_url('login') ?>"><i class="fa-solid fa-arrow-left"></i> Volver a Iniciar Sesión</a></p>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const recoveryForm = document.getElementById('recoveryForm');
        const btnSubmitRecovery = document.getElementById('btnSubmitRecovery');
        const recoveryFeedback = document.getElementById('recoveryFeedback');

        recoveryForm.addEventListener('submit', function(e) {
            e.preventDefault();

            btnSubmitRecovery.disabled = true;
            btnSubmitRecovery.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
            recoveryFeedback.style.display = 'none';

            const formData = new FormData(recoveryForm);

            fetch('<?= site_url("auth/procesar_recuperacion"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmitRecovery.disabled = false;
                btnSubmitRecovery.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Generar Enlace de Recuperación';

                if (data.success) {
                    recoveryFeedback.className = 'alert-success';
                    recoveryFeedback.innerHTML = `
                        <strong><i class="fa-solid fa-circle-check"></i> Enlace Generado:</strong><br>
                        ${data.message}<br><br>
                        <div style="margin-top: 10px; background: rgba(0,0,0,0.5); padding: 12px; border-radius: 8px; word-break: break-all;">
                            <span style="font-size: 0.82rem; color: #aaa;">Haz clic en el siguiente enlace para restablecer tu contraseña:</span><br><br>
                            <a href="${data.reset_url}" style="color: #4ade80; font-weight: bold; text-decoration: underline;">
                                ${data.reset_url}
                            </a>
                        </div>
                    `;
                    recoveryFeedback.style.display = 'block';
                } else {
                    recoveryFeedback.className = 'alert-error';
                    recoveryFeedback.textContent = data.error || 'Ocurrió un error al procesar la solicitud.';
                    recoveryFeedback.style.display = 'block';
                }
            })
            .catch(err => {
                btnSubmitRecovery.disabled = false;
                btnSubmitRecovery.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Generar Enlace de Recuperación';
                console.error(err);
                recoveryFeedback.className = 'alert-error';
                recoveryFeedback.textContent = 'Error al procesar la solicitud.';
                recoveryFeedback.style.display = 'block';
            });
        });
    });
    </script>

</body>
</html>
