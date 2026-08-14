<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Playera_model');
    }

    public function index()
    {
        $us_id = $this->session->userdata('usuario_id');
        $us_sexo = $this->session->userdata('sexo');

        // Si el sexo no estuviese en la sesión, consultarlo en base de datos
        if (empty($us_sexo) && !empty($us_id)) {
            $user_row = $this->db->get_where('us_usuarios', array('us_id' => $us_id))->row();
            if ($user_row && !empty($user_row->us_sexo)) {
                $us_sexo = $user_row->us_sexo;
                $this->session->set_userdata('sexo', $us_sexo);
            }
        }

        // Obtener hasta 6 productos con más stock según el sexo del usuario
        $all_products = $this->Playera_model->obtener_top_stock_por_genero($us_sexo, 6);

        // Dividir en 2 bloques de 3 productos
        $productos_top3 = array_slice($all_products, 0, 3);
        $productos_next3 = array_slice($all_products, 3, 3);

        // Mensajes aleatorios inspirados en la cultura alternativa por sesión/petición
        $mensajes_alt = array(
            "Expresa tu esencia oscura y alternativa sin filtros.",
            "El estilo alternativo no es una moda, es una declaración de libertad.",
            "Rompe el molde y viste tu auténtica identidad con la mejor calidad.",
            "Sombras, arte y rebeldía: el look que define tu verdadera esencia.",
            "La cultura alternativa vive en ti. Encuentra tu estilo sin ataduras.",
            "No encajes en el sistema, viste tu propia rebelión."
        );
        $mensaje_random = $mensajes_alt[array_rand($mensajes_alt)];

        $data = array(
            'title' => 'Dashboard',
            'content' => 'dashboard',
            'us_nombre' => $this->session->userdata('usuario_nombre'),
            'us_correo' => $this->session->userdata('usuario_email'),
            'us_telefono' => $this->session->userdata('telefono'),
            'us_sexo' => $us_sexo,
            'us_rol' => $this->session->userdata('rol'),
            'productos_top3' => $productos_top3,
            'productos_next3' => $productos_next3,
            'mensaje_random' => $mensaje_random
        );

        $this->load->view('layouts/main', $data);
    }
}