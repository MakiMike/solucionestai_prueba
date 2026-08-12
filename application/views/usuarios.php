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

    </script>


</body>

</html>