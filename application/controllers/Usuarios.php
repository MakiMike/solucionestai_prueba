<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

class Usuarios extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        if (!$this->session->userdata('logueado')) {

            redirect('login');
        }

        $this->load->model('Usuario_model');
    }

	public function index()
	{
        $this->load->view('usuarios');
    }
	
    public function listar()
    {
        $resultado = $this->Usuario_model->listar_datatable();

        echo json_encode($resultado);
    }
}