<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->userdata('logueado')) {
            redirect('login');
            exit;
        }
        $mutations = array('agregar','actualizar','eliminar','vaciar','procesar','crear','editar','guardar','guardar_usuario','importar','accion');
        if (in_array($this->router->fetch_method(), $mutations, true) && $this->input->method() !== 'post') {
            show_error('Método no permitido.', 405);
            exit;
        }
    }
}
