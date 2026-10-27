<?php
if (!$this->session->userdata('pedido_token')) $this->session->set_userdata('pedido_token', bin2hex(random_bytes(32)));
$escape = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$labels = array('pendiente_pago'=>'Pendiente de pago','confirmado'=>'Confirmado','en_preparacion'=>'En preparación','listo_entrega'=>'Listo para entrega','entregado'=>'Entregado','vencido'=>'Vencido','cancelado'=>'Cancelado');
?>
<section class="pedidos-page">
    <h1><?= $admin ? 'Pedidos de la tienda' : 'Mis pedidos'; ?></h1>
    <p>Tienes 30 minutos para pagar cada pedido. Si vence el plazo, las prendas vuelven a estar disponibles.</p>
    <?php if ($notice = $this->session->flashdata('pedido_aviso')): ?><p role="status"><?= $escape($notice); ?></p><?php endif; ?>
    <form method="get" action="<?= site_url('pedidos'); ?>">
        <label>Buscar por número o estado <input name="q" value="<?= $escape($search); ?>" maxlength="100"></label>
        <button>Buscar</button>
    </form>
    <?php if (!$orders): ?><p>No hay pedidos para mostrar.</p><?php endif; ?>
    <?php foreach ($orders as $order): ?>
    <article class="pedido-card">
        <h2>Pedido #<?= (int) $order['ped_id']; ?> · <?= $escape(isset($labels[$order['ped_estado']]) ? $labels[$order['ped_estado']] : $order['ped_estado']); ?></h2>
        <p><?= $escape($order['us_nombre']); ?> · $<?= $escape($order['ped_total']); ?></p>
        <?php if ($order['ped_estado'] === 'pendiente_pago'): ?>
            <p>Tiempo para pagar: <strong class="countdown" data-deadline="<?= $escape(date('c', strtotime($order['ped_vence_en']))); ?>">calculando…</strong></p>
        <?php endif; ?>
        <?php if ((int) $order['sincronizacion_pendiente'] > 0): ?><p>Historial con sincronización pendiente.</p><?php endif; ?>
        <a href="<?= site_url('pedidos') . '?pedido=' . (int) $order['ped_id']; ?>">Ver prendas e historial</a>
        <?php
        $actions = array();
        if ((int) $order['us_id'] === (int) $this->session->userdata('usuario_id') && $order['ped_estado'] === 'pendiente_pago') $actions = array('pagar'=>'Pagar ahora simulación','cancelar'=>'Cancelar pedido');
        if ($admin) {
            $next = array('confirmado'=>array('preparar'=>'Preparar pedido'),'en_preparacion'=>array('listo'=>'Marcar listo'),'listo_entrega'=>array('entregar'=>'Registrar entrega'));
            if (isset($next[$order['ped_estado']])) $actions += $next[$order['ped_estado']];
        }
        foreach ($actions as $action => $label): ?>
            <form method="post" action="<?= site_url('pedidos/accion'); ?>" class="pedido-action">
                <input type="hidden" name="pedido_token" value="<?= $escape($this->session->userdata('pedido_token')); ?>">
                <input type="hidden" name="ped_id" value="<?= (int) $order['ped_id']; ?>">
                <input type="hidden" name="accion" value="<?= $escape($action); ?>">
                <button><?= $escape($label); ?></button>
            </form>
        <?php endforeach; ?>
    </article>
    <?php endforeach; ?>
    <nav aria-label="Paginación de pedidos">
        <?php if ($page > 1): ?><a href="<?= site_url('pedidos') . '?page=' . ($page-1) . '&q=' . urlencode($search); ?>">Anterior</a><?php endif; ?>
        <?php if (count($orders) === 20): ?><a href="<?= site_url('pedidos') . '?page=' . ($page+1) . '&q=' . urlencode($search); ?>">Siguiente</a><?php endif; ?>
    </nav>
    <?php if ($selected): ?>
        <h2>Prendas del pedido #<?= (int) $selected; ?></h2>
        <?php foreach ($details as $d): ?><p><?= $escape($d['nombre']); ?> · <?= $escape($d['talla']); ?> · <?= (int) $d['pde_cantidad']; ?> unidades · $<?= $escape($d['pde_precio']); ?> cada una</p><?php endforeach; ?>
        <h2>Historial del pedido</h2>
        <?php if (!$historyAvailable): ?><p>El historial no está disponible temporalmente. Tu pedido se conserva y los eventos pendientes se sincronizarán al recuperar la conexión.</p><?php elseif (!$events): ?><p>Sin eventos sincronizados todavía.</p><?php endif; ?>
        <ol><?php foreach ($events as $event): ?><li><?= $escape($event->fecha->toDateTime()->format('Y-m-d H:i:s') . ' UTC · ' . $event->tipo); ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
</section>
<style>
.pedidos-page{max-width:1050px;margin:auto}.pedidos-page h1,.pedidos-page h2{margin:1rem 0}.pedidos-page p{margin:.7rem 0}.pedido-card{background:var(--gray-dark);border:1px solid var(--purple-accent);padding:1.2rem;margin:1rem 0}.pedidos-page a{color:#dfa4c8}.pedidos-page button,.pedidos-page input{padding:.65rem;background:var(--gray-medium);color:var(--white);border:1px solid var(--pink-dark);margin:.4rem 0}.pedido-action{display:inline-block;margin-right:1rem}.pedidos-page nav{display:flex;gap:1rem}
</style>
<script>
function updateOrderTimers(){document.querySelectorAll('.countdown').forEach(function(el){const left=Math.max(0,Math.floor((new Date(el.dataset.deadline).getTime()-Date.now())/1000));el.textContent=left?Math.floor(left/60)+':'+String(left%60).padStart(2,'0'):'Plazo agotado';if(!left){el.closest('article').querySelectorAll('button').forEach(function(b){b.disabled=true;});}});}
updateOrderTimers();setInterval(updateOrderTimers,1000);
</script>
