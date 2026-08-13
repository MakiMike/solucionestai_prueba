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
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->row();
    }

    public function insert_detalle($data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->insert($this->table, $data);
    }

    public function update_detalle($id, $data)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete_detalle($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('id', $id)->delete($this->table);
    }

    // Métodos personalizados
    public function get_detalles_by_pedido($pedido_id)
    {
        $this->db->select('pd.*, p.pr_nombre, p.pr_precio');
        $this->db->from('pedido_detalle pd');
        $this->db->join('producto p', 'pd.producto_id = p.id');
        $this->db->where('pd.pedido_id', $pedido_id);
        $query = $this->db->get();
        return $query->result();
    }
}