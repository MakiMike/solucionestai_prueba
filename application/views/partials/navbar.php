<nav class="navbar">
    <!-- Logo y Título -->
    <div class="navbar-brand">
        <img src="<?= base_url('assets/img/logo.png'); ?>" alt="Logo" class="logo">
        <h1>TSkull-Shirt</h1>
    </div>

    <!-- Menú Hamburguesa -->
    <input type="checkbox" id="menu-toggle" class="menu-toggle">
    <label for="menu-toggle" class="menu-icon">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <!-- Menú de Navegación (Solo Perfil) -->
    <ul class="navbar-menu">
        <li><a href="<?= base_url('configuracion'); ?>" class="profile-link">Perfil</a></li>
        <li class="navbar-divider"></li>
        <li><a href="<?= base_url('logout'); ?>" class="logout-link">Cerrar Sesión</a></li>
    </ul>
</nav>