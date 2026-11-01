<div class="dashboard-page">

    <div class="welcome-header">
        <div class="welcome-titles">
            <?php 
                $saludo = ($us_sexo === 'M' || strtolower($us_sexo) === 'mujer') ? 'Bienvenida' : 'Bienvenido';
            ?>
            <span class="welcome-kicker"><i class="fa-solid fa-skull"></i> Tienda de Cultura Alternativa</span>
            <h1><?= $saludo; ?>, <span class="user-highlight"><?= htmlspecialchars($us_nombre); ?></span></h1>
            <p class="alt-random-quote">
                <i class="fa-solid fa-quote-left"></i> 
                <?= htmlspecialchars($mensaje_random); ?> 
                <i class="fa-solid fa-quote-right"></i>
            </p>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-title-bar">
            <h2><i class="fa-solid fa-fire-flame-curved"></i> Playeras con Mayor Inventario</h2>
            <span class="section-subtitle">
                Recomendadas para ti según tu catálogo de preferencia
            </span>
        </div>

        <?php if (!empty($productos_top3)): ?>
            <div class="product-grid-3">
                <?php foreach ($productos_top3 as $prod): ?>
                    <?php 
                        $imgSrc = !empty($prod->pla_imagen) 
                            ? base_url('uploads/playeras/' . $prod->pla_imagen) 
                            : base_url('assets/img/logo.png');
                    ?>
                    <a href="<?= base_url('ropa/detalle/' . $prod->pla_id); ?>" class="product-card">
                        <div class="product-img-wrapper">
                            <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($prod->pla_nombre); ?>">
                        </div>
                        <h3 class="product-title"><?= htmlspecialchars($prod->pla_nombre); ?></h3>
                        <div class="product-price">$<?= number_format($prod->pla_precio, 2); ?></div>
                        <div class="stock-badge">
                            <i class="fa-solid fa-boxes-stacked"></i> Stock: <?= (int)$prod->stock_total; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card">No se encontraron productos disponibles.</div>
        <?php endif; ?>
    </div>

    <div class="fullwidth-alt-banner">
        <div class="banner-background">
            <img src="<?= base_url('assets/img/login-bg1.jpg'); ?>" alt="Fondo Alternativo" class="banner-bg-img">
        </div>
        <div class="banner-blur-overlay">
            <div class="banner-content">
                <i class="fa-solid fa-shirt banner-icon"></i>
                <h2>Vendemos playeras alternativa para todos los tipos de estilo alt</h2>
                <p>Moda gótica, punk, emo, y2k, grunge y underground con cortes exclusivos y telas de primera calidad.</p>
                <a href="<?= base_url('ProductosAll'); ?>" class="btn-banner-explore">
                    <i class="fa-solid fa-compass"></i> Explorar Todo el Catálogo
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-title-bar">
            <h2><i class="fa-solid fa-layer-group"></i> Más Opciones Destacadas</h2>
            <span class="section-subtitle">
                Completa tu estilo con nuestros diseños más solicitados
            </span>
        </div>

        <?php if (!empty($productos_next3)): ?>
            <div class="product-grid-3">
                <?php foreach ($productos_next3 as $prod): ?>
                    <?php 
                        $imgSrc = !empty($prod->pla_imagen) 
                            ? base_url('uploads/playeras/' . $prod->pla_imagen) 
                            : base_url('assets/img/logo.png');
                    ?>
                    <a href="<?= base_url('ropa/detalle/' . $prod->pla_id); ?>" class="product-card">
                        <div class="product-img-wrapper">
                            <img src="<?= $imgSrc; ?>" alt="<?= htmlspecialchars($prod->pla_nombre); ?>">
                        </div>
                        <h3 class="product-title"><?= htmlspecialchars($prod->pla_nombre); ?></h3>
                        <div class="product-price">$<?= number_format($prod->pla_precio, 2); ?></div>
                        <div class="stock-badge">
                            <i class="fa-solid fa-boxes-stacked"></i> Stock: <?= (int)$prod->stock_total; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card">Explora nuestro catálogo para descubrir más estilos.</div>
        <?php endif; ?>
    </div>

</div>

