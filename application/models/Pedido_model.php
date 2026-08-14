<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pedido_model extends CI_Model
{
    private $table = 'pedidos';
    public function __construct()
    {
        parent::__construct();
    }

    //CRUD
    public function get_pedidos()
    {
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function get_pedido($id)
    {
        $query = $this->db->get_where($this->table, array('ped_id' => $id));
        return $query->row();
    }

    public function crear($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function actualizar($id, $data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }
        return $this->db->where('ped_id', $id)->update($this->table, $data);
    }

    public function eliminar($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }
        return $this->db->where('ped_id', $id)->delete($this->table);
    }

    // Métodos personalizados
}