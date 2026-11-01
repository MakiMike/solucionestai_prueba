<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Playera_Talla_model extends CI_Model
{
    private $table = 'playera_tallas';
    public function __construct()
    {
        parent::__construct();
    }

    //CRUD
    public function get_playera_tallas()
    {
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function get_playera_talla($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->row();
    }

    public function crear($data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->insert($this->table, $data);
    }

    public function actualizar($id, $data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function eliminar($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->delete($this->table);
    }
}