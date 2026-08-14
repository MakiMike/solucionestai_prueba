<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pedido_Detalle_model extends CI_Model
{
    private $table = 'pedido_detalle';
    public function __construct()
    {
        parent::__construct();
    }

    //CRUD
    public function get_detalles()
    {
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function get_detalle($id)
    {
        $query = $this->db->get_where($this->table, array('pde_id' => $id));
        return $query->row();
    }

    public function crear($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function actualizar($id, $data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }
        return $this->db->where('pde_id', $id)->update($this->table, $data);
    }

    public function eliminar($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }
        return $this->db->where('pde_id', $id)->delete($this->table);
    }

    // Métodos personalizados
    public function get_detalles_by_pedido($pedido_id)
    {
        $this->db->select('pd.*, pl.pla_nombre, t.tal_nombre');
        $this->db->from('pedido_detalle pd');
        $this->db->join('playeras pl', 'pd.pla_id = pl.pla_id');
        $this->db->join('tallas t', 'pd.tal_id = t.tal_id');
        $this->db->where('pd.ped_id', $pedido_id);
        $query = $this->db->get();
        return $query->result();
    }
}