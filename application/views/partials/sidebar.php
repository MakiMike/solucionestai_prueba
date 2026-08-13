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
                <a href="<?= base_url('Productos'); ?>">
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

    </ul>

</aside>