<div class="product-detail-page">

    <!-- BREADCRUMBS Y BOTÓN VOLVER -->
    <div class="detail-nav-bar">
        <div class="breadcrumbs">
            <a href="<?= base_url('dashboard'); ?>"><i class="fa-solid fa-house"></i> Inicio</a>
            <span class="sep">/</span>
            <a href="<?= base_url('ProductosAll'); ?>">Catálogo</a>
            <span class="sep">/</span>
            <span class="current"><?= htmlspecialchars($producto->pla_nombre); ?></span>
        </div>

        <a href="javascript:history.back()" class="btn-back-catalog">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- MAIN PRODUCT SECTION (2 COLUMNAS) -->
    <div class="product-main-grid">
        
        <!-- COLUMNA IZQUIERDA: IMAGEN PRINCIPAL -->
        <div class="product-image-container">
            <div class="image-wrapper">
                <?php 
                    $imgSrc = !empty($producto->pla_imagen) 
                        ? base_url('uploads/playeras/' . $producto->pla_imagen) 
                        : base_url('assets/img/logo.png');
                ?>
                <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($producto->pla_nombre); ?>" id="mainProductImage">
                <span class="badge-online"><i class="fa-solid fa-vial"></i> Simulación de Venta</span>
            </div>

            <!-- GARANTÍAS RÁPIDAS ABAJO DE LA IMAGEN -->
            <div class="quick-guarantees">
                <div class="guarantee-item">
                    <i class="fa-solid fa-cart-flatbed"></i>
                    <span>Catálogo de Prueba</span>
                </div>
                <div class="guarantee-item">
                    <i class="fa-solid fa-shield"></i>
                    <span>Sin Cargos Reales</span>
                </div>
                <div class="guarantee-item">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Demostración Online</span>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: INFORMACIÓN Y FORMULARIO DE COMPRA -->
        <div class="product-info-container">

            <div class="product-header-info">
                <?php 
                    $generoLabel = ($producto->pla_genero === 'H' || strtolower($producto->pla_genero) === 'hombre') ? 'Ropa de Hombre' : 'Ropa de Mujer';
                ?>
                <span class="category-tag"><i class="fa-solid fa-tag"></i> <?= $generoLabel; ?></span>
                <h1 class="product-title"><?= htmlspecialchars($producto->pla_nombre); ?></h1>
                
                <div class="product-price-box">
                    <span class="price-amount">$<?= number_format($producto->pla_precio, 2); ?></span>
                </div>
            </div>

            <hr class="detail-divider">

            <!-- DESCRIPCIÓN DEL PRODUCTO -->
            <div class="description-section">
                <h3>Descripción del Producto</h3>
                <p><?= !empty($producto->pla_descripcion) ? nl2br(htmlspecialchars($producto->pla_descripcion)) : 'Esta playera cuenta con un diseño único de alta calidad, confeccionada con telas de primera categoría para máximo confort y durabilidad en tu día a día.'; ?></p>
            </div>

            <hr class="detail-divider">

            <!-- FORMULARIO DE SELECCIÓN Y AGREGAR AL CARRITO -->
            <div class="purchase-form">
                
                <!-- SELECTOR DE TALLAS -->
                <div class="form-group">
                    <label for="selectTalla"><i class="fa-solid fa-ruler-horizontal"></i> Selecciona tu Talla:</label>
                    <select id="selectTalla" class="form-select-talla">
                        <option value="">-- Elige una Talla --</option>
                        <?php 
                            $tallaIdMap = array('S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, '2XL' => 5);
                            
                            if (!empty($stocks)): 
                                foreach ($stocks as $st): 
                                    $tName = $st->tal_nombre;
                                    $tStock = (int)$st->pta_stock;
                                    $tId = isset($st->tal_id) ? (int)$st->tal_id : (isset($tallaIdMap[$tName]) ? $tallaIdMap[$tName] : 1);
                        ?>
                                    <option value="<?= $tId; ?>" data-stock="<?= $tStock; ?>" data-nombre="<?= $tName; ?>" <?= $tStock <= 0 ? 'disabled' : ''; ?>>
                                        Talla <?= $tName; ?> <?= $tStock > 0 ? '(Stock: ' . $tStock . ' disponibles)' : '(Agotada)'; ?>
                                    </option>
                        <?php 
                                endforeach; 
                            else:
                                foreach (array('S'=>1, 'M'=>2, 'L'=>3, 'XL'=>4, '2XL'=>5) as $tName => $tId):
                        ?>
                                    <option value="<?= $tId; ?>" data-stock="99" data-nombre="<?= $tName; ?>">
                                        Talla <?= $tName; ?> (Disponible)
                                    </option>
                        <?php 
                                endforeach;
                            endif; 
                        ?>
                    </select>
                    <div id="stockDisplay" class="stock-display-info"></div>
                </div>

                <!-- SELECTOR DE CANTIDAD -->
                <div class="form-group">
                    <label><i class="fa-solid fa-layer-group"></i> Cantidad:</label>
                    <div class="qty-selector">
                        <button type="button" id="btnQtyMinus" class="qty-btn">-</button>
                        <input type="number" id="inputQty" value="1" min="1" max="99" readonly>
                        <button type="button" id="btnQtyPlus" class="qty-btn">+</button>
                    </div>
                </div>

                <!-- FEEDBACK DE ACCIÓN -->
                <div id="cartFeedbackMsg" class="feedback-msg" style="display: none;"></div>

                <!-- BOTÓN ÚNICO: AGREGAR AL CARRITO -->
                <div class="action-buttons-group">
                    <button type="button" id="btnAddToCart" class="btn-primary-cart">
                        <i class="fa-solid fa-cart-shopping"></i> Agregar al Carrito
                    </button>
                </div>

            </div>

        </div>

    </div>

    <!-- BANNER DE INFORMACIÓN DE PRUEBA -->
    <div class="ecommerce-info-banner">
        <div class="info-card">
            <i class="fa-solid fa-laptop-code"></i>
            <div>
                <h4>Simulación de Venta</h4>
                <p>Página de prueba interactiva para simular compras en línea.</p>
            </div>
        </div>
        
        <div class="info-card">
            <i class="fa-solid fa-clipboard-check"></i>
            <div>
                <h4>Registro en Administración</h4>
                <p>Al finalizar la venta, el pedido se registra en la pestaña Ventas del Admin.</p>
            </div>
        </div>

        <div class="info-card">
            <i class="fa-solid fa-user-clock"></i>
            <div>
                <h4>Sesión Temporal</h4>
                <p>Los ítems del carrito son temporales y no se conservan tras cerrar sesión.</p>
            </div>
        </div>
    </div>

</div>

<style>
    .product-detail-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-bottom: 3rem;
        color: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* BREADCRUMBS Y VOLVER */
    .detail-nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem 1.4rem;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 12px;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.88rem;
        color: #aaaaaa;
    }

    .breadcrumbs a {
        color: #b87399;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumbs a:hover {
        color: #ffffff;
    }

    .breadcrumbs .sep {
        color: #444444;
    }

    .breadcrumbs .current {
        color: #ffffff;
        font-weight: bold;
    }

    .btn-back-catalog {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #181818;
        border: 1px solid #333333;
        border-radius: 8px;
        color: #ffffff;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: bold;
        transition: all 0.2s ease;
    }

    .btn-back-catalog:hover {
        background: #9d4a6f;
        border-color: #9d4a6f;
    }

    /* MAIN GRID (IMAGEN / DETALLES) */
    .product-main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
        margin-bottom: 3rem;
        background: #0d0d0d;
        border: 1px solid #222222;
        border-radius: 16px;
        padding: 2.5rem;
    }

    @media (max-width: 900px) {
        .product-main-grid {
            grid-template-columns: 1fr;
            padding: 1.5rem;
            gap: 2rem;
        }
    }

    /* COLUMNA IMAGEN */
    .product-image-container {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .image-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
    }

    .image-wrapper:hover img {
        transform: scale(1.04);
    }

    .badge-online {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(157, 74, 111, 0.9);
        color: #ffffff;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        backdrop-filter: blur(4px);
    }

    .quick-guarantees {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.8rem;
        text-align: center;
    }

    .guarantee-item {
        background: #141414;
        border: 1px solid #222222;
        border-radius: 10px;
        padding: 0.8rem 0.5rem;
        font-size: 0.78rem;
        color: #cccccc;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
    }

    .guarantee-item i {
        color: #9d4a6f;
        font-size: 1.1rem;
    }

    /* COLUMNA INFORMACIÓN Y FORMULARIO */
    .product-info-container {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .category-tag {
        display: inline-block;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #b87399;
        font-weight: bold;
        letter-spacing: 0.08em;
        margin-bottom: 0.3rem;
    }

    .product-title {
        font-size: clamp(1.8rem, 2.5vw, 2.3rem);
        color: #ffffff;
        margin: 0 0 0.8rem 0;
        font-weight: 800;
        line-height: 1.2;
    }

    .product-price-box {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .price-amount {
        font-size: 2.2rem;
        font-weight: 900;
        color: #4ade80;
    }

    .detail-divider {
        border: none;
        border-top: 1px solid #222222;
        margin: 0.5rem 0;
    }

    .description-section h3 {
        margin: 0 0 0.5rem 0;
        font-size: 0.95rem;
        text-transform: uppercase;
        color: #b87399;
        letter-spacing: 0.05em;
    }

    .description-section p {
        margin: 0;
        font-size: 0.95rem;
        color: #dddddd;
        line-height: 1.5;
    }

    /* FORMULARIO DE COMPRA */
    .purchase-form {
        display: flex;
        flex-direction: column;
        gap: 1.4rem;
        background: #141414;
        border: 1px solid #282828;
        padding: 1.5rem;
        border-radius: 14px;
    }

    .form-group label {
        display: block;
        font-size: 0.88rem;
        font-weight: bold;
        color: #ffffff;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .form-select-talla {
        width: 100%;
        padding: 0.85rem 1rem;
        background: #000000;
        border: 1px solid #444444;
        border-radius: 10px;
        color: #ffffff;
        font-family: inherit;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-select-talla:focus {
        border-color: #9d4a6f;
    }

    .stock-display-info {
        font-size: 0.82rem;
        color: #60a5fa;
        margin-top: 0.4rem;
        font-weight: bold;
    }

    .qty-selector {
        display: inline-flex;
        align-items: center;
        background: #000000;
        border: 1px solid #444444;
        border-radius: 10px;
        overflow: hidden;
    }

    .qty-btn {
        width: 44px;
        height: 44px;
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 1.4rem;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }

    .qty-btn:hover {
        background: #4a2d5f;
    }

    .qty-selector input {
        width: 60px;
        height: 44px;
        background: transparent;
        border: none;
        border-left: 1px solid #333333;
        border-right: 1px solid #333333;
        color: #ffffff;
        text-align: center;
        font-family: inherit;
        font-size: 1.1rem;
        font-weight: bold;
    }

    .action-buttons-group {
        display: block;
        width: 100%;
        margin-top: 0.5rem;
    }

    .btn-primary-cart {
        width: 100%;
        padding: 1rem;
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
    }

    .btn-primary-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(157, 74, 111, 0.4);
    }

    .feedback-msg {
        padding: 0.8rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: bold;
        text-align: center;
    }
    .feedback-msg.success {
        background: rgba(74, 222, 128, 0.15);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.4);
    }
    .feedback-msg.error {
        background: rgba(248, 113, 113, 0.15);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
    }

    /* BANNER INFORMATIVO TIENDA EN LÍNEA */
    .ecommerce-info-banner {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 16px;
        padding: 1.8rem;
    }

    @media (max-width: 800px) {
        .ecommerce-info-banner {
            grid-template-columns: 1fr;
        }
    }

    .info-card {
        display: flex;
        align-items: center;
        gap: 1.2rem;
    }

    .info-card i {
        font-size: 2.2rem;
        color: #9d4a6f;
    }

    .info-card h4 {
        margin: 0 0 0.2rem 0;
        font-size: 1rem;
        color: #ffffff;
    }

    .info-card p {
        margin: 0;
        font-size: 0.85rem;
        color: #aaaaaa;
        line-height: 1.35;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const plaId = <?= (int)$producto->pla_id; ?>;
    const selectTalla = document.getElementById('selectTalla');
    const stockDisplay = document.getElementById('stockDisplay');
    const inputQty = document.getElementById('inputQty');
    const btnQtyMinus = document.getElementById('btnQtyMinus');
    const btnQtyPlus = document.getElementById('btnQtyPlus');
    const btnAddToCart = document.getElementById('btnAddToCart');
    const cartFeedbackMsg = document.getElementById('cartFeedbackMsg');

    // Manejo del cambio de Talla
    selectTalla.addEventListener('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
            const stock = parseInt(selectedOpt.dataset.stock);
            if (isNaN(stock) || stock >= 99) {
                stockDisplay.textContent = `Talla seleccionada: ${selectedOpt.dataset.nombre}`;
                inputQty.max = 99;
            } else {
                stockDisplay.textContent = `Existencias disponibles: ${stock} unidades en almacén.`;
                inputQty.max = stock > 0 ? stock : 1;
                if (parseInt(inputQty.value) > stock) {
                    inputQty.value = stock > 0 ? stock : 1;
                }
            }
        } else {
            stockDisplay.textContent = '';
        }
    });

    // Control de cantidad
    btnQtyMinus.addEventListener('click', function() {
        let val = parseInt(inputQty.value);
        if (val > 1) {
            inputQty.value = val - 1;
        }
    });

    btnQtyPlus.addEventListener('click', function() {
        let val = parseInt(inputQty.value);
        const selectedOpt = selectTalla.options[selectTalla.selectedIndex];
        let maxStock = 99;
        if (selectedOpt && selectedOpt.dataset.stock) {
            maxStock = parseInt(selectedOpt.dataset.stock);
            if (isNaN(maxStock)) maxStock = 99;
        }
        if (val < maxStock) {
            inputQty.value = val + 1;
        }
    });

    // Agregar al carrito
    btnAddToCart.addEventListener('click', function() {
        const talId = selectTalla.value;
        const cantidad = inputQty.value;

        if (!talId) {
            showFeedback('Por favor seleccione una talla antes de agregar.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('pla_id', plaId);
        formData.append('tal_id', talId);
        formData.append('cantidad', cantidad);

        fetch('<?= site_url("carrito/agregar"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadgeCount(data.total_items);
                showFeedback(data.message, 'success');
            } else {
                showFeedback(data.error || 'Error al agregar el producto.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showFeedback('Error al procesar la solicitud.', 'error');
        });
    });

    function showFeedback(msg, type) {
        cartFeedbackMsg.textContent = msg;
        cartFeedbackMsg.className = 'feedback-msg ' + type;
        cartFeedbackMsg.style.display = 'block';
    }

    function updateCartBadgeCount(count) {
        const cartBadges = document.querySelectorAll('.cart-count-badge');
        cartBadges.forEach(b => {
            b.textContent = count > 0 ? `(${count})` : '';
        });
    }
});
</script>
