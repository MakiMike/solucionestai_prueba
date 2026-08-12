<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios</title>

    <!-- DataTables -->
    <link href="DataTables/datatables.min.css" rel="stylesheet">

  
    <link href="<?= base_url('assets/css/usuarios.css'); ?>" rel="stylesheet">

</head>

<body>

    <div class="container">

        <!-- TÍTULO -->
        <h1 class="page-title">Usuarios Registrados</h1>

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


            ajax: {

                url: '<?= base_url('usuarios/listar'); ?>',

                type: 'POST',

                data: function (d) {
                    d.sexo = document.getElementById('filtroSexo').value;

                }

            },

            columns: [

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

    </script>


</body>

</html>