<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Carrito extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Playera_model');
        $this->load->model('Pedido_model');
        $this->load->model('Pedido_Detalle_model');
    }

    public function index()
    {
        $carrito = $this->session->userdata('carrito') ?: array();
        
        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += (float)$item['subtotal'];
        }

        $total = $subtotal;

        $data = array(
            'title' => 'Carrito de Compras',
            'content' => 'carrito',
            'carrito' => $carrito,
            'subtotal' => $subtotal,
            'total' => $total
        );

        $this->load->view('layouts/main', $data);
    }

    public function agregar()
    {
        $pla_id   = (int) $this->input->post('pla_id');
        $tal_id   = (int) $this->input->post('tal_id');
        $cantidad = (int) $this->input->post('cantidad');

        if ($pla_id <= 0 || $tal_id <= 0 || $cantidad <= 0) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'Datos inválidos. Por favor seleccione talla y cantidad.'
                )));
            return;
        }

        $playera = $this->Playera_model->get_playera($pla_id);
        if (!$playera) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El producto seleccionado no existe.'
                )));
            return;
        }

        // Obtener nombre de la talla
        $talla_row = $this->db->get_where('tallas', array('tal_id' => $tal_id))->row();
        $tal_nombre = $talla_row ? $talla_row->tal_nombre : 'N/A';

        // Obtener stock disponible de esta playera y talla
        $stock_row = $this->db->get_where('playera_tallas', array('pla_id' => $pla_id, 'tal_id' => $tal_id))->row();
        $stock_disponible = $stock_row ? (int)$stock_row->pta_stock : 0;

        $carrito = $this->session->userdata('carrito') ?: array();
        $key = $pla_id . '_' . $tal_id;

        $cantidad_actual = isset($carrito[$key]) ? (int)$carrito[$key]['cantidad'] : 0;
        $nueva_cantidad = $cantidad_actual + $cantidad;

        if ($stock_disponible > 0 && $nueva_cantidad > $stock_disponible) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'Stock insuficiente. Solo hay ' . $stock_disponible . ' unidades disponibles de talla ' . $tal_nombre . '.'
                )));
            return;
        }

        $precio = (float) $playera->pla_precio;

        $carrito[$key] = array(
            'key' => $key,
            'pla_id' => $pla_id,
            'tal_id' => $tal_id,
            'nombre' => $playera->pla_nombre,
            'talla' => $tal_nombre,
            'precio' => $precio,
            'cantidad' => $nueva_cantidad,
            'imagen' => $playera->pla_imagen,
            'subtotal' => $nueva_cantidad * $precio
        );

        $this->session->set_userdata('carrito', $carrito);

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => '¡Producto agregado al carrito con éxito!',
                'total_items' => count($carrito)
            )));
    }

    public function actualizar()
    {
        $key      = $this->input->post('key');
        $cantidad = (int) $this->input->post('cantidad');

        $carrito = $this->session->userdata('carrito') ?: array();

        if (!isset($carrito[$key])) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'Producto no encontrado en el carrito.')));
            return;
        }

        if ($cantidad <= 0) {
            unset($carrito[$key]);
        } else {
            // Verificar stock
            $pla_id = $carrito[$key]['pla_id'];
            $tal_id = $carrito[$key]['tal_id'];
            $stock_row = $this->db->get_where('playera_tallas', array('pla_id' => $pla_id, 'tal_id' => $tal_id))->row();
            $stock_disponible = $stock_row ? (int)$stock_row->pta_stock : 0;

            if ($stock_disponible > 0 && $cantidad > $stock_disponible) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'error' => 'Stock máximo disponible para esta talla: ' . $stock_disponible
                    )));
                return;
            }

            $carrito[$key]['cantidad'] = $cantidad;
            $carrito[$key]['subtotal'] = $cantidad * (float)$carrito[$key]['precio'];
        }

        $this->session->set_userdata('carrito', $carrito);

        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += (float)$item['subtotal'];
        }
        $impuestos = 0;
        $total = $subtotal + $impuestos;

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Carrito actualizado.',
                'carrito' => array_values($carrito),
                'total_items' => count($carrito),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'impuestos' => number_format($impuestos, 2, '.', ''),
                'total' => number_format($total, 2, '.', '')
            )));
    }

    public function eliminar()
    {
        $key = $this->input->post('key');
        $carrito = $this->session->userdata('carrito') ?: array();

        if (isset($carrito[$key])) {
            unset($carrito[$key]);
            $this->session->set_userdata('carrito', $carrito);
        }

        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += (float)$item['subtotal'];
        }
        $impuestos = 0;
        $total = $subtotal + $impuestos;

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto eliminado del carrito.',
                'total_items' => count($carrito),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'impuestos' => number_format($impuestos, 2, '.', ''),
                'total' => number_format($total, 2, '.', '')
            )));
    }

    public function vaciar()
    {
        $this->session->unset_userdata('carrito');

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'El carrito ha sido vaciado.',
                'total_items' => 0,
                'subtotal' => '0.00',
                'impuestos' => '0.00',
                'total' => '0.00'
            )));
    }

    public function procesar()
    {
        $carrito = $this->session->userdata('carrito') ?: array();
        if (empty($carrito)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El carrito está vacío. Agregue productos antes de comprar.'
                )));
            return;
        }

        $us_id = $this->session->userdata('usuario_id');
        if (empty($us_id)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'Debe iniciar sesión para realizar la compra.'
                )));
            return;
        }

        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += (float)$item['subtotal'];
        }
        $total = $subtotal;

        $this->db->trans_start();

        $pedido_data = array(
            'us_id' => $us_id,
            'ped_fecha' => date('Y-m-d H:i:s'),
            'ped_estado' => 'pagado',
            'ped_total' => $total
        );

        $ped_id = $this->Pedido_model->crear($pedido_data);

        foreach ($carrito as $item) {
            $detalle_data = array(
                'ped_id' => $ped_id,
                'pla_id' => (int)$item['pla_id'],
                'tal_id' => (int)$item['tal_id'],
                'pde_cantidad' => (int)$item['cantidad'],
                'pde_precio' => (float)$item['precio']
            );
            $this->Pedido_Detalle_model->crear($detalle_data);

            // Descontar stock
            $this->db->query(
                "UPDATE playera_tallas SET pta_stock = GREATEST(0, pta_stock - ?) WHERE pla_id = ? AND tal_id = ?",
                array((int)$item['cantidad'], (int)$item['pla_id'], (int)$item['tal_id'])
            );
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'Ocurrió un error al procesar el pedido. Por favor intente nuevamente.'
                )));
            return;
        }

        // Vaciar carrito
        $this->session->unset_userdata('carrito');

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => '¡Compra realizada con éxito! Su pedido #' . $ped_id . ' ha sido registrado.',
                'ped_id' => $ped_id
            )));
    }
}
