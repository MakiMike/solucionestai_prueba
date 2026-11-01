<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Perfil extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Usuario_model');
    }

    public function index()
    {
        $us_id = $this->session->userdata('usuario_id');
        if (empty($us_id)) {
            redirect('login');
            return;
        }

        $usuario = $this->Usuario_model->obtener_por_id($us_id);
        if (!$usuario) {
            redirect('dashboard');
            return;
        }

        $data = array(
            'title' => 'Mi Perfil',
            'content' => 'perfil',
            'usuario' => $usuario
        );

        $this->load->view('layouts/main', $data);
    }

    public function actualizar()
    {
        $us_id = $this->session->userdata('usuario_id');
        if (empty($us_id)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'Sesión no válida.')));
            return;
        }

        $nombre       = trim((string)$this->input->post('us_nombre'));
        $telefono     = trim((string)$this->input->post('us_telefono'));
        $sexo         = strtoupper(trim((string)$this->input->post('us_sexo')));
        $password     = (string)$this->input->post('nueva_password');
        $confirm_pass = (string)$this->input->post('confirmar_password');

        // Validaciones
        if (empty($nombre)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'El nombre no puede estar vacío.')));
            return;
        }

        if (!in_array($sexo, array('H', 'M', 'O'))) {
            $sexo = 'O';
        }

        // Validar teléfono duplicado si fue ingresado
        if (!empty($telefono)) {
            if (strlen($telefono) > 15) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'El teléfono no puede superar los 15 caracteres.')));
                return;
            }

            $existingTel = $this->Usuario_model->obtener_por_telefono($telefono);
            if ($existingTel && $existingTel->us_id != $us_id) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'Este número de teléfono ya está registrado por otro usuario.')));
                return;
            }
        }

        // Preparar arreglo de actualización
        $update_data = array(
            'us_nombre'   => $nombre,
            'us_telefono' => !empty($telefono) ? $telefono : NULL,
            'us_sexo'     => $sexo
        );

        if (!empty($password)) {
            if (strlen($password) < 4) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'La contraseña debe tener al menos 4 caracteres.')));
                return;
            }

            if ($password !== $confirm_pass) {
                if (ob_get_length() > 0) { @ob_clean(); }
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'error' => 'La nueva contraseña y su confirmación no coinciden.')));
                return;
            }

            $update_data['us_contrasenia'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $result = $this->Usuario_model->actualizar($us_id, $update_data);

        if ($result) {
            // Actualizar datos de la sesión activa
            $this->session->set_userdata('usuario_nombre', $nombre);
            $this->session->set_userdata('telefono', !empty($telefono) ? $telefono : NULL);
            $this->session->set_userdata('sexo', $sexo);

            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => true,
                    'message' => '¡Perfil actualizado con éxito!'
                )));
        } else {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'Ocurrió un error al guardar los datos.')));
        }
    }
}
