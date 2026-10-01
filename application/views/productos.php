<div class="productos-page">
   
    <section class="action-section">
        <button type="button" id="btnNuevaPlayera" class="primary-button">
            <i class="fa-solid fa-plus"></i>
            Nueva playera
        </button>

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
                <span class="file-name" id="fileName">
                    ninguno seleccionado
                </span>
            </div>
        </section>
    </section>


    <section class="filters-section">
        <div class="filter-group">
            <label for="filtroGenero">Ropa para:</label>

            <select id="filtroGenero">
                <option value="todos">Todos</option>
                <option value="H">Hombre</option>
                <option value="M">Mujer</option>
            </select>
        </div>
    </section>


    <!-- MODAL -->
    <div id="modalPlayera" class="modal-playera" style="display:none;">

        <div class="modal-content">

            <div class="modal-header">
                <h2 id="tituloModal">Nueva playera</h2>

                <button
                    type="button"
                    id="btnCerrarModal"
                    class="close-modal">
                    ×
                </button>
            </div>


            <form
                id="formPlayera"
                enctype="multipart/form-data"
                class="playera-form">

                <!-- ID -->
                <input
                    type="hidden"
                    id="pla_id"
                    name="pla_id">


                <div class="form-grid">

                    <!-- NOMBRE -->
                    <div class="field">
                        <label>Nombre</label>

                        <input
                            type="text"
                            name="pla_nombre"
                            id="pla_nombre"
                            required>
                    </div>


                    <!-- GENERO -->
                    <div class="field">
                        <label>Género</label>

                        <select
                            name="pla_genero"
                            id="pla_genero"
                            required>

                            <option value="H">
                                Hombre
                            </option>

                            <option value="M">
                                Mujer
                            </option>

                        </select>
                    </div>


                    <!-- PRECIO -->
                    <div class="field">
                        <label>Precio</label>

                        <input
                            type="number"
                            name="pla_precio"
                            id="pla_precio"
                            step="0.01"
                            min="0"
                            required>
                    </div>


                    <!-- DESCRIPCION -->
                    <div class="field field-full">

                        <label>Descripción</label>

                        <textarea
                            name="pla_descripcion"
                            id="pla_descripcion"></textarea>

                    </div>


                    <!-- IMAGEN -->
                    <div class="field field-full">

                        <label>Imagen</label>

                        <input
                            type="file"
                            name="pla_imagen"
                            id="pla_imagen"
                            accept="image/jpeg,image/png,image/webp">

                    </div>


                    <!-- STOCK -->
                    <h3>Stock por talla</h3>


                    <label>
                        S

                        <input
                            type="number"
                            name="stock_S"
                            id="stock_S"
                            min="0"
                            value="0">
                    </label>


                    <label>
                        M

                        <input
                            type="number"
                            name="stock_M"
                            id="stock_M"
                            min="0"
                            value="0">
                    </label>


                    <label>
                        L

                        <input
                            type="number"
                            name="stock_L"
                            id="stock_L"
                            min="0"
                            value="0">
                    </label>


                    <label>
                        XL

                        <input
                            type="number"
                            name="stock_XL"
                            id="stock_XL"
                            min="0"
                            value="0">
                    </label>


                    <label>
                        2XL

                        <input
                            type="number"
                            name="stock_2XL"
                            id="stock_2XL"
                            min="0"
                            value="0">
                    </label>

                </div>


                <div class="modal-actions">

                    <button
                        type="submit"
                        class="primary-button">
                        Guardar
                    </button>

                    <button
                        type="button"
                        id="btnCerrarModal2"
                        class="action-button secondary">
                        Cancelar
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- TABLA -->
    <div class="table-container" style="overflow-x: auto; width: 100%;">

        <table
            id="productosTable"
            class="display" style="width: 100%; white-space: nowrap;">

            <thead>

                <tr>

                    <th></th>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Género</th>
                    <th>Precio</th>

                    <th>Stock-S</th>
                    <th>Stock-M</th>
                    <th>Stock-L</th>
                    <th>Stock-XL</th>
                    <th>Stock-2XL</th>

                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>
            </tbody>

        </table>

    </div>

    

</div>


<script src="<?= base_url('DataTables/datatables.min.js'); ?>"></script>


<script>

