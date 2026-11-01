from pathlib import Path
p=Path('application/controllers/Carrito.php'); s=p.read_text(encoding='utf8'); start=s.index('        $subtotal = 0;',s.index('    public function procesar()')); s=s[:start]+'''        if ($this->input->method() !== 'post') { show_error('Método no permitido.', 405); return; }
        $token = (string) $this->input->post('pedido_token');
        $expected = (string) $this->session->userdata('pedido_token');
        if ($expected === '' || !hash_equals($expected, $token)) { show_error('Recarga el carrito antes de continuar.', 403); return; }
        require_once APPPATH . 'services/TiendaConnection.php';
        require_once APPPATH . 'services/TiendaService.php';
        try {
            $service = new TiendaService(TiendaConnection::open());
            $order = $service->checkout((int) $us_id, $carrito, (string) $this->session->userdata('checkout_key'));
            $this->session->unset_userdata('carrito');
            $response = array('success' => true, 'message' => 'Pedido reservado. Tienes 30 minutos para pagar.', 'ped_id' => $order['ped_id'], 'redirect' => site_url('pedidos'));
        } catch (DomainException $e) {
            $this->output->set_status_header(409);
            $response = array('success' => false, 'error' => $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Checkout: ' . get_class($e));
            $this->output->set_status_header(503);
            $response = array('success' => false, 'error' => 'No se pudo reservar el pedido. Intenta nuevamente.');
        }
        if (ob_get_length() > 0) { ob_clean(); }
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }
}
'''; s=s.replace('if ($stock_disponible > 0 && $nueva_cantidad > $stock_disponible)', 'if ($nueva_cantidad > $stock_disponible)').replace('if ($stock_disponible > 0 && $cantidad > $stock_disponible)', 'if ($cantidad > $stock_disponible)'); s=s.replace("        $total = $subtotal;", "        $total = $subtotal;\n        $this->session->set_userdata('checkout_key', bin2hex(random_bytes(16)));\n        if (!$this->session->userdata('pedido_token')) $this->session->set_userdata('pedido_token', bin2hex(random_bytes(32)));",1); p.write_text(s,encoding='utf8')
p=Path('application/views/carrito.php'); s=p.read_text(encoding='utf8').replace('Finalizar Compra (Simular Venta)','Reservar pedido por 30 minutos').replace('¡Venta Simulada con Éxito!','¡Pedido reservado!').replace('El pedido ha sido registrado en la pestaña Ventas del Admin.','Tienes 30 minutos para pagar en Mis pedidos.'); s=s.replace("fetch('<?= site_url(\"carrito/procesar\"); ?>', { method: 'POST' })", "fetch('<?= site_url(\"carrito/procesar\"); ?>', { method: 'POST', body: new URLSearchParams({pedido_token: <?= json_encode($this->session->userdata('pedido_token')); ?>}) })"); s=s.replace('                    purchaseSuccessMessage.textContent = data.message;', "                    window.location.assign(data.redirect);\n                    purchaseSuccessMessage.textContent = data.message;"); p.write_text(s,encoding='utf8')
p=Path('application/views/partials/sidebar.php'); s=p.read_text(encoding='utf8').replace('    <ul>', '''    <ul>
        <li><a href="<?= site_url('pedidos'); ?>">Mis pedidos</a></li>''',1); p.write_text(s,encoding='utf8')
p=Path('application/config/routes.php'); s=p.read_text(encoding='utf8'); s+="\n$route['pedidos'] = 'pedidos/index';\n$route['pedidos/accion'] = 'pedidos/accion';\n"; p.write_text(s,encoding='utf8')
