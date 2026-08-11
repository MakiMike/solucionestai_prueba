<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario_model extends CI_Model
{
    private $tabla = 'us_usuarios';

    public function obtener_por_correo($email)
    {
        return $this->db
            ->where('us_correo', $email)
            ->get($this->tabla)
            ->row();
    }
}