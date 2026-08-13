<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Venta_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listar_datatable()
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }

        $start = (int) $this->input->post('start');
        $length = (int) $this->input->post('length');
        $search = $this->input->post('search');
        $search_value = '';

        if ($search && isset($search['value'])) {
            $search_value = trim($search['value']);
        }

        // Total de registros
        $recordsTotal = $this->db->count_all_results('pedido_detalle');

        // Consulta principal
        $this->db->select("
            pd.pde_id,
            p.ped_id,
            p.ped_fecha,
            u.us_nombre,
            pl.pla_nombre,
            t.tal_nombre,
            pd.pde_cantidad,
            pd.pde_precio,
            (pd.pde_cantidad * pd.pde_precio) AS subtotal,
            p.ped_estado
        ", false);

        $this->db->from('pedido_detalle pd');
        $this->db->join('pedidos p', 'p.ped_id = pd.ped_id', 'inner');
        $this->db->join('playeras pl', 'pl.pla_id = pd.pla_id', 'inner');
        $this->db->join('tallas t', 't.tal_id = pd.tal_id', 'inner');
        $this->db->join('us_usuarios u', 'u.us_id = p.us_id', 'inner');

        // Búsqueda general
        if (!empty($search_value)) {
            $this->db->group_start();
            $this->db->like('u.us_nombre', $search_value);
            $this->db->or_like('pl.pla_nombre', $search_value);
            $this->db->or_like('t.tal_nombre', $search_value);
            $this->db->or_like('p.ped_estado', $search_value);
            $this->db->or_like('CAST(p.ped_id AS TEXT)', $search_value, 'both', FALSE);
            $this->db->or_like('CAST(pd.pde_precio AS TEXT)', $search_value, 'both', FALSE);
            $this->db->group_end();
        }

        $query = $this->db->get();
        $ventas_filtradas = $query->result();

        $recordsFiltered = count($ventas_filtradas);

        // Calcular Gran Total de las ventas filtradas
        $gran_total = 0;
        foreach ($ventas_filtradas as $venta) {
            $gran_total += (float) $venta->subtotal;
        }

        // Ordenamiento por fecha desc
        $this->db->order_by('p.ped_fecha', 'DESC');

        // Aplicar paginación
        if ($length > 0) {
            $ventas = array_slice($ventas_filtradas, $start, $length);
        } else {
            $ventas = array_slice($ventas_filtradas, $start);
        }

        return array(
            'draw' => intval($this->input->post('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $ventas,
            'gran_total' => number_format($gran_total, 2, '.', '')
        );
    }

    public function obtener_ventas_por_ids($ids = array())
    {
        $this->db->select("
            pd.pde_id,
            p.ped_id,
            p.ped_fecha,
            u.us_nombre,
            pl.pla_nombre,
            t.tal_nombre,
            pd.pde_cantidad,
            pd.pde_precio,
            (pd.pde_cantidad * pd.pde_precio) AS subtotal,
            p.ped_estado
        ", false);

        $this->db->from('pedido_detalle pd');
        $this->db->join('pedidos p', 'p.ped_id = pd.ped_id', 'inner');
        $this->db->join('playeras pl', 'pl.pla_id = pd.pla_id', 'inner');
        $this->db->join('tallas t', 't.tal_id = pd.tal_id', 'inner');
        $this->db->join('us_usuarios u', 'u.us_id = p.us_id', 'inner');

        if (!empty($ids)) {
            $this->db->where_in('pd.pde_id', $ids);
        }

        $this->db->order_by('p.ped_fecha', 'DESC');

        return $this->db->get()->result();
    }
}
