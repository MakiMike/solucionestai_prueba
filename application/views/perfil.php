<div class="perfil-page">

    <!-- CABECERA DEL PERFIL -->
    <div class="perfil-header">
        <div class="header-titles">
            <span class="perfil-kicker"><i class="fa-solid fa-user-gear"></i> Ajustes de Cuenta</span>
            <h1>Mi Perfil</h1>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL DEL FORMULARIO -->
    <div class="perfil-card-container">

        <!-- AVATAR BANNER -->
        <div class="user-avatar-banner">
            <div class="avatar-circle">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-meta">
                <h2><?= htmlspecialchars($usuario->us_nombre); ?></h2>
                <span class="user-email-badge"><?= htmlspecialchars($usuario->us_correo); ?></span>
                <span class="user-role-tag">
                    <i class="fa-solid fa-shield-halved"></i> 
                    <?= $usuario->us_rol === 'A' ? 'Administrador' : 'Usuario Cliente'; ?>
                </span>
            </div>
        </div>

        <hr class="perfil-divider">

        <!-- FEEDBACK ALERT -->
        <div id="perfilFeedback" class="perfil-feedback-msg" style="display: none;"></div>

        <!-- FORMULARIO DE EDICIÓN DE PERFIL -->
        <form id="perfilForm" autocomplete="off">
            
            <div class="perfil-form-grid">
                
                <!-- NOMBRE COMPLETO -->
                <div class="field-group">
                    <label for="us_nombre"><i class="fa-solid fa-signature"></i> Nombre Completo:</label>
                    <input 
                        type="text" 
                        id="us_nombre" 
                        name="us_nombre" 
                        value="<?= htmlspecialchars($usuario->us_nombre); ?>" 
                        required 
                        placeholder="Ingresa tu nombre completo"
                    >
                </div>

                <!-- CORREO ELECTRÓNICO (SOLO LECTURA) -->
                <div class="field-group">
                    <label for="us_correo"><i class="fa-solid fa-envelope"></i> Correo Electrónico:</label>
                    <input 
                        type="email" 
                        id="us_correo" 
                        value="<?= htmlspecialchars($usuario->us_correo); ?>" 
                        readonly 
                        disabled
                        class="input-disabled"
                    >
                    <span class="field-hint"><i class="fa-solid fa-lock"></i> El correo electrónico está vinculado a tu cuenta y no se puede modificar.</span>
                </div>

                <!-- TELÉFONO -->
                <div class="field-group">
                    <label for="us_telefono"><i class="fa-solid fa-phone"></i> Número de Teléfono:</label>
                    <input 
                        type="text" 
                        id="us_telefono" 
                        name="us_telefono" 
                        value="<?= htmlspecialchars($usuario->us_telefono ? $usuario->us_telefono : ''); ?>" 
                        placeholder="Ej. 5512345678 (Opcional)"
                        maxlength="15"
                    >
                    <span class="field-hint">Agrega o actualiza tu teléfono de contacto.</span>
                </div>

                <!-- SEXO / GÉNERO -->
                <div class="field-group">
                    <label for="us_sexo"><i class="fa-solid fa-venus-mars"></i> Sexo / Género:</label>
                    <select id="us_sexo" name="us_sexo" class="select-sexo">
                        <option value="H" <?= (strtoupper($usuario->us_sexo) === 'H' || strtolower($usuario->us_sexo) === 'hombre') ? 'selected' : ''; ?>>
                            Hombre (H)
                        </option>
                        <option value="M" <?= (strtoupper($usuario->us_sexo) === 'M' || strtolower($usuario->us_sexo) === 'mujer') ? 'selected' : ''; ?>>
                            Mujer (M)
                        </option>
                    </select>
                    <span class="field-hint">Utilizado para personalizar las recomendaciones en tu Dashboard.</span>
                </div>

                <!-- CAMBIAR CONTRASEÑA -->
                <div class="field-group full-width">
                    <label for="nueva_password"><i class="fa-solid fa-key"></i> Cambiar Contraseña:</label>
                    <input 
                        type="password" 
                        id="nueva_password" 
                        name="nueva_password" 
                        placeholder="Dejar en blanco si deseas conservar tu contraseña actual"
                        autocomplete="new-password"
                    >
                    <span class="field-hint">Escribe una nueva contraseña solo si deseas cambiarla.</span>
                </div>

            </div>

            <!-- BOTÓN GUARDAR -->
            <div class="form-actions">
                <button type="submit" id="btnGuardarPerfil" class="btn-save-profile">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </div>

        </form>

    </div>

</div>

