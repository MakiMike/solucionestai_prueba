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

    public function obtener_por_telefono($telefono)
    {
        return $this->db
            ->where('us_telefono', $telefono)
            ->get($this->tabla)
            ->row();
    }

    public function listar_usuarios()
    {
        return $this->db
            ->where('us_rol', 'U')
            ->get($this->tabla)
            ->result();
    }

    public function listar_datatable()
{
    $start  = (int) $this->input->post('start');
    $length = (int) $this->input->post('length');

    $search = $this->input->post('search');

    $search_value = '';

    if (isset($search['value'])) {
        $search_value = trim($search['value']);
    }

    $sexo_filter = $this->input->post('sexo');


   
    $recordsTotal = $this->db
        ->where('us_rol', 'U')
        ->count_all_results($this->tabla);



     //CONSULTA PARA REGISTROS FILTRADOS

    $this->db->where('us_rol', 'U');


    // Filtro por sexo

    if (!empty($sexo_filter) && $sexo_filter !== 'todos') {

        $this->db->where('us_sexo', $sexo_filter);

    }


    // Búsqueda general

    if (!empty($search_value)) {

        $search_value = $this->db->escape_like_str($search_value);

        $this->db->group_start();

        $this->db->where(
            "us_nombre ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_correo ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_telefono ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_sexo ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->group_end();
    }

    $recordsFiltered = $this->db
        ->count_all_results($this->tabla);


    $this->db->where('us_rol', 'U');


    // volver a aplicar el filtro de sexo

    if (!empty($sexo_filter) && $sexo_filter !== 'todos') {

        $this->db->where('us_sexo', $sexo_filter);

    }

    // volver a aplicar la búsqueda

    if (!empty($search_value)) {

        $this->db->group_start();

        $this->db->where(
            "us_nombre ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_correo ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_telefono ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->or_where(
            "us_sexo ILIKE '%{$search_value}%'",
            NULL,
            FALSE
        );

        $this->db->group_end();
    }



    //PAGINACIÓN
    $usuarios = $this->db
        ->limit($length, $start)
        ->get($this->tabla)
        ->result();


   
     //RESPUESTA PARA DATATABLES
    return array(

        'draw' => intval($this->input->post('draw')),

        'recordsTotal' => $recordsTotal,

        'recordsFiltered' => $recordsFiltered,

        'data' => $usuarios

    );
}


    public function listar_usuarios_por_ids($ids)
    {  
        return $this->db
            ->where_in('us_id', $ids)
            ->get($this->tabla)
            ->result();
        
    }
}