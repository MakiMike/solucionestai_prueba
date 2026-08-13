<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->session->userdata('logueado')) {

            redirect('login');
        }
    }


    public function index()
    {
        $data = array(
            'title' => 'Dashboard',
            'content' => 'dashboard',
            'us_nombre' => $this->session->userdata('usuario_nombre'),
            'us_correo' => $this->session->userdata('usuario_email'),
            'us_telefono' => $this->session->userdata('telefono'),
            'us_sexo' => $this->session->userdata('sexo'),
            'us_rol' => $this->session->userdata('rol')
        );

        $this->load->view('layouts/main', $data);
    }
}