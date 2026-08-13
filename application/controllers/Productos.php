<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Admin_Controller.php';

class Productos extends MY_Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Playera_model');
        $this->load->library('upload');
    }


    public function index()
    {
        $data = array(
            'title' => 'Productos',
            'content' => 'productos'
        );

        $this->load->view('layouts/main', $data);
    }


    public function listar()
    {
        $productos = $this->Playera_model->listar_datatable();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($productos));
    }

    public function guardar()
    {
        $id = $this->input->post('pla_id');

        if (!empty($id)) {
            $this->editar();
        } else {
            $this->crear();
        }
    }

    public function crear()
    {
        $datos = array(
            'pla_nombre' => $this->input->post('pla_nombre'),
            'pla_descripcion' => $this->input->post('pla_descripcion'),
            'pla_genero' => $this->input->post('pla_genero'),
            'pla_precio' => $this->input->post('pla_precio')
        );

        $imagen = NULL;

        if (!empty($_FILES['pla_imagen']['name'])) {
            $config['upload_path']   = './uploads/playeras/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size']      = 2048;
            $config['encrypt_name']  = TRUE;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('pla_imagen')) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'error' => $this->upload->display_errors()
                    )));
                return;
            }

            $archivo = $this->upload->data();
            $imagen = $archivo['file_name'];
            $datos['pla_imagen'] = $imagen;
        }

        $stocks = array(
            'S' => $this->input->post('stock_S'),
            'M' => $this->input->post('stock_M'),
            'L' => $this->input->post('stock_L'),
            'XL' => $this->input->post('stock_XL'),
            '2XL' => $this->input->post('stock_2XL')
        );

        foreach ($stocks as $talla => $stock) {
            $stocks[$talla] = ($stock === NULL || $stock === '') ? 0 : (int) $stock;
        }

        $this->db->trans_start();

        $this->Playera_model->crear($datos);
        $pla_id = $this->db->insert_id();

        $this->Playera_model->guardar_tallas($pla_id, $stocks);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            if ($imagen !== NULL) {
                $ruta = './uploads/playeras/' . $imagen;
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo crear el producto.'
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto creado correctamente.'
            )));
    }


    public function editar()
    {
        $id = $this->input->post('pla_id');
        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El producto no existe.'
                )));
            return;
        }

        $datos = array(
            'pla_nombre' => $this->input->post('pla_nombre'),
            'pla_descripcion' => $this->input->post('pla_descripcion'),
            'pla_genero' => $this->input->post('pla_genero'),
            'pla_precio' => $this->input->post('pla_precio')
        );

        $stocks = array(
            'S' => $this->input->post('stock_S'),
            'M' => $this->input->post('stock_M'),
            'L' => $this->input->post('stock_L'),
            'XL' => $this->input->post('stock_XL'),
            '2XL' => $this->input->post('stock_2XL')
        );

        foreach ($stocks as $talla => $stock) {
            $stocks[$talla] = ($stock === NULL || $stock === '') ? 0 : (int) $stock;
        }

        $imagen_nueva = NULL;

        if (!empty($_FILES['pla_imagen']['name'])) {
            $config['upload_path']   = './uploads/playeras/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size']      = 2048;
            $config['encrypt_name']  = TRUE;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('pla_imagen')) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'error' => $this->upload->display_errors()
                    )));
                return;
            }

            $archivo = $this->upload->data();
            $imagen_nueva = $archivo['file_name'];
            $datos['pla_imagen'] = $imagen_nueva;
        }

        $this->db->trans_start();

        $this->Playera_model->actualizar($id, $datos);
        $this->Playera_model->actualizar_tallas($id, $stocks);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            if ($imagen_nueva !== NULL) {
                $ruta = './uploads/playeras/' . $imagen_nueva;
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo actualizar el producto.'
                )));
            return;
        }

        if ($imagen_nueva !== NULL) {
            if (!empty($playera->pla_imagen)) {
                $ruta_anterior = './uploads/playeras/' . $playera->pla_imagen;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto actualizado correctamente.'
            )));
    }
    
    public function eliminar()
    {
        $id = $this->input->post('pla_id');
        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El producto no existe.'
                )));
            return;
        }

        $resultado = $this->Playera_model->desactivar($id);

        if (!$resultado) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo desactivar el producto.'
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto desactivado correctamente.'
            )));
    }

    public function obtener($id = NULL)
    {
        if (empty($id)) {
            $id = $this->input->post('pla_id');
        }

        if (empty($id)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'ID nulo')));
            return;
        }

        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'No encontrada')));
            return;
        }

        $stocks = $this->Playera_model->obtener_stock($id);

        $json = json_encode(array(
            'success' => true,
            'producto' => $playera,
            'playera' => $playera,
            'stocks' => $stocks
        ));

        if ($json === false) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => json_last_error_msg())));
            return;
        }

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

}