<style>
    .dashboard-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding-bottom: 3rem;
        color: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    .welcome-header {
        margin-bottom: 2.5rem;
        padding: 1.8rem 2rem;
        background: #000000;
        border: 1px solid #222222;
        border-radius: 16px;
        width: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    .welcome-kicker {
        font-size: 0.78rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #b87399;
        display: block;
        margin-bottom: 0.4rem;
        font-weight: bold;
    }

    .welcome-titles h1 {
        margin: 0 0 0.8rem 0;
        font-size: clamp(1.8rem, 2.5vw, 2.4rem);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #ffffff;
        font-weight: 800;
    }

    .user-highlight {
        color: #9d4a6f;
        background: linear-gradient(135deg, #b87399, #9d4a6f);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .alt-random-quote {
        margin: 0;
        font-size: 1rem;
        font-style: italic;
        color: #cccccc;
        line-height: 1.4;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alt-random-quote i {
        color: #9d4a6f;
        font-size: 0.9rem;
    }

    .dashboard-section {
        margin-bottom: 3rem;
        width: 100%;
    }

    .section-title-bar {
        margin-bottom: 1.5rem;
    }

    .section-title-bar h2 {
        margin: 0 0 0.3rem 0;
        font-size: 1.35rem;
        color: #ffffff;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .section-title-bar h2 i {
        color: #9d4a6f;
    }

    .section-subtitle {
        font-size: 0.88rem;
        color: #888888;
    }

    .product-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        width: 100%;
    }

    @media (max-width: 900px) {
        .product-grid-3 {
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
    }

    @media (max-width: 550px) {
        .product-grid-3 {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }

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
        padding-bottom: 1rem;
    }

    .product-card:hover {
        transform: translateY(-4px);
        opacity: 0.95;
    }

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

    .product-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 0.5rem 0;
        line-height: 1.35;
        padding: 0 0.5rem;
        text-align: center;
    }

    .product-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #4ade80;
        margin: 0 0 0.4rem 0;
        text-align: center;
    }

    .stock-badge {
        font-size: 0.78rem;
        color: #60a5fa;
        background: rgba(96, 165, 250, 0.12);
        padding: 0.3rem 0.7rem;
        border-radius: 12px;
        border: 1px solid rgba(96, 165, 250, 0.3);
        font-weight: bold;
    }

    /* BANNER DE ANCHO COMPLETO CON BLUR */
    .fullwidth-alt-banner {
        position: relative;
        width: 100%;
        min-height: 320px;
        margin: 3.5rem 0;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(157, 74, 111, 0.6);
        box-shadow: 0 20px 40px rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .banner-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, #2e1838, #050505);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .banner-bg-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.25;
        filter: contrast(120%) grayscale(40%);
    }

    .banner-blur-overlay {
        position: relative;
        z-index: 2;
        width: 100%;
        height: 100%;
        padding: 3rem 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
        text-align: center;
    }

    .banner-content {
        max-width: 800px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .banner-icon {
        font-size: 2.8rem;
        color: #9d4a6f;
        text-shadow: 0 0 15px rgba(157, 74, 111, 0.6);
    }

    .banner-content h2 {
        margin: 0;
        font-size: clamp(1.6rem, 2.8vw, 2.3rem);
        color: #ffffff;
        font-weight: 900;
        line-height: 1.25;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        text-shadow: 0 4px 10px rgba(0,0,0,0.8);
    }

    .banner-content p {
        margin: 0;
        font-size: 1.05rem;
        color: #e0e0e0;
        line-height: 1.5;
    }

    .btn-banner-explore {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        margin-top: 0.8rem;
        padding: 0.9rem 1.8rem;
        background: linear-gradient(135deg, #6b3d80, #9d4a6f);
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.92rem;
        transition: all 0.25s ease;
        box-shadow: 0 8px 20px rgba(157, 74, 111, 0.4);
    }

    .btn-banner-explore:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(157, 74, 111, 0.6);
        background: linear-gradient(135deg, #82479e, #b85382);
    }

    .empty-state-card {
        padding: 3rem;
        text-align: center;
        background: #111111;
        border: 1px dashed #333333;
        border-radius: 12px;
        color: #888888;
    }
</style>