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

    // Obtener paginación
    $start = (int) $this->input->post('start');
    $length = (int) $this->input->post('length');

    // Obtener búsqueda de DataTables
    $search = $this->input->post('search');

    $search_value = '';

    
    if ($search && isset($search['value'])) {

        $search_value = trim($search['value']);
    }

    // Total de registros
    $recordsTotal = $this->db
        ->count_all_results('pedido_detalle');


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


    // Tabla principal
    $this->db->from('pedido_detalle pd');


    // Relación con pedidos
    $this->db->join(
        'pedidos p',
        'p.ped_id = pd.ped_id',
        'inner'
    );


    // Relación con productos
    $this->db->join(
        'playeras pl',
        'pl.pla_id = pd.pla_id',
        'inner'
    );


    // Relación con tallas
    $this->db->join(
        'tallas t',
        't.tal_id = pd.tal_id',
        'inner'
    );


    // Relación con usuarios
    $this->db->join(
        'us_usuarios u',
        'u.us_id = p.us_id',
        'inner'
    );


    // Aplicar búsqueda
    if (!empty($search_value)) {

        $search_value =
            $this->db->escape_like_str($search_value);


        $this->db->group_start();

        $this->db->where(
            "u.us_nombre ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "pl.pla_nombre ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "t.tal_nombre ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "p.ped_estado ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "CAST(p.ped_id AS TEXT) ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "CAST(pd.pde_precio AS TEXT) ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );


     
        $this->db->group_end();
    }



    $this->db->order_by(
        'p.ped_fecha',
        'DESC'
    );


    // Ejecutar consulta
    $query = $this->db->get();


    // Obtener resultados
    $ventas_filtradas = $query->result();


    // Total de resultados filtrados
    $recordsFiltered = count($ventas_filtradas);


    // Calcular gran total
    $gran_total = 0;


    // Sumar subtotales
    foreach ($ventas_filtradas as $venta) {

        $gran_total +=
            (float) $venta->subtotal;
    }


    // Aplicar paginación
    if ($length > 0) {

        $ventas = array_slice(
            $ventas_filtradas,
            $start,
            $length
        );

    } else {

        $ventas = array_slice(
            $ventas_filtradas,
            $start
        );
    }


    // Respuesta para DataTables
    return array(

        'draw' => intval(
            $this->input->post('draw')
        ),

     
        'recordsTotal' =>
            $recordsTotal,

     
        'recordsFiltered' =>
            $recordsFiltered,

        
        'data' =>
            $ventas,

        
        'gran_total' =>
            number_format(
                $gran_total,
                2,
                '.',
                ''
            )
    );
}
}