const table = new DataTable('#productosTable', {

    processing: true,

    serverSide: true,

    paging: true,

    filter: true,


    ajax: {

        url: '<?= site_url('productos/listar'); ?>',

        type: 'POST',

        data: function (d) {
            d.genero = document.getElementById('filtroGenero').value;

        }

    },


    columns: [

        {
            data: null,

            render: function (data, type, row) {

                return `
                    <input
                        type="checkbox"
                        class="select-checkbox"
                        value="${row.pla_id}">
                `;

            },

            orderable: false,

            searchable: false
        },


        { data: 'pla_id' },


        {
            data: 'pla_imagen',

            render: function(data) {

                if (!data || data.toString().trim() === '') {
                    return '<span style="color: #9ca3af; font-size: 0.75rem; font-style: italic;">Sin imagen</span>';
                }

                let raw = data.toString().trim();
                let fileName = raw.includes('/') ? raw.split('/').pop() : raw;

                if (!fileName) {
                    return '<span style="color: #9ca3af; font-size: 0.75rem; font-style: italic;">Sin imagen</span>';
                }

                const fullUrl = "<?= base_url('uploads/playeras/'); ?>" + fileName;
                const fallbackSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='50' height='50' viewBox='0 0 50 50'><rect width='50' height='50' fill='%23171717'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%239ca3af' font-size='9' font-family='sans-serif'>No Img</text></svg>";

                return `
                    <img
                        src="${fullUrl}"
                        alt="Playera"
                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(157, 74, 111, 0.5); display: block;"
                        onerror="this.onerror=null; this.src='${fallbackSvg}';">
                `;

            },

            orderable: false,

            searchable: false
        },


        { data: 'pla_nombre' },


        {
            data: 'pla_genero',

            render: function(data) {

                return data === 'H'
                    ? 'Hombre'
                    : (data === 'M'
                        ? 'Mujer'
                        : data);

            }

        },


        { data: 'pla_precio' },


        { data: 'stock_s' },

        { data: 'stock_m' },

        { data: 'stock_l' },

        { data: 'stock_xl' },

        { data: 'stock_2xl' },


        {
            data: 'pla_activa',

            render: function(data) {

                return (data === true || data === 't' || data === '1' || data === 1)
                    ? 'Activa'
                    : 'Inactiva';

            }

        },


        {
            data: 'pla_id',

            orderable: false,

            searchable: false,

            render: function(data, type, row) {

                return `

                    <button
                        type="button"
                        class="btn-editar"
                        data-id="${row.pla_id}">
                        Editar
                    </button>

                    <button
                        type="button"
                        class="btn-eliminar"
                        data-id="${row.pla_id}">
                        Eliminar
                    </button>

                `;

            }

        }

    ],


    pageLength: 10,

    lengthMenu: [10, 25, 50, 100],

    order: [[1, 'asc']]

});


document
    .getElementById('filtroGenero')
    .addEventListener('change', function () {

        table.ajax.reload();

    });


document
    .getElementById('btnNuevaPlayera')
    .addEventListener('click', function () {

        document.getElementById('formPlayera').reset();

        document.getElementById('pla_id').value = '';

        document.getElementById('stock_S').value = 0;
        document.getElementById('stock_M').value = 0;
        document.getElementById('stock_L').value = 0;
        document.getElementById('stock_XL').value = 0;
        document.getElementById('stock_2XL').value = 0;

        document.getElementById('tituloModal').textContent =
            'Nueva playera';

        document.getElementById('modalPlayera').style.display =
            'flex';

    });


document.addEventListener('click', function (e) {
    const btnEditar = e.target.closest('.btn-editar');
    if (btnEditar) {
        const id = btnEditar.getAttribute('data-id');

        if (!id || id === 'undefined') {
            alert('Error: No se pudo obtener el ID del producto seleccionado.');
            return;
        }

        const formData = new FormData();
        formData.append('pla_id', id);

        fetch('<?= site_url('productos/obtener'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP status ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta del servidor no válida (no es JSON): ' + text.substring(0, 150));
                }
            });
        })
        .then(data => {
            if (!data.success) {
                alert(data.error || data.message || 'No se pudo obtener la información de la playera.');
                return;
            }

            const producto = data.producto || data.playera;

            // DATA
            document.getElementById('pla_id').value = producto.pla_id;
            document.getElementById('pla_nombre').value = producto.pla_nombre;
            document.getElementById('pla_genero').value = producto.pla_genero;
            document.getElementById('pla_precio').value = producto.pla_precio;
            document.getElementById('pla_descripcion').value = producto.pla_descripcion || '';

            // STOCK
            if (data.stocks && Array.isArray(data.stocks)) {
                data.stocks.forEach(item => {
                    const inputStock = document.getElementById('stock_' + item.tal_nombre);
                    if (inputStock) {
                        inputStock.value = item.pta_stock;
                    }
                });
            } else {
                document.getElementById('stock_S').value = producto.stock_s || 0;
                document.getElementById('stock_M').value = producto.stock_m || 0;
                document.getElementById('stock_L').value = producto.stock_l || 0;
                document.getElementById('stock_XL').value = producto.stock_xl || 0;
                document.getElementById('stock_2XL').value = producto.stock_2xl || 0;
            }

            // MODAL
            document.getElementById('tituloModal').textContent = 'Editar playera';
            document.getElementById('modalPlayera').style.display = 'flex';
        })
        .catch(error => {
            console.error(error);
            alert('Error: ' + error.message);
        });
    }

    const btnEliminar = e.target.closest('.btn-eliminar');
    if (btnEliminar) {
        const id = btnEliminar.getAttribute('data-id');
        if (confirm('¿Está seguro de que desea desactivar este producto?')) {
            const formData = new FormData();
            formData.append('pla_id', id);

            fetch('<?= site_url('productos/eliminar'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    table.ajax.reload(null, false);
                } else {
                    alert(data.error || 'No se pudo desactivar el producto.');
                }
            })
            .catch(error => {
                console.error('Error al desactivar el producto:', error);
                alert('Ocurrió un error al desactivar el producto.');
            });
        }
    }
});

