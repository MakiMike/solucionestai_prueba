<aside>

    <ul>

        <li>
            <a href="<?= base_url('dashboard'); ?>">
                Inicio
            </a>
        </li>

        <?php if ($this->session->userdata('rol') === 'A'): ?>

            <li>
                <a href="<?= base_url('usuarios'); ?>">
                    Usuarios
                </a>
            </li>

            <li>
                <a href="<?= base_url('productos'); ?>">
                    Productos
                </a>
            </li>

            <li>
                <a href="<?= base_url('Ventas'); ?>">
                    Ventas
                </a>
            </li>

        <?php endif; ?>

        <li>
            <a href="<?= base_url('Ropa/hombre'); ?>">
                Ropa Hombre
            </a>
        </li>

        <li>
            <a href="<?= base_url('Ropa/mujer'); ?>">
                Ropa Mujer
            </a>
        </li>

        <li>
            <a href="<?= base_url('ProductosAll'); ?>">
                Todos los productos
            </a>
        </li>
         
        <li>
            <a href="<?= base_url('Carrito'); ?>">
                Carrito <span class="cart-count-badge"><?= $this->session->userdata('carrito') ? '(' . count($this->session->userdata('carrito')) . ')' : ''; ?></span>
            </a>
        </li>

        <li>
            <a href="<?= base_url('perfil'); ?>">
                Mi Perfil
            </a>
        </li>

    </ul>

</aside>