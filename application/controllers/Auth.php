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
            'sexo' => $usuario->us_sexo,
            'rol' => $usuario->us_rol,
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
        $sexo = $this->input->post('sexo');
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

        if (empty($sexo)) {
            $this->session->set_flashdata(
                'error',
                'El campo sexo es obligatorio.'
            );

            redirect('register');
            return;
        }

         $usuario_existente = $this->Usuario_model->obtener_por_correo($correo);
         
         if (empty($telefono)) {
             $telefono = NULL;
            } else {
                $telefono_existente = $this->Usuario_model->obtener_por_telefono($telefono);

                if ($telefono_existente) {
                    $this->session->set_flashdata(
                    'error',
                    'El número de teléfono ya está registrado.'
                    );

                    redirect('register');
                    return;
                }
            }


         if($usuario_existente) {
            $this->session->set_flashdata(
                'error',
                'El correo ya está registrado.'
            );

            redirect('register');
            return;
        }

        if (!in_array($sexo, ['H', 'M', 'O'])) {
            $this->session->set_flashdata(
                'error',
                'Sexo no válido.'
            );

            redirect('register');
            return;
        }

        $data = array(
            'us_nombre' => $nombre,
            'us_correo' => $correo,
            'us_telefono' => $telefono,
            'us_sexo' => $sexo,
            'us_rol' => 'U',
            'us_contrasenia' => $contrasenia
        );

        foreach ($data as $key => $value) {
            if ($key === 'us_rol') {
                  if ($value === 'A') {
                    $this->session->set_flashdata(
                        'error',
                        'No puedes registrarte como administrador.'
                    );

                    redirect('register');
                    return;
                    
            }
        }

        $this->db->insert('us_usuarios', $data);

        $this->session->set_flashdata(
            'success',
            'Usuario registrado exitosamente. Ahora puedes iniciar sesión.'
        );

        redirect('login');
    }
    }

    public function logout()
    {
        $this->session->sess_destroy();

        redirect('login');
    }

    public function recuperar_password()
    {
        $this->load->view('auth/recuperar_password');
    }

    public function procesar_recuperacion()
    {
        $email = trim((string)$this->input->post('email'));

        if (empty($email)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'Por favor ingresa tu correo electrónico.')));
            return;
        }

        $usuario = $this->Usuario_model->obtener_por_correo($email);
        if (!$usuario) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'El correo electrónico no se encuentra registrado.')));
            return;
        }

        // Generar Token de Recuperación único de 64 caracteres compatible con todas las versiones de PHP
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $token = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
        }
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->db->where('us_id', $usuario->us_id)->update('us_usuarios', array(
            'us_reset_token' => $token,
            'us_reset_token_expires' => $expires
        ));

        $reset_url = base_url('restablecer_password/' . $token);

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Se ha generado el enlace de restablecimiento de contraseña para ' . htmlspecialchars($email) . '.',
                'reset_url' => $reset_url
            )));
    }

    public function restablecer_password($token = NULL)
    {
        if (empty($token)) {
            $this->session->set_flashdata('error', 'Token de recuperación no proporcionado.');
            redirect('recuperar_password');
            return;
        }

        $usuario = $this->db->get_where('us_usuarios', array('us_reset_token' => $token))->row();

        if (!$usuario || (strtotime($usuario->us_reset_token_expires) < time())) {
            $this->session->set_flashdata('error', 'El enlace de restablecimiento es inválido o ha expirado.');
            redirect('recuperar_password');
            return;
        }

        $data = array(
            'token' => $token,
            'usuario' => $usuario
        );

        $this->load->view('auth/restablecer_password', $data);
    }

    public function guardar_nueva_password()
    {
        $token        = (string)$this->input->post('token');
        $password     = (string)$this->input->post('password');
        $confirm_pass = (string)$this->input->post('confirm_password');

        if (empty($token)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'Token no válido.')));
            return;
        }

        if (empty($password) || strlen($password) < 4) {
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
                ->set_output(json_encode(array('success' => false, 'error' => 'La contraseña y la confirmación no coinciden.')));
            return;
        }

        $usuario = $this->db->get_where('us_usuarios', array('us_reset_token' => $token))->row();
        if (!$usuario || (strtotime($usuario->us_reset_token_expires) < time())) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'El enlace de recuperación ha expirado o es inválido.')));
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->where('us_id', $usuario->us_id)->update('us_usuarios', array(
            'us_contrasenia' => $hash,
            'us_reset_token' => NULL,
            'us_reset_token_expires' => NULL
        ));

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => '¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión con tu nueva contraseña.'
            )));
    }
}