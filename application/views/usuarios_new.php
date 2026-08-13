<div class="usuarios-page">
    <div class="page-header">
        <div>
            <p class="page-kicker">Panel administrativo</p>
            <h1>Usuarios</h1>
        </div>
    </div>

    <section class="actions-section">
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

    <div class="table-container">
        <table id="usuariosTable">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Sexo</th>
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

    .actions-section {
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

    @media (max-width: 768px) {
        .actions-section {
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
            { data: 'us_sexo' }
        ],
        pageLength: 4,
        lengthMenu: [
            [4, 10, 20, 50],
            [4, 10, 20, 50]
        ],
        order: [[0, 'asc'], [2, 'asc']]
    });

    document.getElementById('filtroSexo').addEventListener('change', function () {
        table.ajax.reload();
    });

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
