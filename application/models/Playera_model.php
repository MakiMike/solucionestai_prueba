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
    return $this->db
        ->select('
            playeras.pla_id,
            playeras.pla_nombre,
            playeras.pla_descripcion,
            playeras.pla_genero,
            playeras.pla_precio,
            playeras.pla_imagen,
            playeras.pla_activa,
            tallas.tal_nombre AS tal_talla,
            playera_tallas.pta_stock
        ')
        ->from('playeras')
        ->join(
            'playera_tallas',
            'playera_tallas.pla_id = playeras.pla_id'
        )
        ->join(
            'tallas',
            'tallas.tal_id = playera_tallas.tal_id'
        )
        ->get()
        ->result();
}

    public function get_playera($id)
    {
        $query = $this->db->get_where($this->table, array('pla_id' => $id));
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
        return $this->db->where('pla_id', $id)->update($this->table, $data);
    }

    public function desactivar($id)
    {
        if ($this->session->userdata('rol') !== 'A') {
            // Redirigir al dashboard si no es admin, en lugar de mostrar error
            redirect('dashboard');
            exit;
        }
        return $this->db->where('pla_id', $id)->update($this->table, array('pla_activa' => 'false'));
    }

    // Métodos personalizados

    public function obtener_tallas()
    {
    return $this->db
        ->order_by('tal_orden', 'ASC')
        ->get('tallas')
        ->result();
    }

    public function guardar_tallas($pla_id, $stocks)
    {
    $tallas = $this->obtener_tallas();

    foreach ($tallas as $talla) {

        $stock = 0;

        if (isset($stocks[$talla->tal_nombre])) {
            $stock = (int) $stocks[$talla->tal_nombre];
        }

        $this->db->insert(
            'playera_tallas',
            array(
                'pla_id' => $pla_id,
                'tal_id' => $talla->tal_id,
                'pta_stock' => $stock
            )
        );
    }
    }

    public function actualizar_tallas($pla_id, $stocks)
    {
        $tallas = $this->obtener_tallas();

        foreach ($tallas as $talla) {

            $stock = 0;

            if (isset($stocks[$talla->tal_nombre])) {
                $stock = (int) $stocks[$talla->tal_nombre];
            }

            $this->db
                ->where('pla_id', $pla_id)
                ->where('tal_id', $talla->tal_id)
                ->update(
                    'playera_tallas',
                    array(
                        'pta_stock' => $stock
                    )
                );
        }

        return TRUE;
    }

    public function obtener_stock($pla_id)
    {
        return $this->db
            ->select('
                tallas.tal_id,
                tallas.tal_nombre,
                COALESCE(playera_tallas.pta_stock, 0) AS pta_stock
            ', FALSE)
            ->from('tallas')
            ->join(
                'playera_tallas',
                'playera_tallas.tal_id = tallas.tal_id AND playera_tallas.pla_id = ' . (int)$pla_id,
                'left'
            )
            ->order_by(
                'tallas.tal_orden',
                'ASC'
            )
            ->get()
            ->result();
    }

    public function obtener_top_stock_por_genero($genero = NULL, $limit = 6)
    {
        $this->db->select('playeras.pla_id, playeras.pla_nombre, playeras.pla_descripcion, playeras.pla_genero, playeras.pla_precio, playeras.pla_imagen, COALESCE(SUM(playera_tallas.pta_stock), 0) AS stock_total', FALSE);
        $this->db->from('playeras');
        $this->db->join('playera_tallas', 'playera_tallas.pla_id = playeras.pla_id', 'left');
        $this->db->where('playeras.pla_activa', true);

        if ($genero === 'H') {
            $this->db->group_start();
            $this->db->where('playeras.pla_genero', 'H');
            $this->db->or_where('playeras.pla_genero', 'Hombre');
            $this->db->or_where('playeras.pla_genero', 'hombre');
            $this->db->group_end();
        } elseif ($genero === 'M') {
            $this->db->group_start();
            $this->db->where('playeras.pla_genero', 'M');
            $this->db->or_where('playeras.pla_genero', 'Mujer');
            $this->db->or_where('playeras.pla_genero', 'mujer');
            $this->db->group_end();
        }

        $this->db->group_by('playeras.pla_id');
        $this->db->order_by('stock_total', 'DESC');
        $this->db->order_by('playeras.pla_id', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    public function listar_datatable()
{
    if ($this->session->userdata('rol') !== 'A') {
        // Redirigir al dashboard si no es admin
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

    $genero_filter = $this->input->post('genero');


    // Total de playeras
    $recordsTotal = $this->db
        ->count_all_results('playeras');


    // Consulta principal
    $this->db->select("
        p.pla_id,
        p.pla_nombre,
        p.pla_genero,
        p.pla_precio,
        p.pla_imagen,
        p.pla_activa,

        COALESCE(
            SUM(
                CASE
                    WHEN t.tal_nombre = 'S'
                    THEN ps.pta_stock
                    ELSE 0
                END
            ), 0
        ) AS stock_S,

        COALESCE(
            SUM(
                CASE
                    WHEN t.tal_nombre = 'M'
                    THEN ps.pta_stock
                    ELSE 0
                END
            ), 0
        ) AS stock_M,

        COALESCE(
            SUM(
                CASE
                    WHEN t.tal_nombre = 'L'
                    THEN ps.pta_stock
                    ELSE 0
                END
            ), 0
        ) AS stock_L,

        COALESCE(
            SUM(
                CASE
                    WHEN t.tal_nombre = 'XL'
                    THEN ps.pta_stock
                    ELSE 0
                END
            ), 0
        ) AS stock_XL,

        COALESCE(
            SUM(
                CASE
                    WHEN t.tal_nombre = '2XL'
                    THEN ps.pta_stock
                    ELSE 0
                END
            ), 0
        ) AS stock_2XL

    ", false);


    $this->db->from('playeras p');


    // Tabla intermedia
    $this->db->join(
        'playera_tallas ps',
        'ps.pla_id = p.pla_id',
        'left'
    );


    // Tabla de tallas
    $this->db->join(
        'tallas t',
        't.tal_id = ps.tal_id',
        'left'
    );


    // Filtro por genero
    if (!empty($genero_filter) && $genero_filter !== 'todos') {

        $this->db->where(
            'p.pla_genero',
            $genero_filter
        );

    }


    // Busqueda general
    if (!empty($search_value)) {

        $this->db->group_start();

        $this->db->like(
            'p.pla_nombre',
            $search_value
        );

        $this->db->or_like(
            'p.pla_descripcion',
            $search_value
        );

        $this->db->or_like(
            'CAST(p.pla_precio AS TEXT)',
            $search_value,
            'both',
            FALSE
        );

        $this->db->or_like(
            'p.pla_genero',
            $search_value
        );

        $this->db->group_end();

    }


    // Agrupar por playera
    $this->db->group_by([
        'p.pla_id',
        'p.pla_nombre',
        'p.pla_genero',
        'p.pla_precio',
        'p.pla_imagen',
        'p.pla_activa'
    ]);


    // Obtener resultados filtrados
    $query = $this->db->get();

    $playeras_filtradas = $query->result();


    // Total de resultados filtrados
    $recordsFiltered = count($playeras_filtradas);


    // Aplicar paginacion
    if ($length > 0) {
        $playeras = array_slice($playeras_filtradas, $start, $length);
    } else {
        $playeras = array_slice($playeras_filtradas, $start);
    }


    return array(

        'draw' => intval(
            $this->input->post('draw')
        ),

        'recordsTotal' => $recordsTotal,

        'recordsFiltered' => $recordsFiltered,

        'data' => $playeras

    );
}
    public function listar_por_ids($ids = array())
    {
        if ($this->session->userdata('rol') !== 'A') {
            redirect('dashboard');
            exit;
        }

        if (!is_array($ids)) {
            $ids = array_filter(explode(',', $ids));
        } else {
            $ids = array_filter($ids);
        }

        $this->db->select("
            p.pla_id,
            p.pla_nombre,
            p.pla_descripcion,
            p.pla_genero,
            p.pla_precio,
            p.pla_imagen,
            p.pla_activa,

            COALESCE(
                SUM(
                    CASE
                        WHEN t.tal_nombre = 'S'
                        THEN ps.pta_stock
                        ELSE 0
                    END
                ), 0
            ) AS stock_s,

            COALESCE(
                SUM(
                    CASE
                        WHEN t.tal_nombre = 'M'
                        THEN ps.pta_stock
                        ELSE 0
                    END
                ), 0
            ) AS stock_m,

            COALESCE(
                SUM(
                    CASE
                        WHEN t.tal_nombre = 'L'
                        THEN ps.pta_stock
                        ELSE 0
                    END
                ), 0
            ) AS stock_l,

            COALESCE(
                SUM(
                    CASE
                        WHEN t.tal_nombre = 'XL'
                        THEN ps.pta_stock
                        ELSE 0
                    END
                ), 0
            ) AS stock_xl,

            COALESCE(
                SUM(
                    CASE
                        WHEN t.tal_nombre = '2XL'
                        THEN ps.pta_stock
                        ELSE 0
                    END
                ), 0
            ) AS stock_2xl
        ", false);

        $this->db->from('playeras p');
        $this->db->join('playera_tallas ps', 'ps.pla_id = p.pla_id', 'left');
        $this->db->join('tallas t', 't.tal_id = ps.tal_id', 'left');

        if (!empty($ids)) {
            $this->db->where_in('p.pla_id', $ids);
        }

        $this->db->group_by([
            'p.pla_id',
            'p.pla_nombre',
            'p.pla_descripcion',
            'p.pla_genero',
            'p.pla_precio',
            'p.pla_imagen',
            'p.pla_activa'
        ]);

        return $this->db->get()->result();
    }
}