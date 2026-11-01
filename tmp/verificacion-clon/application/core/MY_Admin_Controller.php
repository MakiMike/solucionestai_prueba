<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Admin_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Verificar si el usuario es admin
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
    }
}