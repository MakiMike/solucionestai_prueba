<div class="ventas-page">

    <section class="action-section">
        <button id="exportExcel" class="action-button success">
            <i class="fa-solid fa-file-excel"></i>
            Exportar a Excel
        </button>

        <button id="exportPDF" class="action-button danger">
            <i class="fa-solid fa-file-pdf"></i>
            Exportar a PDF
        </button>
    </section>

   
    <div class="table-container" style="overflow-x: auto; width: 100%;">
        <table id="ventasTable" class="display" style="width: 100%; white-space: nowrap;">
            <thead>
                <tr>
                    <th></th>
                    <th>Pedido #</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Cantidad</th>
                    <th>Precio U.</th>
                    <th>Subtotal</th>
                    <th>Estado</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- RESUMEN / TOTAL GENERAL ABAJO DE LA TABLA -->
    <div class="ventas-resumen">
        <div class="resumen-info">
            <i class="fa-solid fa-chart-line"></i>
            <span>Total de Ventas Registradas:</span>
        </div>
        <div class="resumen-monto" id="granTotalVentas">$0.00</div>
    </div>

</div>

<style>
    .ventas-page {
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
        align-items: center;
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

    .action-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(74, 45, 95, 0.28);
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

    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-success {
        background-color: rgba(74, 222, 128, 0.15);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.4);
    }

    .badge-info {
        background-color: rgba(96, 165, 250, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(96, 165, 250, 0.4);
    }

    .badge-danger {
        background-color: rgba(248, 113, 113, 0.15);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
    }

    .badge-secondary {
        background-color: rgba(156, 163, 175, 0.15);
        color: #9ca3af;
        border: 1px solid rgba(156, 163, 175, 0.4);
    }

    .ventas-resumen {
        margin-top: 1.5rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, rgba(74, 45, 95, 0.3), rgba(17, 17, 17, 0.95));
        border: 1px solid rgba(157, 74, 111, 0.6);
        border-left: 5px solid #4ade80;
        border-radius: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .resumen-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #f5f5f5;
    }

    .resumen-info i {
        color: #4ade80;
        font-size: 1.3rem;
    }

    .resumen-monto {
        font-size: 1.6rem;
        font-weight: 800;
        color: #4ade80;
        letter-spacing: 0.05em;
        text-shadow: 0 0 10px rgba(74, 222, 128, 0.3);
    }

    /* Estilos DataTables Oscuros */
    .dt-container .dt-search input,
    .dt-container .dt-length select {
        background: #111111 !important;
        color: #f0f0f0 !important;
        border: 1px solid rgba(157, 74, 111, 0.8) !important;
        border-radius: 8px !important;
        padding: 0.4rem 0.8rem !important;
    }

    .dt-container .dt-info,
    .dt-container .dt-paging {
        color: #e5e5e5 !important;
        padding-top: 1rem !important;
    }

    @media (max-width: 768px) {
        .action-section {
            flex-direction: column;
            align-items: stretch;
        }

        .ventas-resumen {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<script src="<?= base_url('DataTables/datatables.min.js'); ?>"></script>

<script>
const table = new DataTable('#ventasTable', {
    processing: true,
    serverSide: true,
    paging: true,
    filter: true,
    ajax: {
        url: '<?= site_url('ventas/listar'); ?>',
        type: 'POST',
        dataSrc: function (json) {
            if (json.gran_total !== undefined) {
                document.getElementById('granTotalVentas').textContent = '$' + json.gran_total;
            }
            return json.data;
        }
    },
    columns: [
        {
            data: null,
            render: function (data, type, row) {
                return `<input type="checkbox" class="select-checkbox" value="${row.pde_id}">`;
            },
            orderable: false,
            searchable: false
        },
        { data: 'ped_id' },
        {
            data: 'ped_fecha',
            render: function(data) {
                if (!data) return '';
                const date = new Date(data);
                return date.toLocaleString();
            }
        },
        { data: 'us_nombre' },
        { data: 'pla_nombre' },
        { data: 'tal_nombre' },
        { data: 'pde_cantidad' },
        {
            data: 'pde_precio',
            render: function(data) {
                return '$' + parseFloat(data).toFixed(2);
            }
        },
        {
            data: 'subtotal',
            render: function(data) {
                return '<strong style="color: #4ade80;">$' + parseFloat(data).toFixed(2) + '</strong>';
            }
        },
        {
            data: 'ped_estado',
            render: function(data) {
                const estado = (data || '').toLowerCase();
                let badgeClass = 'badge-secondary';
                if (estado === 'pagado' || estado === 'entregado') badgeClass = 'badge-success';
                else if (estado === 'procesando' || estado === 'enviado') badgeClass = 'badge-info';
                else if (estado === 'cancelado') badgeClass = 'badge-danger';
                return `<span class="badge ${badgeClass}">${data}</span>`;
            }
        }
    ],
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'desc']]
});

// EXCEL EXPORT
document.getElementById('exportExcel').addEventListener('click', function () {
    const selectedIds = Array.from(
        document.querySelectorAll('.select-checkbox:checked')
    ).map(cb => cb.value);

    let url = '<?= site_url('ventas/exportar_excel'); ?>';
    if (selectedIds.length > 0) {
        url += '?ids=' + selectedIds.join(',');
    }

    window.location.href = url;
});

// PDF EXPORT
document.getElementById('exportPDF').addEventListener('click', function () {
    const selectedIds = Array.from(
        document.querySelectorAll('.select-checkbox:checked')
    ).map(cb => cb.value);

    let url = '<?= site_url('ventas/exportar_pdf'); ?>';
    if (selectedIds.length > 0) {
        url += '?ids=' + selectedIds.join(',');
    }

    window.location.href = url;
});
</script>
