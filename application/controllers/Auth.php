<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Usuario_model');
    }

    public function login()
    {
        $this->load->view('auth/login');
    }

    public function attempt_login()
    {
        $correo = $this->input->post('correo');
        $contrasenia = $this->input->post('contrasenia');

        $usuario = $this->Usuario_model->obtener_por_correoc($correo);

         if (!$usuario) {

            $this->session->set_flashdata(
                'error',
                'Correo o contraseña incorrectos.'
            );

            redirect('login');
            return;
        }

        if (!password_verify($contrasenia, $usuario->contrasenia)) {

            $this->session->set_flashdata(
                'error',
                'Correo o contraseña incorrectos.'
            );

            redirect('login');
            return;
        }

        // Regeneramos el ID de sesión
        $this->session->sess_regenerate(TRUE);

        // Guardamos información del usuario
        $this->session->set_userdata(array(
            'usuario_id' => $usuario->us_id,
            'usuario_nombre' => $usuario->us_nombre,
            'usuario_email' => $usuario->us_correo,
            'telefono' => $usuario->us_telefono,
            'logueado' => TRUE
        ));

        redirect('dashboard');
    }

    public function logout()
    {
        $this->session->sess_destroy();

        redirect('login');
    }
}