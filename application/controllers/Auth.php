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

    public function register()
    {
        $this->load->view('auth/register');
    }

    public function attempt_login()
    {
        $correo = $this->input->post('email');
        $contrasenia = $this->input->post('password');

        $usuario = $this->Usuario_model->obtener_por_correo($correo);

         if (!$usuario) {

            $this->session->set_flashdata(
                'error',
                'Correo o contraseña incorrectos.'
            );

            redirect('login');
            return;
        }

        if (!password_verify($contrasenia, $usuario->us_contrasenia)) {

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
       // echo "Usuario logueado: " . $usuario->us_nombre;
        redirect('dashboard');
    }

    public function create_user()
    {
        $nombre = $this->input->post('nombre');
        $correo = $this->input->post('email');
        $telefono = $this->input->post('telefono');
        $contrasenia = password_hash($this->input->post('password'), PASSWORD_DEFAULT);

        if (
            empty ($nombre) ||
            empty ($correo) ||
            empty ($contrasenia)
        ) {
            $this->session->set_flashdata(
                'error',
                'Todos los campos son obligatorios.'
            );

            redirect('register');
            return;
        }

        if ($telefono && !preg_match('/^\+?[0-9]{7,15}$/', $telefono)) {
            $this->session->set_flashdata(
                'error',
                'El número de teléfono no es válido.'
            );

            redirect('register');
            return;
        }

        if ($telefono && strlen($telefono) > 10) {
            $this->session->set_flashdata(
                'error',
                'El número de teléfono no puede tener más de 10 dígitos.'
            );

            redirect('register');
            return;
        }
        

         $usuario_existente = $this->Usuario_model->obtener_por_correo($correo);
         $telefono_existente = $this->Usuario_model->obtener_por_telefono($telefono);

         if ($telefono_existente) {
            $this->session->set_flashdata(
                'error',
                'El número de teléfono ya está registrado.'
            );

            redirect('register');
            return;
        }

         if($usuario_existente) {
            $this->session->set_flashdata(
                'error',
                'El correo ya está registrado.'
            );

            redirect('register');
            return;
        }

        $data = array(
            'us_nombre' => $nombre,
            'us_correo' => $correo,
            'us_telefono' => $telefono,
            'us_contrasenia' => $contrasenia
        );

        $this->db->insert('us_usuarios', $data);

        $this->session->set_flashdata(
            'success',
            'Usuario registrado exitosamente. Ahora puedes iniciar sesión.'
        );

        redirect('login');
    }

    public function logout()
    {
        $this->session->sess_destroy();

        redirect('login');
    }
}