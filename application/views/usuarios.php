<div class="usuarios-page">
    
    <section class="action-section">
        <button id="exportExcel" class="action-button success">
            <i class="fa-solid fa-file-excel"></i>
            Exportar a Excel
        </button>

        <button id="exportPDF" class="action-button danger">
            <i class="fa-solid fa-file-pdf"></i>
            Exportar a PDF
        </button>

        <button id="Machote" class="action-button info">
            <i class="fa-solid fa-file-arrow-down"></i>
            Generar Plantilla
        </button>

        <section class="import-section">
            <div class="import-input-group">
                <label for="importFile" class="import-label">
                    <i class="fa-solid fa-upload"></i>
                    Seleccionar Excel
                </label>
                <input type="file" id="importFile" accept=".xlsx, .xls">
                <button id="importar" class="action-button warning">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Importar
                </button>
            </div>
            <div class="file-info">
                <span>Archivo:</span>
                <span class="file-name" id="fileName">ninguno seleccionado</span>
            </div>
        </section>
    </section>

    <section class="filters-section">
        <div class="filter-group">
            <label for="filtroSexo">Filtrar por Sexo:</label>
            <select id="filtroSexo">
                <option value="todos">Todos</option>
                <option value="H">Hombre</option>
                <option value="M">Mujer</option>
                <option value="O">Otro</option>
            </select>
        </div>
    </section>

    <!--MODAL PARA EDITAR USUARIO-->
    <div id="modalEditarUsuario" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-pen"></i> Editar Usuario</h2>
                <button type="button" id="cerrarModalEditar" class="modal-close-btn"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div id="modalFeedback" style="display:none; margin-bottom: 1rem;"></div>

            <form id="formEditarUsuario" autocomplete="off">
                <input type="hidden" id="us_id" name="us_id">

                <div class="modal-form-grid">
                    <div class="modal-field">
                        <label for="us_nombre">Nombre</label>
                        <input type="text" id="us_nombre" name="us_nombre" placeholder="Nombre completo" required>
                    </div>
                    <div class="modal-field">
                        <label for="us_correo">Correo</label>
                        <input type="email" id="us_correo" name="us_correo" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div class="modal-field">
                        <label for="us_telefono">Teléfono <span style="color:#888;font-size:0.78rem;">(opcional)</span></label>
                        <input type="text" id="us_telefono" name="us_telefono" placeholder="10 dígitos">
                    </div>
                    <div class="modal-field">
                        <label for="us_sexo">Sexo</label>
                        <select id="us_sexo" name="us_sexo">
                            <option value="">Seleccione...</option>
                            <option value="H">Hombre</option>
                            <option value="M">Mujer</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="us_rol">Rol</label>
                        <select id="us_rol" name="us_rol">
                            <option value="U">Usuario</option>
                            <option value="A">Administrador</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="us_contrasenia">Nueva Contraseña <span style="color:#888;font-size:0.78rem;">(dejar vacío para no cambiar)</span></label>
                        <input type="password" id="us_contrasenia" name="us_contrasenia" placeholder="••••••••" autocomplete="new-password">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" id="cerrarModalEditar2" class="action-button danger">
                        <i class="fa-solid fa-xmark"></i> Cancelar
                    </button>
                    <button type="submit" id="btnGuardarUsuario" class="action-button success">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container" style="overflow-x: auto; width: 100%;">
        <table id="usuariosTable" class="display" white-space: nowrap style="width:100%">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Sexo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<style>
    .usuarios-page {
        max-width: 1300px;
        margin: 0 auto;
        padding: 0.5rem 0 2rem;
        color: #e8e8e8;
        font-family: 'Courier New', monospace;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1.25rem;
        border: 1px solid rgba(157, 74, 111, 0.5);
        background: linear-gradient(135deg, rgba(74, 45, 95, 0.18), rgba(17, 17, 17, 0.9));
        border-radius: 18px;
    }

    .page-kicker {
        margin: 0 0 0.35rem;
        font-size: 0.72rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #b87399;
    }

    .page-header h1 {
        margin: 0;
        font-size: clamp(1.8rem, 2vw, 2.5rem);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #f5f5f5;
    }

    .action-section {
        display: flex;
        align-items: stretch;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1rem 1.25rem;
        background: #171717;
        border: 1px solid rgba(157, 74, 111, 0.4);
        border-left: 4px solid #9d4a6f;
        border-radius: 16px;
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        min-height: 44px;
        border: 1px solid rgba(157, 74, 111, 0.8);
        border-radius: 12px;
        background: transparent;
        color: #f5f5f5;
        padding: 0.8rem 1.1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .action-button.success {
        border-color: rgba(74, 222, 128, 0.7);
        color: #d9ffe5;
    }

    .action-button.danger {
        border-color: rgba(248, 113, 113, 0.7);
        color: #ffd3d3;
    }

    .action-button.info {
        border-color: rgba(96, 165, 250, 0.7);
        color: #dfeeff;
    }

    .action-button.warning {
        border-color: rgba(249, 115, 22, 0.7);
        color: #ffe3c6;
    }

    .action-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(74, 45, 95, 0.28);
    }

    .import-section {
        display: flex;
        flex: 1;
        min-width: min(100%, 420px);
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(249, 115, 22, 0.5);
        border-radius: 12px;
        margin-left: auto;
    }

    .import-input-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .import-label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 0.9rem;
        border: 1px solid rgba(249, 115, 22, 0.7);
        border-radius: 12px;
        background: rgba(249, 115, 22, 0.08);
        color: #ffd7b3;
        cursor: pointer;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.74rem;
    }

    #importFile {
        display: none;
    }

    .file-info {
        color: #d5d5d5;
        font-size: 0.8rem;
        display: flex;
        gap: 0.35rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .file-name {
        color: #f7b267;
        font-weight: 700;
    }

    .filters-section {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        background: rgba(26, 26, 26, 0.96);
        border: 1px solid rgba(157, 74, 111, 0.45);
        border-radius: 16px;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 200px;
    }

    .filter-group label {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #d4d4d4;
    }

    .filter-group select {
        appearance: none;
        -webkit-appearance: none;
        background: #111111;
        color: #f0f0f0;
        min-width: 180px;
        border: 1px solid rgba(157, 74, 111, 0.8);
        border-radius: 10px;
        padding: 0.8rem 2.4rem 0.8rem 0.9rem;
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        background-image: linear-gradient(45deg, transparent 50%, #d0a6c2 50%), linear-gradient(135deg, #d0a6c2 50%, transparent 50%);
        background-position: calc(100% - 18px) calc(1.1em), calc(100% - 12px) calc(1.1em);
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat;
    }

    .table-container {
        background: rgba(17, 17, 17, 0.9);
        border: 1px solid rgba(157, 74, 111, 0.5);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 18px 35px rgba(0, 0, 0, 0.25);
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
        background: #101010;
    }

    .table-container thead th {
        background: linear-gradient(180deg, #4a2d5f, #331d3f);
        color: #f1ebf5;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-align: left;
        padding: 1rem 0.85rem;
        border-bottom: 1px solid rgba(157, 74, 111, 0.9);
    }

    .table-container tbody td {
        padding: 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        color: #e5e5e5;
        vertical-align: middle;
        background: rgba(255, 255, 255, 0.01);
    }

    .table-container tbody tr:nth-child(even) td {
        background: rgba(30, 30, 30, 0.9);
    }

    .table-container tbody tr:hover td {
        background: rgba(74, 45, 95, 0.18);
    }

    .select-checkbox {
        accent-color: #9d4a6f;
        width: 18px;
        height: 18px;
    }

    .btn-editar,
    .btn-eliminar {
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.02);
        color: #f5f5f5;
        border-radius: 9px;
        padding: 0.55rem 0.7rem;
        margin-right: 0.45rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Courier New', monospace;
        font-size: 0.78rem;
    }

    .btn-editar:hover {
        background: rgba(96, 165, 250, 0.12);
        border-color: rgba(96, 165, 250, 0.8);
    }

    .btn-eliminar:hover {
        background: rgba(248, 113, 113, 0.12);
        border-color: rgba(248, 113, 113, 0.8);
    }

    @media (max-width: 768px) {
        .action-section {
            flex-direction: column;
            align-items: stretch;
        }

        .import-section {
            margin-left: 0;
            min-width: auto;
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* ── MODAL EDITAR USUARIO ────────────────────────────────────────────── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9000;
        background: rgba(0, 0, 0, 0.72);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-box {
        background: linear-gradient(160deg, #1a1122 0%, #111 100%);
        border: 1px solid rgba(157, 74, 111, 0.55);
        border-radius: 20px;
        width: min(100%, 620px);
        max-height: 90vh;
        overflow-y: auto;
        padding: 2rem;
        box-shadow: 0 24px 64px rgba(0,0,0,0.6);
        animation: modalSlideIn 0.22s ease;
    }

    @keyframes modalSlideIn {
        from { opacity: 0; transform: translateY(-18px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid rgba(157, 74, 111, 0.35);
        padding-bottom: 1rem;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.15rem;
        color: #f0e6ff;
        text-transform: uppercase;
        letter-spacing: 0.07em;
    }

    .modal-close-btn {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        color: #ccc;
        border-radius: 8px;
        width: 34px;
        height: 34px;
        cursor: pointer;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .modal-close-btn:hover {
        background: rgba(248,113,113,0.18);
        border-color: rgba(248,113,113,0.6);
        color: #fca5a5;
    }

    .modal-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem 1.4rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 520px) {
        .modal-form-grid { grid-template-columns: 1fr; }
    }

    .modal-field {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .modal-field label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #b87399;
    }

    .modal-field input,
    .modal-field select {
        width: 100%;
        height: 46px;
        padding: 0 0.9rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        color: #f0f0f0;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .modal-field input:focus,
    .modal-field select:focus {
        border-color: rgba(157, 74, 111, 0.8);
        box-shadow: 0 0 0 3px rgba(157, 74, 111, 0.15);
    }

    .modal-field select option {
        background: #1a1a1a;
        color: #f0f0f0;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.9rem;
        border-top: 1px solid rgba(157,74,111,0.25);
        padding-top: 1.2rem;
        margin-top: 0.5rem;
    }

    .modal-feedback-ok {
        padding: 0.85rem 1rem;
        border-radius: 10px;
        border: 1px solid rgba(74, 222, 128, 0.4);
        background: rgba(74, 222, 128, 0.08);
        color: #4ade80;
        font-size: 0.88rem;
    }

    .modal-feedback-err {
        padding: 0.85rem 1rem;
        border-radius: 10px;
        border: 1px solid rgba(248, 113, 113, 0.4);
        background: rgba(248, 113, 113, 0.08);
        color: #fca5a5;
        font-size: 0.88rem;
    }
</style>

<script src="<?= base_url('DataTables/datatables.min.js'); ?>"></script>
<script>
    const table = new DataTable('#usuariosTable', {
        processing: true,
        serverSide: true,
        paging: true,
        filter: true,
        ajax: {
            url: '<?= base_url('usuarios/listar'); ?>',
            type: 'POST',
            data: function (d) {
                d.sexo = document.getElementById('filtroSexo').value;
            }
        },
        columns: [
            {
                data: null,
                render: function (data, type, row) {
                    return `<input type="checkbox" class="select-checkbox" value="${row.us_id}">`;
                },
                orderable: false,
                searchable: false
            },
            { data: 'us_id' },
            { data: 'us_nombre' },
            { data: 'us_correo' },
            { data: 'us_telefono' },
            { data: 'us_sexo' },
            { data: 'us_id', orderable: false, searchable: false,
                render: function (data, type, row) {
                    return `
                       <button
                        type="button"
                        class="btn-editar"
                        data-id="${row.us_id}">
                        Editar
                    </button>

                    <button
                        type="button"
                        class="btn-eliminar"
                        data-id="${row.us_id}">
                        Eliminar
                    </button>
                    `;
                }
             }
        ],
        pageLength: 5,
        lengthMenu: [
            [5, 10, 20, 50],
            [5, 10, 20, 50]
        ],
        order: [[0, 'asc'], [2, 'asc']]
    });

    document.getElementById('filtroSexo').addEventListener('change', function () {
        table.ajax.reload();
    });

    // ── Helpers del modal ─────────────────────────────────────────────────
    const modal        = document.getElementById('modalEditarUsuario');
    const feedback     = document.getElementById('modalFeedback');
    const formEditar   = document.getElementById('formEditarUsuario');
    const btnGuardar   = document.getElementById('btnGuardarUsuario');

    function abrirModal() {
        modal.classList.add('active');
        feedback.style.display = 'none';
        document.getElementById('us_contrasenia').value = '';
    }

    function cerrarModal() {
        modal.classList.remove('active');
        formEditar.reset();
        feedback.style.display = 'none';
    }

    function mostrarFeedback(msg, esError) {
        feedback.className  = esError ? 'modal-feedback-err' : 'modal-feedback-ok';
        feedback.textContent = msg;
        feedback.style.display = 'block';
    }

    document.getElementById('cerrarModalEditar').addEventListener('click',  cerrarModal);
    document.getElementById('cerrarModalEditar2').addEventListener('click', cerrarModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) cerrarModal();
    });

    // ── Botones de editar y eliminar (delegación) ─────────────────────────
    document.addEventListener('click', function (e) {

        // ── EDITAR ──
        const btnEditar = e.target.closest('.btn-editar');
        if (btnEditar) {
            const userId = btnEditar.getAttribute('data-id');
            if (!userId || userId === 'undefined') {
                alert('Error: no se pudo obtener el ID del usuario.');
                return;
            }

            const fd = new FormData();
            fd.append('us_id', userId);

            fetch('<?= base_url('usuarios/obtener_usuario'); ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || 'No se pudo obtener la información del usuario.');
                    return;
                }
                const u = data.usuario;
                document.getElementById('us_id').value        = u.us_id;
                document.getElementById('us_nombre').value    = u.us_nombre   || '';
                document.getElementById('us_correo').value    = u.us_correo   || '';
                document.getElementById('us_telefono').value  = u.us_telefono || '';
                document.getElementById('us_sexo').value      = (u.us_sexo || '').trim();
                document.getElementById('us_rol').value       = (u.us_rol  || '').trim();
                document.getElementById('us_contrasenia').value = '';
                abrirModal();
            })
            .catch(() => alert('Error de conexión al obtener el usuario.'));
            return;
        }

        // ── ELIMINAR ──
        const btnEliminar = e.target.closest('.btn-eliminar');
        if (btnEliminar) {
            const userId = btnEliminar.getAttribute('data-id');
            if (!userId) return;

            if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) return;

            const fd = new FormData();
            fd.append('us_id', userId);

            fetch('<?= base_url('usuarios/eliminar_usuario'); ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    table.ajax.reload(null, false);
                } else {
                    alert(data.error || 'No se pudo eliminar el usuario.');
                }
            })
            .catch(() => alert('Error de conexión al eliminar el usuario.'));
            return;
        }
    });

    // ── Submit del formulario de editar ───────────────────────────────────
    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        const fd = new FormData(formEditar);

        fetch('<?= base_url('usuarios/actualizar_usuario'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';

            if (data.success) {
                mostrarFeedback('✓ ' + data.message, false);
                table.ajax.reload(null, false);
                setTimeout(cerrarModal, 1400);
            } else {
                mostrarFeedback('✗ ' + (data.error || 'Error al actualizar.'), true);
            }
        })
        .catch(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
            mostrarFeedback('✗ Error de conexión.', true);
        });
    });

    // Exportar a Excel y PDF
    document.getElementById('exportExcel').addEventListener('click', function () {
        const selectedIds = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value);

        if (selectedIds.length === 0) {
            alert('Por favor, seleccione al menos un usuario para exportar.');
            return;
        }

        const url = '<?= base_url('usuarios/exportar_excel'); ?>' + '?ids=' + selectedIds.join(',');
        window.location.href = url;
    });

    document.getElementById('exportPDF').addEventListener('click', function () {
        const selectedIds = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value);

        if (selectedIds.length === 0) {
            alert('Por favor, seleccione al menos un usuario para exportar.');
            return;
        }

        const url = '<?= base_url('usuarios/exportar_pdf'); ?>' + '?ids=' + selectedIds.join(',');
        window.location.href = url;
    });

    document.getElementById('importFile').addEventListener('change', function (e) {
        const fileName = e.target.files[0]?.name || 'ninguno seleccionado';
        document.getElementById('fileName').textContent = fileName;
    });

    document.getElementById('Machote').addEventListener('click', function () {
        const url = '<?= base_url('usuarios/plantilla_machote'); ?>';
        window.location.href = url;
    });

    document.getElementById('importar').addEventListener('click', function () {
        const fileInput = document.getElementById('importFile');

        if (!fileInput.files.length) {
            alert('Por favor, seleccione un archivo Excel para importar.');
            return;
        }

        const formData = new FormData();
        formData.append('excelFile', fileInput.files[0]);

        fetch('<?= base_url('usuarios/importar_excel'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                table.ajax.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error al importar el archivo:', error);
            alert('Ocurrió un error al importar el archivo.');
        });
    });

</script>