<style>
    .perfil-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-bottom: 3rem;
        color: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    .perfil-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.2rem 1.5rem;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 14px;
        width: 100%;
    }

    .perfil-kicker {
        font-size: 0.75rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #b87399;
        display: block;
        margin-bottom: 0.2rem;
        font-weight: bold;
    }

    .header-titles h1 {
        margin: 0;
        font-size: clamp(1.5rem, 2.2vw, 2rem);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        font-weight: 800;
    }

    .perfil-card-container {
        background: #0d0d0d;
        border: 1px solid #222222;
        border-radius: 16px;
        padding: 2.5rem;
        width: 100%;
        box-shadow: 0 15px 35px rgba(0,0,0,0.6);
    }

    @media (max-width: 700px) {
        .perfil-card-container {
            padding: 1.5rem;
        }
    }

    /* AVATAR Y DATOS DE CABECERA */
    .user-avatar-banner {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6b3d80, #9d4a6f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #ffffff;
        box-shadow: 0 0 20px rgba(157, 74, 111, 0.4);
        border: 2px solid #b87399;
    }

    .user-meta h2 {
        margin: 0 0 0.3rem 0;
        font-size: 1.6rem;
        color: #ffffff;
        font-weight: 800;
    }

    .user-email-badge {
        display: block;
        font-size: 0.9rem;
        color: #b0b0b0;
        margin-bottom: 0.5rem;
    }

    .user-role-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.3rem 0.8rem;
        background: rgba(157, 74, 111, 0.2);
        border: 1px solid rgba(157, 74, 111, 0.6);
        border-radius: 20px;
        font-size: 0.78rem;
        color: #f5f5f5;
        font-weight: bold;
        text-transform: uppercase;
    }

    .perfil-divider {
        border: none;
        border-top: 1px solid #222222;
        margin: 1.5rem 0 2rem 0;
    }

    /* FORMULARIO GRID */
    .perfil-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.8rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .perfil-form-grid {
            grid-template-columns: 1fr;
            gap: 1.4rem;
        }
    }

    .field-group.full-width {
        grid-column: 1 / -1;
    }

    .field-group label {
        display: block;
        font-size: 0.88rem;
        font-weight: bold;
        color: #ffffff;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .field-group label i {
        color: #9d4a6f;
        margin-right: 0.3rem;
    }

    .field-group input, 
    .field-group select {
        width: 100%;
        padding: 0.85rem 1rem;
        background: #141414;
        border: 1px solid #333333;
        border-radius: 10px;
        color: #ffffff;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.25s ease;
    }

    .field-group input:focus, 
    .field-group select:focus {
        border-color: #9d4a6f;
        box-shadow: 0 0 10px rgba(157, 74, 111, 0.3);
    }

    .field-group input.input-disabled {
        background: #080808;
        border-color: #222222;
        color: #777777;
        cursor: not-allowed;
    }

    .field-hint {
        display: block;
        font-size: 0.78rem;
        color: #888888;
        margin-top: 0.4rem;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
    }

    .btn-save-profile {
        padding: 1rem 2.2rem;
        background: linear-gradient(135deg, #6b3d80, #9d4a6f);
        border: none;
        border-radius: 12px;
        color: #ffffff;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
    }

    .btn-save-profile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(157, 74, 111, 0.5);
    }

    /* FEEDBACK MSG */
    .perfil-feedback-msg {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.8rem;
        font-size: 0.9rem;
        font-weight: bold;
        text-align: center;
    }
    .perfil-feedback-msg.success {
        background: rgba(74, 222, 128, 0.15);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.4);
    }
    .perfil-feedback-msg.error {
        background: rgba(248, 113, 113, 0.15);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const perfilForm = document.getElementById('perfilForm');
    const btnGuardarPerfil = document.getElementById('btnGuardarPerfil');
    const perfilFeedback = document.getElementById('perfilFeedback');

    perfilForm.addEventListener('submit', function(e) {
        e.preventDefault();

        btnGuardarPerfil.disabled = true;
        btnGuardarPerfil.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        perfilFeedback.style.display = 'none';

        const formData = new FormData(perfilForm);

        fetch('<?= site_url("perfil/actualizar"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnGuardarPerfil.disabled = false;
            btnGuardarPerfil.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';

            if (data.success) {
                showFeedback(data.message, 'success');
                // Limpiar campo de nueva contraseña si se ingresó
                document.getElementById('nueva_password').value = '';
                // Actualizar avatar header con nuevo nombre
                const newName = document.getElementById('us_nombre').value.trim();
                if (newName) {
                    const metaH2 = document.querySelector('.user-meta h2');
                    if (metaH2) metaH2.textContent = newName;
                }
            } else {
                showFeedback(data.error || 'Ocurrió un error al actualizar el perfil.', 'error');
            }
        })
        .catch(err => {
            btnGuardarPerfil.disabled = false;
            btnGuardarPerfil.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
            console.error('Error:', err);
            showFeedback('Error al procesar la solicitud.', 'error');
        });
    });

    function showFeedback(msg, type) {
        perfilFeedback.textContent = msg;
        perfilFeedback.className = 'perfil-feedback-msg ' + type;
        perfilFeedback.style.display = 'block';
    }
});
</script>
