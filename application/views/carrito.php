<div class="carrito-page">

    <div class="carrito-header">
        <div class="header-titles">
            <span class="carrito-kicker">Tu Compra</span>
            <h1><i class="fa-solid fa-cart-shopping"></i> Carrito de Compras</h1>
        </div>

        <?php if (!empty($carrito)): ?>
            <button type="button" id="btnVaciarCarrito" class="btn-clear-cart">
                <i class="fa-solid fa-trash-can"></i> Vaciar Carrito
            </button>
        <?php endif; ?>
    </div>

    <div class="demo-notice-box">
        <i class="fa-solid fa-circle-info demo-icon"></i>
        <span><strong>Página de Prueba:</strong> Esta es una simulación de venta en línea. Los productos añadidos al carrito son temporales y no se conservan al cerrar sesión.</span>
    </div>

    
    <div id="cartGlobalFeedback" class="cart-feedback" style="display: none;"></div>

    <?php if (empty($carrito)): ?>

        <div id="emptyCartView" class="empty-cart-box">
            <i class="fa-solid fa-cart-arrow-down empty-icon"></i>
            <h2>Tu carrito está vacío</h2>
            <p>Parece que aún no has agregado playeras a tu compra.</p>
            <a href="<?= base_url('ProductosAll'); ?>" class="btn-explore">
                <i class="fa-solid fa-shirt"></i> Explorar Productos
            </a>
        </div>

    <?php else: ?>

        <div id="cartContentWrapper" class="cart-layout-grid">
            
            <!-- LISTA DE PRODUCTOS -->
            <div class="cart-items-column">
                <div class="table-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Talla</th>
                                <th>Precio U.</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <?php foreach ($carrito as $item): ?>
                                <tr data-key="<?= htmlspecialchars($item['key']); ?>">
                                    <td>
                                        <div class="item-product-info">
                                            <?php 
                                                $imgSrc = !empty($item['imagen']) 
                                                    ? base_url('uploads/playeras/' . $item['imagen']) 
                                                    : base_url('assets/img/logo.png');
                                            ?>
                                            <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($item['nombre']); ?>" class="item-thumb">
                                            <span class="item-name"><?= htmlspecialchars($item['nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-talla"><?= htmlspecialchars($item['talla']); ?></span>
                                    </td>
                                    <td>
                                        <span class="item-price">$<?= number_format($item['precio'], 2); ?></span>
                                    </td>
                                    <td>
                                        <div class="cart-qty-control">
                                            <button type="button" class="btn-qty-mod" data-key="<?= htmlspecialchars($item['key']); ?>" data-delta="-1">-</button>
                                            <span class="qty-val"><?= (int)$item['cantidad']; ?></span>
                                            <button type="button" class="btn-qty-mod" data-key="<?= htmlspecialchars($item['key']); ?>" data-delta="1">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="item-subtotal">$<?= number_format($item['subtotal'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-delete-item" data-key="<?= htmlspecialchars($item['key']); ?>" title="Eliminar producto">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RESUMEN DE LA COMPRA -->
            <div class="cart-summary-column">
                <div class="summary-card">
                    <h3>Resumen del Pedido</h3>

                    <div class="summary-line total-line">
                        <span>Total de la Compra:</span>
                        <strong id="cartTotalVal" class="total-amount">$<?= number_format($subtotal, 2); ?></strong>
                    </div>

                    <button type="button" id="btnComprarPedido" class="btn-checkout">
                        <i class="fa-solid fa-circle-check"></i> Reservar pedido por 30 minutos
                    </button>

                    <a href="<?= base_url('ProductosAll'); ?>" class="btn-continue-shopping">
                        <i class="fa-solid fa-arrow-left"></i> Seguir Comprando
                    </a>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<!-- MODAL DE CONFIRMACIÓN DE COMPRA -->
<div id="purchaseSuccessModal" class="modal-overlay" style="display: none;">
    <div class="modal-card success-card">
        <div class="success-icon-box">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2>¡Pedido reservado!</h2>
        <p id="purchaseSuccessMessage">Tienes 30 minutos para pagar en Mis pedidos.</p>
        <div class="success-actions">
            <a href="<?= base_url('ProductosAll'); ?>" class="btn-explore">Volver a la Tienda</a>
            <?php if ($this->session->userdata('rol') === 'A'): ?>
                <a href="<?= base_url('ventas'); ?>" class="btn-admin-ventas">Ir a Registro de Ventas</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .carrito-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-bottom: 3rem;
        color: #e8e8e8;
        font-family: 'Courier New', monospace;
    }

    .carrito-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, rgba(74, 45, 95, 0.25), rgba(15, 15, 15, 0.95));
        border: 1px solid rgba(157, 74, 111, 0.5);
        border-radius: 18px;
    }

    .carrito-kicker {
        font-size: 0.75rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #b87399;
        display: block;
        margin-bottom: 0.2rem;
    }

    .header-titles h1 {
        margin: 0;
        font-size: clamp(1.6rem, 2.5vw, 2.2rem);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #f5f5f5;
    }

    .demo-notice-box {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 1rem 1.2rem;
        background: rgba(96, 165, 250, 0.12);
        border: 1px solid rgba(96, 165, 250, 0.4);
        border-radius: 12px;
        color: #93c5fd;
        font-size: 0.88rem;
        margin-bottom: 2rem;
        line-height: 1.4;
    }

    .demo-notice-box i {
        font-size: 1.3rem;
        color: #60a5fa;
    }

    .btn-clear-cart {
        background: transparent;
        border: 1px solid rgba(248, 113, 113, 0.6);
        color: #ffd3d3;
        padding: 0.6rem 1.1rem;
        border-radius: 10px;
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        font-weight: bold;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-clear-cart:hover {
        background: rgba(248, 113, 113, 0.15);
        border-color: #f87171;
    }

    .empty-cart-box {
        text-align: center;
        padding: 5rem 1.5rem;
        background: rgba(26, 26, 26, 0.8);
        border: 1px dashed rgba(157, 74, 111, 0.5);
        border-radius: 20px;
    }

    .empty-icon {
        font-size: 4rem;
        color: #9d4a6f;
        margin-bottom: 1.2rem;
    }

    .empty-cart-box h2 {
        font-size: 1.8rem;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .empty-cart-box p {
        color: #b0b0b0;
        margin-bottom: 2rem;
    }

    .btn-explore {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        background: linear-gradient(135deg, #6b3d80, #9d4a6f);
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        border-radius: 12px;
        text-transform: uppercase;
        transition: all 0.25s ease;
    }

    .btn-explore:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(157, 74, 111, 0.4);
    }

    /* Layout Grid */
    .cart-layout-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 2rem;
    }

    @media (max-width: 900px) {
        .cart-layout-grid {
            grid-template-columns: 1fr;
        }
    }

    .table-container {
        background: rgba(26, 26, 26, 0.9);
        border: 1px solid rgba(157, 74, 111, 0.5);
        border-radius: 18px;
        overflow-x: auto;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cart-table thead th {
        background: linear-gradient(180deg, #4a2d5f, #331d3f);
        color: #f1ebf5;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-align: left;
        padding: 1rem 0.9rem;
        border-bottom: 1px solid rgba(157, 74, 111, 0.9);
    }

    .cart-table tbody td {
        padding: 1rem 0.9rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        vertical-align: middle;
    }

    .item-product-info {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .item-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid rgba(157, 74, 111, 0.4);
        background: #111;
    }

    .item-name {
        font-weight: bold;
        color: #ffffff;
        font-size: 0.95rem;
    }

    .badge-talla {
        display: inline-block;
        padding: 0.3em 0.6em;
        background: rgba(107, 61, 128, 0.4);
        border: 1px solid #9d4a6f;
        border-radius: 6px;
        color: #f5f5f5;
        font-weight: bold;
        font-size: 0.82rem;
    }

    .cart-qty-control {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(157, 74, 111, 0.6);
        border-radius: 8px;
        overflow: hidden;
        background: #181818;
    }

    .btn-qty-mod {
        width: 30px;
        height: 30px;
        background: transparent;
        border: none;
        color: #ffffff;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-qty-mod:hover {
        background: #4a2d5f;
    }

    .qty-val {
        width: 32px;
        text-align: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .item-subtotal {
        color: #4ade80;
        font-size: 1.05rem;
    }

    .btn-delete-item {
        background: transparent;
        border: none;
        color: #f87171;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.4rem;
        transition: transform 0.2s;
    }

    .btn-delete-item:hover {
        transform: scale(1.2);
    }

    /* Tarjeta Resumen */
    .summary-card {
        background: rgba(26, 26, 26, 0.9);
        border: 1px solid rgba(157, 74, 111, 0.6);
        border-radius: 18px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }

    .summary-card h3 {
        margin: 0;
        font-size: 1.2rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        border-bottom: 1px solid rgba(157, 74, 111, 0.4);
        padding-bottom: 0.8rem;
    }

    .summary-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        color: #cccccc;
    }

    .total-line {
        font-size: 1.15rem;
        font-weight: bold;
        color: #ffffff;
    }

    .total-amount {
        font-size: 1.6rem;
        color: #4ade80;
    }

    .btn-checkout {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #2e7d32, #4ade80);
        border: none;
        border-radius: 12px;
        color: #052e16;
        font-family: 'Courier New', monospace;
        font-size: 0.95rem;
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

    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(74, 222, 128, 0.4);
    }

    .btn-continue-shopping {
        text-align: center;
        color: #b87399;
        text-decoration: none;
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: bold;
        transition: color 0.2s;
    }

    .btn-continue-shopping:hover {
        color: #ffffff;
    }

    /* Modal Éxito */
    .success-card {
        max-width: 500px;
        text-align: center;
        padding: 2.5rem 1.8rem;
    }

    .success-icon-box {
        font-size: 4rem;
        color: #4ade80;
        margin-bottom: 1rem;
    }

    .success-card h2 {
        color: #ffffff;
        margin-bottom: 0.6rem;
    }

    .success-card p {
        color: #cccccc;
        margin-bottom: 1.8rem;
    }

    .success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-admin-ventas {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        background: transparent;
        border: 1px solid #9d4a6f;
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        border-radius: 12px;
        text-transform: uppercase;
    }

    .cart-feedback {
        padding: 0.9rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: bold;
        text-align: center;
    }
    .cart-feedback.error {
        background: rgba(248, 113, 113, 0.15);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
    }
    .cart-feedback.success {
        background: rgba(74, 222, 128, 0.15);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.4);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const btnVaciarCarrito = document.getElementById('btnVaciarCarrito');
    const btnComprarPedido = document.getElementById('btnComprarPedido');
    const purchaseSuccessModal = document.getElementById('purchaseSuccessModal');
    const purchaseSuccessMessage = document.getElementById('purchaseSuccessMessage');
    const cartGlobalFeedback = document.getElementById('cartGlobalFeedback');

    // Modificar cantidad (+ / -)
    document.querySelectorAll('.btn-qty-mod').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            const delta = parseInt(this.dataset.delta);

            const row = this.closest('tr');
            const qtySpan = row.querySelector('.qty-val');
            let currentQty = parseInt(qtySpan.textContent);
            let newQty = currentQty + delta;

            if (newQty <= 0) {
                deleteCartItem(key);
            } else {
                updateCartQuantity(key, newQty);
            }
        });
    });

    // Eliminar producto individual
    document.querySelectorAll('.btn-delete-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            deleteCartItem(key);
        });
    });

    function updateCartQuantity(key, newQty) {
        const formData = new FormData();
        formData.append('key', key);
        formData.append('cantidad', newQty);

        fetch('<?= site_url("carrito/actualizar"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showGlobalFeedback(data.error || 'No se pudo actualizar.', 'error');
            }
        })
        .catch(err => console.error(err));
    }

    function deleteCartItem(key) {
        const formData = new FormData();
        formData.append('key', key);

        fetch('<?= site_url("carrito/eliminar"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    // Vaciar Carrito
    if (btnVaciarCarrito) {
        btnVaciarCarrito.addEventListener('click', function() {
            if (confirm('¿Está seguro de que desea vaciar todo el carrito?')) {
                fetch('<?= site_url("carrito/vaciar"); ?>', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        });
    }

    // Reservar pedido por 30 minutos
    if (btnComprarPedido) {
        btnComprarPedido.addEventListener('click', function() {
            btnComprarPedido.disabled = true;
            btnComprarPedido.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

            fetch('<?= site_url("carrito/procesar"); ?>', { method: 'POST', body: new URLSearchParams({pedido_token: <?= json_encode($this->session->userdata('pedido_token')); ?>}) })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.assign(data.redirect);
                    purchaseSuccessMessage.textContent = data.message;
                    purchaseSuccessModal.style.display = 'flex';
                    updateCartBadgeCount(0);
                } else {
                    btnComprarPedido.disabled = false;
                    btnComprarPedido.innerHTML = '<i class="fa-solid fa-circle-check"></i> Reservar pedido por 30 minutos';
                    showGlobalFeedback(data.error || 'Ocurrió un error al procesar el pedido.', 'error');
                }
            })
            .catch(err => {
                btnComprarPedido.disabled = false;
                btnComprarPedido.innerHTML = '<i class="fa-solid fa-circle-check"></i> Reservar pedido por 30 minutos';
                console.error(err);
                showGlobalFeedback('Error al procesar la compra.', 'error');
            });
        });
    }

    function showGlobalFeedback(msg, type) {
        cartGlobalFeedback.textContent = msg;
        cartGlobalFeedback.className = 'cart-feedback ' + type;
        cartGlobalFeedback.style.display = 'block';
    }

    function updateCartBadgeCount(count) {
        const cartBadges = document.querySelectorAll('.cart-count-badge');
        cartBadges.forEach(b => {
            b.textContent = count > 0 ? `(${count})` : '';
        });
    }

});
</script>
