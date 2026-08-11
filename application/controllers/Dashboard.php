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
            'us_nombre' => $this->session->userdata('usuario_nombre'),
            'us_correo' => $this->session->userdata('usuario_email')
        );

        $this->load->view('dashboard', $data);
    }
}