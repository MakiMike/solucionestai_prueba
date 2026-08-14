<div class="ropa-catalog-page">

    <!-- CABECERA DE LA SECCIÓN (Ancho completo) -->
    <div class="catalog-header">
        <div class="header-titles">
            <span class="catalog-kicker">Catálogo Exclusivo</span>
            <h1><?= isset($title) ? htmlspecialchars($title) : 'Productos'; ?></h1>
        </div>
        
        <!-- BARRA DE BÚSQUEDA AJAX EN TIEMPO REAL -->
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input 
                type="text" 
                id="searchCatalog" 
                placeholder="Buscar por nombre..." 
                autocomplete="off"
            >
            <button type="button" id="clearSearchBtn" class="clear-search-btn" style="display: none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- ESTADO DE CARGA -->
    <div id="catalogLoading" class="catalog-loading" style="display: none;">
        <i class="fa-solid fa-spinner fa-spin"></i>
        <span>Cargando productos...</span>
    </div>

    <!-- CUADRÍCULA DE PRODUCTOS (GRID DE 9 POR PÁGINA) -->
    <div id="productGrid" class="product-grid">
        <!-- Generado dinámicamente mediante AJAX -->
    </div>

    <!-- MENSAJE SIN RESULTADOS -->
    <div id="noResults" class="no-results" style="display: none;">
        <i class="fa-solid fa-box-open"></i>
        <h3>No se encontraron productos</h3>
        <p>Intenta buscar con otro nombre o término de filtro.</p>
    </div>

    <!-- SISTEMA DE PAGINACIÓN -->
    <div id="paginationContainer" class="pagination-container">
        <!-- Paginación dinámica (1, 2, 3... Anterior / Siguiente) -->
    </div>

</div>

