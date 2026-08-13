<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Playera_model extends CI_Model
{
    private $table = 'playeras';
    public function __construct()
    {
        parent::__construct();
    }

    //CRUD
    public function get_playeras()
    {
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function get_playera($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->row();
    }

    public function insert_playera($data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->insert($this->table, $data);
    }

    public function update_playera($id, $data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete_playera($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->delete($this->table);
    }

    // Métodos personalizados
}