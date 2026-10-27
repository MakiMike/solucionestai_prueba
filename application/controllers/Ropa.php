<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Ropa extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Playera_model');
    }

    public function hombre()
    {
        $data = array(
            'title' => 'Ropa de Hombre',
            'content' => 'ropa',
            'genero' => 'H'
        );
        $this->load->view('layouts/main', $data);
    }

    public function mujer()
    {
        $data = array(
            'title' => 'Ropa de Mujer',
            'content' => 'ropa',
            'genero' => 'M'
        );
        $this->load->view('layouts/main', $data);
    }

    public function todos()
    {
        $data = array(
            'title' => 'Todos los Productos',
            'content' => 'ropa',
            'genero' => 'TODOS'
        );
        $this->load->view('layouts/main', $data);
    }

    public function obtener_catalogo()
    {
        $genero = $this->input->post('genero') ? $this->input->post('genero') : $this->input->get('genero');
        $search = $this->input->post('search') ? trim($this->input->post('search')) : trim((string)$this->input->get('search'));
        $page   = (int) ($this->input->post('page') ? $this->input->post('page') : $this->input->get('page'));
        if ($page < 1) {
            $page = 1;
        }

        $limit = 9; 
        $offset = ($page - 1) * $limit;

        //Conteo Total
        $this->db->from('playeras');
        $this->db->where('pla_activa', true);

        if ($genero === 'H') {
            $this->db->group_start();
            $this->db->where('pla_genero', 'H');
            $this->db->or_where('pla_genero', 'Hombre');
            $this->db->or_where('pla_genero', 'hombre');
            $this->db->group_end();
        } elseif ($genero === 'M') {
            $this->db->group_start();
            $this->db->where('pla_genero', 'M');
            $this->db->or_where('pla_genero', 'Mujer');
            $this->db->or_where('pla_genero', 'mujer');
            $this->db->group_end();
        }

        if (!empty($search)) {
            $this->db->where(
                "pla_nombre ILIKE '%" . $this->db->escape_like_str($search) . "%' ESCAPE '!'",
                NULL,
                FALSE
                );
        }

        $total_records = $this->db->count_all_results();

        // Datos Paginados
        $this->db->select('pla_id, pla_nombre, pla_descripcion, pla_genero, pla_precio, pla_imagen');
        $this->db->from('playeras');
        $this->db->where('pla_activa', true);

        if ($genero === 'H') {
            $this->db->group_start();
            $this->db->where('pla_genero', 'H');
            $this->db->or_where('pla_genero', 'Hombre');
            $this->db->or_where('pla_genero', 'hombre');
            $this->db->group_end();
        } elseif ($genero === 'M') {
            $this->db->group_start();
            $this->db->where('pla_genero', 'M');
            $this->db->or_where('pla_genero', 'Mujer');
            $this->db->or_where('pla_genero', 'mujer');
            $this->db->group_end();
        }

        if (!empty($search)) {
            $this->db->where(
                "pla_nombre ILIKE '%" . $this->db->escape_like_str($search) . "%' ESCAPE '!'",
                NULL,
                FALSE
                );
        }

        $this->db->order_by('pla_id', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $productos = $query->result();

        $fallback_img = base_url('assets/img/logo.png');
        foreach ($productos as &$prod) {
            $img = trim((string)$prod->pla_imagen);
            if (!empty($img)) {
                $prod->imagen_url = base_url('uploads/playeras/' . $img);
            } else {
                $prod->imagen_url = $fallback_img;
            }
            $prod->detalle_url = site_url('ropa/detalle/' . $prod->pla_id);
        }

        $total_pages = ceil($total_records / $limit);
        if ($total_pages < 1) {
            $total_pages = 1;
        }

        if (ob_get_length() > 0) { @ob_clean(); }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'productos' => $productos,
                'page' => $page,
                'total_pages' => (int)$total_pages,
                'total_records' => (int)$total_records,
                'fallback_img' => $fallback_img
            )));
    }

    public function detalle($id = NULL)
    {
        if (empty($id)) {
            $id = $this->input->post('pla_id');
        }

        if (empty($id)) {
            if ($this->input->is_ajax_request()) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'ID no proporcionado')));
                return;
            }
            redirect('ProductosAll');
            return;
        }

        $playera = $this->Playera_model->get_playera($id);
        if (!$playera) {
            if ($this->input->is_ajax_request()) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'Producto no encontrado')));
                return;
            }
            redirect('ProductosAll');
            return;
        }

        $stocks = $this->Playera_model->obtener_stock($id);

        if ($this->input->is_ajax_request()) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => true,
                    'producto' => $playera,
                    'stocks' => $stocks
                )));
            return;
        }

        // Cargar vista completa de la página de detalle del producto
        $data = array(
            'title' => $playera->pla_nombre . ' - Venta en Línea',
            'content' => 'producto_detalle',
            'producto' => $playera,
            'stocks' => $stocks
        );

        $this->load->view('layouts/main', $data);
    }
}