<style>
    /* Estilos del Catálogo - Ocupa 100% de la pestaña Main */
    .ropa-catalog-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-bottom: 2rem;
        color: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    .catalog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.2rem;
        margin-bottom: 2.5rem;
        padding: 1.2rem 1.5rem;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 14px;
        width: 100%;
    }

    .catalog-kicker {
        font-size: 0.75rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #b87399;
        display: block;
        margin-bottom: 0.2rem;
    }

    .header-titles h1 {
        margin: 0;
        font-size: clamp(1.5rem, 2.2vw, 2rem);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        font-weight: 800;
    }

    /* Buscador AJAX */
    .search-box {
        position: relative;
        flex: 1;
        max-width: 400px;
        min-width: 250px;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #888888;
        font-size: 0.95rem;
    }

    .search-box input {
        width: 100%;
        padding: 0.75rem 2.5rem 0.75rem 2.6rem;
        background: #111111;
        border: 1px solid #333333;
        border-radius: 25px;
        color: #ffffff;
        font-family: inherit;
        font-size: 0.88rem;
        transition: all 0.25s ease;
        outline: none;
    }

    .search-box input:focus {
        border-color: #9d4a6f;
        box-shadow: 0 0 10px rgba(157, 74, 111, 0.4);
    }

    .clear-search-btn {
        position: absolute;
        right: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #888888;
        cursor: pointer;
        font-size: 1.1rem;
    }

    .catalog-loading {
        text-align: center;
        padding: 4rem;
        font-size: 1.2rem;
        color: #b87399;
    }
    .catalog-loading i {
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    /* Grid de 9 Productos por página cubriendo 100% de main */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin-bottom: 2.5rem;
        width: 100%;
    }

    @media (max-width: 900px) {
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
    }

    @media (max-width: 550px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }

    /* DISEÑO EXACTO DE LAS CARDS (ENLACE A LA VISTA DEDICADA DEL PRODUCTO) */
    .product-card {
        background: #000000;
        border: none;
        border-radius: 0px;
        overflow: hidden;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: transform 0.25s ease, opacity 0.25s ease;
        user-select: none;
        width: 100%;
        text-decoration: none;
        color: #ffffff;
    }

    .product-card:hover {
        transform: translateY(-4px);
        opacity: 0.95;
    }

    /* Contenedor de la Imagen */
    .product-img-wrapper {
        width: 100%;
        aspect-ratio: 1 / 1.05;
        overflow: hidden;
        background: #cccccc;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.2rem;
    }

    .product-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Título del producto centrado (Ej. Legend Of The Keeper T-shirt) */
    .product-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 0.6rem 0;
        line-height: 1.35;
        padding: 0 0.5rem;
        text-align: center;
    }

    /* Precio del producto centrado (Ej. $35.00) */
    .product-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        padding-bottom: 0.5rem;
        text-align: center;
    }

    .no-results {
        text-align: center;
        padding: 5rem 1rem;
        color: #888888;
        width: 100%;
    }
    .no-results i {
        font-size: 3rem;
        color: #9d4a6f;
        margin-bottom: 1rem;
    }

    /* Paginación */
    .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .page-btn {
        min-width: 42px;
        height: 42px;
        padding: 0 0.7rem;
        border: 1px solid #333333;
        background: #000000;
        color: #ffffff;
        border-radius: 8px;
        font-family: inherit;
        font-weight: bold;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .page-btn:hover:not(:disabled) {
        background: #9d4a6f;
        color: #ffffff;
        border-color: #9d4a6f;
    }

    .page-btn.active {
        background: #9d4a6f;
        color: #ffffff;
        border-color: #9d4a6f;
        box-shadow: 0 0 10px rgba(157, 74, 111, 0.5);
    }

    .page-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generoCat = '<?= isset($genero) ? $genero : "TODOS"; ?>';
    let currentPage = 1;
    let searchDebounceTimer = null;

    const productGrid = document.getElementById('productGrid');
    const searchCatalog = document.getElementById('searchCatalog');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const catalogLoading = document.getElementById('catalogLoading');
    const noResults = document.getElementById('noResults');
    const paginationContainer = document.getElementById('paginationContainer');

    const detailUrlBase = '<?= site_url("ropa/detalle/"); ?>';

    // Cargar Catálogo por AJAX
    function fetchCatalog(page = 1) {
        currentPage = page;
        const searchVal = searchCatalog.value.trim();

        catalogLoading.style.display = 'block';
        productGrid.style.display = 'none';
        noResults.style.display = 'none';

        const formData = new FormData();
        formData.append('genero', generoCat);
        formData.append('search', searchVal);
        formData.append('page', page);

        fetch('<?= site_url("ropa/obtener_catalogo"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            catalogLoading.style.display = 'none';

            if (!data.success || !data.productos || data.productos.length === 0) {
                noResults.style.display = 'block';
                paginationContainer.innerHTML = '';
                return;
            }

            productGrid.style.display = 'grid';
            renderProducts(data.productos);
            renderPagination(data.page, data.total_pages);
        })
        .catch(err => {
            catalogLoading.style.display = 'none';
            console.error('Error al cargar catálogo:', err);
        });
    }

    // RENDERIZAR CARDS QUE NAVEGAN DIRECTAMENTE A LA VISTA INDIVIDUAL DEL PRODUCTO
    function renderProducts(productos) {
        productGrid.innerHTML = '';

        productos.forEach(prod => {
            const card = document.createElement('a');
            card.className = 'product-card';
            card.href = detailUrlBase + '/' + prod.pla_id;

            const imgSrc = prod.pla_imagen 
                ? '<?= base_url("uploads/playeras/"); ?>' + prod.pla_imagen 
                : '<?= base_url("assets/img/logo.png"); ?>';

            const precioFmt = parseFloat(prod.pla_precio).toFixed(2);

            card.innerHTML = `
                <div class="product-img-wrapper">
                    <img src="${imgSrc}" alt="${escapeHtml(prod.pla_nombre)}">
                </div>
                <h3 class="product-title">${escapeHtml(prod.pla_nombre)}</h3>
                <div class="product-price">$${precioFmt}</div>
            `;

            productGrid.appendChild(card);
        });
    }

    // Renderizar Paginación (1, 2, 3...)
    function renderPagination(page, totalPages) {
        paginationContainer.innerHTML = '';

        if (totalPages <= 1) return;

        // Botón Anterior
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prevBtn.disabled = (page === 1);
        prevBtn.addEventListener('click', () => fetchCatalog(page - 1));
        paginationContainer.appendChild(prevBtn);

        // Botones de Páginas
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'page-btn' + (i === page ? ' active' : '');
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => fetchCatalog(i));
            paginationContainer.appendChild(pageBtn);
        }

        // Botón Siguiente
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        nextBtn.disabled = (page === totalPages);
        nextBtn.addEventListener('click', () => fetchCatalog(page + 1));
        paginationContainer.appendChild(nextBtn);
    }

    // Filtro AJAX en tiempo real con debounce
    searchCatalog.addEventListener('input', function() {
        clearSearchBtn.style.display = this.value.trim().length > 0 ? 'block' : 'none';
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            fetchCatalog(1);
        }, 300);
    });

    clearSearchBtn.addEventListener('click', function() {
        searchCatalog.value = '';
        clearSearchBtn.style.display = 'none';
        fetchCatalog(1);
    });

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Inicializar catálogo
    fetchCatalog(1);
});
</script>