//Ventana modal cerrar
document
    .getElementById('btnCerrarModal')
    .addEventListener('click', function () {

        document.getElementById('modalPlayera').style.display =
            'none';

    });


document
    .getElementById('btnCerrarModal2')
    .addEventListener('click', function () {

        document.getElementById('modalPlayera').style.display =
            'none';

    });



//EXCEL EXPORT
document
    .getElementById('exportExcel')
    .addEventListener('click', function () {

        const selectedIds =
            Array.from(
                document.querySelectorAll(
                    '.select-checkbox:checked'
                )
            ).map(cb => cb.value);


        if (selectedIds.length === 0) {

            alert(
                'Por favor, seleccione al menos un producto para exportar.'
            );

            return;

        }


        const url =
            '<?= site_url('productos/exportar_excel'); ?>' +
            '?ids=' +
            selectedIds.join(',');


        window.location.href = url;

    });



//PDF EXPORT

document
    .getElementById('exportPDF')
    .addEventListener('click', function () {

        const selectedIds =
            Array.from(
                document.querySelectorAll(
                    '.select-checkbox:checked'
                )
            ).map(cb => cb.value);


        if (selectedIds.length === 0) {

            alert(
                'Por favor, seleccione al menos un producto para exportar.'
            );

            return;

        }


        const url =
            '<?= site_url('productos/exportar_pdf'); ?>' +
            '?ids=' +
            selectedIds.join(',');


        window.location.href = url;

    });



//EXCEL IMPORT
document
    .getElementById('importFile')
    .addEventListener('change', function (e) {

        const fileName =
            e.target.files[0]?.name ||
            'ninguno seleccionado';


        document.getElementById('fileName').textContent =
            fileName;

    });


//MACHOTE
document
    .getElementById('Machote')
    .addEventListener('click', function () {

        const url =
            '<?= site_url('productos/plantilla_machote'); ?>';

        window.location.href = url;

    });



// IMPORTAR EXCEL
document
    .getElementById('importar')
    .addEventListener('click', function () {

        const fileInput =
            document.getElementById('importFile');


        if (!fileInput.files.length) {

            alert(
                'Por favor, seleccione un archivo Excel para importar.'
            );

            return;

        }


        const formData = new FormData();

        formData.append(
            'excelFile',
            fileInput.files[0]
        );


        fetch(
            '<?= site_url('productos/importar_excel'); ?>',
            {
                method: 'POST',
                body: formData
            }
        )

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

            console.error(
                'Error al importar el archivo Excel:',
                error
            );

            alert(
                'Ocurrió un error al importar el archivo Excel.'
            );

        });

    });


// GUARDAR / ACTUALIZAR
document
    .getElementById('formPlayera')
    .addEventListener('submit', function (e) {

        e.preventDefault();


        const formData =
            new FormData(this);


        fetch(
            '<?= site_url('productos/guardar'); ?>',
            {
                method: 'POST',
                body: formData
            }
        )

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                alert(data.message);


                document
                    .getElementById('modalPlayera')
                    .style.display = 'none';


                table.ajax.reload(null, false);

            } else {

                alert(data.error || data.message || 'Error al guardar el producto.');

            }

        })

        .catch(error => {

            console.error(error);

            alert(
                'Ocurrió un error al guardar la playera.'
            );

        });

    });

</script>

<style>
    .productos-page {
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

    .primary-button,
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

    .primary-button {
        background: linear-gradient(135deg, #4a2d5f, #6b3d80);
        border-color: #9d4a6f;
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

    .action-button.secondary {
        border-color: rgba(255, 255, 255, 0.2);
        color: #f5f5f5;
    }

    .primary-button:hover,
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

    .table-container img {
        border-radius: 12px;
        border: 1px solid rgba(157, 74, 111, 0.5);
        object-fit: cover;
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

    .modal-playera {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    .modal-content {
        width: min(700px, 90vw);
        background: #141414;
        border: 1px solid rgba(157, 74, 111, 0.7);
        border-radius: 18px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
        overflow: hidden;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(135deg, rgba(74, 45, 95, 0.35), rgba(17, 17, 17, 0.95));
    }

    .modal-header h2 {
        margin: 0;
        color: #f5f5f5;
        font-size: 1.2rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .close-modal {
        border: none;
        background: transparent;
        color: #f5f5f5;
        font-size: 1.8rem;
        cursor: pointer;
    }

    .playera-form {
        padding: 1.2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .field-full {
        grid-column: 1 / -1;
    }

    .field label {
        color: #d7d7d7;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
    }

    .field input,
    .field textarea,
    .field select {
        width: 100%;
        border: 1px solid rgba(157, 74, 111, 0.7);
        background: #0e0e0e;
        color: #f5f5f5;
        border-radius: 10px;
        padding: 0.8rem 0.9rem;
        font-family: 'Courier New', monospace;
        resize: vertical;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.2rem;
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

        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>