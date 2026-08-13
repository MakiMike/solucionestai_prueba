<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios</title>

    <!-- DataTables -->
    <link href="DataTables/datatables.min.css" rel="stylesheet">

    <link href="<?= base_url('assets/css/usuarios.css'); ?>" rel="stylesheet">

    <!-- Estilos de Botones -->
    <style>
        /* ===== SECCIÓN DE ACCIONES (BOTONES) ===== */
        .actions-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: flex-start;
        }

        .action-button {
            padding: 0.75rem 1.5rem;
            border: 2px solid;
            background-color: transparent;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: 'Courier New', monospace;
            width: auto;
            min-width: 180px;
        }

        .action-button:hover {
            transform: translateY(-2px);
        }

        /* Botón Excel - Verde */
        #exportExcel {
            border-color: #2d7a3f;
            color: #4ade80;
        }

        #exportExcel:hover {
            background-color: rgba(45, 122, 63, 0.2);
            border-color: #4ade80;
            color: #86efac;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.3);
        }

        #exportExcel:active {
            background-color: rgba(45, 122, 63, 0.4);
        }

        /* Botón PDF - Rojo */
        #exportPDF {
            border-color: #7a2d2d;
            color: #f87171;
        }

        #exportPDF:hover {
            background-color: rgba(122, 45, 45, 0.2);
            border-color: #f87171;
            color: #fca5a5;
            box-shadow: 0 0 15px rgba(248, 113, 113, 0.3);
        }

        #exportPDF:active {
            background-color: rgba(122, 45, 45, 0.4);
        }

        /* Botón Machote - Azul */
        #Machote {
            border-color: #1e3a8a;
            color: #3b82f6;
        }

        #Machote:hover {
            background-color: rgba(30, 58, 138, 0.2);
            border-color: #3b82f6;
            color: #60a5fa;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }

        #Machote:active {
            background-color: rgba(30, 58, 138, 0.4);
        }

        /* ===== SECCIÓN DE IMPORTACIÓN ===== */
        .import-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem;
            background: #1a1a1a;
            border: 1px solid #404040;
            border-left: 3px solid #f97316;
        }

        .import-input-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        #importFile {
            display: none;
        }

        .import-label {
            padding: 0.75rem 1.5rem;
            border: 2px solid #78350f;
            background-color: transparent;
            color: #f97316;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: 'Courier New', monospace;
            display: inline-block;
            min-width: 180px;
            text-align: center;
        }

        .import-label:hover {
            background-color: rgba(120, 53, 15, 0.2);
            border-color: #f97316;
            color: #fb923c;
            box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);
            transform: translateY(-2px);
        }

        .import-label:active {
            background-color: rgba(120, 53, 15, 0.4);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #e8e8e8;
            font-family: 'Courier New', monospace;
        }

        .file-name {
            color: #f97316;
            font-weight: 500;
            max-width: 300px;
            word-break: break-word;
        }

        /* Botón Importar - Naranja */
        #importar {
            border-color: #78350f;
            color: #f97316;
        }

        #importar:hover {
            background-color: rgba(120, 53, 15, 0.2);
            border-color: #f97316;
            color: #fb923c;
            box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);
        }

        #importar:active {
            background-color: rgba(120, 53, 15, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .actions-section {
                flex-direction: column;
            }

            .import-section {
                width: 100%;
            }

            .import-input-group {
                flex-direction: column;
            }

            .import-label {
                width: 100%;
                min-width: auto;
            }

            #importar {
                width: 100%;
            }
        }
    </style>

</head>

<body>

    <div class="container">

        <!-- TÍTULO -->
        <h1 class="page-title">Usuarios Registrados</h1>

        <section class="actions-section">
            <button id="exportExcel" class="action-button">
                Exportar a Excel
            </button>

            <button id="exportPDF" class="action-button">
                Exportar a PDF
            </button>

            <button id="Machote" class="action-button">
                Generar Plantilla
            </button>

            <!-- SECCIÓN DE IMPORTACIÓN -->
            <section class="import-section">
                <div class="import-input-group">
                    <label for="importFile" class="import-label">
                        Seleccionar Excel
                    </label>
                    <input type="file" id="importFile" accept=".xlsx, .xls">
                    <button id="importar" class="action-button">
                        Importar
                    </button>
                </div>
                <div class="file-info">
                    <span>Archivo:</span>
                    <span class="file-name" id="fileName">ninguno seleccionado</span>
                </div>
            </section>

        </section>

        <!-- SECCIÓN DE FILTROS -->
        <section class="filters-section">
            <div class="filter-group">
                <label for="filtroSexo">
                    Filtrar por Sexo:
                </label>

                <select id="filtroSexo">

                    <option value="todos">
                        Todos
                    </option>

                    <option value="H">
                        Hombre
                    </option>

                    <option value="M">
                        Mujer
                    </option>

                    <option value="O">
                        Otro
                    </option>

                </select>
            </div>
        </section>

        <!-- TABLA -->
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



    <script src="DataTables/datatables.min.js"></script>


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
                    orderable: false
                },

                {
                    data: 'us_id'
                },

                {
                    data: 'us_nombre'
                },

                {
                    data: 'us_correo'
                },

                {
                    data: 'us_telefono'
                },

                {
                    data: 'us_sexo'
                }

            ],


            pageLength: 4,


            lengthMenu: [

                [4, 10, 20, 50],

                [4, 10, 20, 50]

            ],


            order: [

                [0, 'asc'],
                [2, 'asc']

            ]

        });

        document
            .getElementById('filtroSexo')
            .addEventListener('change', function () {

                table.ajax.reload();

            });

            // Seleccion de checkbox para exportar a excel o pdf

            //excel

            document.getElementById('exportExcel').addEventListener('click', function () {

                const selectedIds = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value);


               // console.log('Selected IDs for Excel Export:', selectedIds);

                if (selectedIds.length === 0) {

                    alert('Por favor, seleccione al menos un usuario para exportar.');

                    return;

                }

                const url = '<?= base_url('usuarios/exportar_excel'); ?>' + '?ids=' + selectedIds.join(',');

                window.location.href = url;

            });

            //pdf
            document.getElementById('exportPDF').addEventListener('click', function () {

                const selectedIds = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value);

                if (selectedIds.length === 0) {

                    alert('Por favor, seleccione al menos un usuario para exportar.');

                    return;

                }

                //console.log('Selected IDs for PDF Export:', selectedIds);

                const url = '<?= base_url('usuarios/exportar_pdf'); ?>' + '?ids=' + selectedIds.join(',');

                window.location.href = url;

            });

            // Mostrar nombre del archivo seleccionado
            document.getElementById('importFile').addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name || 'ninguno seleccionado';
                document.getElementById('fileName').textContent = fileName;
            });

            //generacion de plantilla machote
            document.getElementById('Machote').addEventListener('click', function () {

                const url = '<?= base_url('usuarios/plantilla_machote'); ?>';

                window.location.href = url;

            });

            //importacion de archivo excel
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


</body>

</html>