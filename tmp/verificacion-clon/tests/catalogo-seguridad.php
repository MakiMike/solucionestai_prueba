<?php
// Ejecuta el controlador real con consultas capturadas; no conecta a una BD.
define('BASEPATH', __DIR__ . '/../vendor/codeigniter/framework/system/');
define('APPPATH', __DIR__ . '/../application/');
require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB_driver.php';
#[AllowDynamicProperties]
class CI_Controller {}
function base_url($path) { return 'http://prueba.local/' . $path; }
function site_url($path) { return base_url($path); }
class CatalogoDbPrueba extends CI_DB_driver {
    public $filtros = array();
    public function __construct() {}
    public function from($table) { return $this; }
    public function where($key, $value = null, $escape = null) {
        if (strpos($key, ' ILIKE ') !== false) $this->filtros[] = $key;
        return $this;
    }
    public function count_all_results() { return 1; }
    public function select($columns) { return $this; }
    public function order_by($column, $direction) { return $this; }
    public function limit($limit, $offset) { return $this; }
    public function get() { return new class { public function result() { return array(); } }; }
}
class CatalogoInputPrueba {
    private $search;
    public function __construct($search) { $this->search = $search; }
    public function post($key) { return null; }
    public function get($key) { return $key === 'search' ? $this->search : ($key === 'page' ? 1 : 'TODOS'); }
}
class CatalogoOutputPrueba {
    public function set_content_type($type) { return $this; }
    public function set_output($json) { return $this; }
}
require APPPATH . 'controllers/Ropa.php';
$cases = array("O'Reilly", '%_!', "%' OR 1=1 --", "x'; DROP TABLE playeras; --", 'coleccion');
foreach ($cases as $search) {
    $controller = (new ReflectionClass('Ropa'))->newInstanceWithoutConstructor();
    $controller->db = new CatalogoDbPrueba();
    $controller->input = new CatalogoInputPrueba($search);
    $controller->output = new CatalogoOutputPrueba();
    $controller->obtener_catalogo();
    $filters = $controller->db->filtros;
    if (count($filters) !== 2 || $filters[0] !== $filters[1]) throw new RuntimeException('Conteo y catalogo deben usar el mismo filtro.');
    foreach ($filters as $sql) {
        if (!preg_match("/^pla_nombre ILIKE '((?:[^']|'')*)' ESCAPE '!'$/D", $sql, $match)) {
            throw new RuntimeException('El texto de busqueda salio del literal SQL.');
        }
        $pattern = str_replace("''", "'", $match[1]);
        if (substr($pattern, 0, 1) !== '%' || substr($pattern, -1) !== '%') throw new RuntimeException('Falta busqueda parcial.');
        $literal = preg_replace('/!(.)/s', '$1', substr($pattern, 1, -1));
        if ($literal !== $search) throw new RuntimeException('El escape cambio el texto buscado.');
        if (!preg_match('/^(?:[^!%_]|![!%_])*$/sD', substr($pattern, 1, -1))) throw new RuntimeException('Comodin sin escapar.');
    }
}
echo 'OK: 5 busquedas, conteo y listado; comillas, comodines y texto SQL tratados como datos.' . PHP_EOL;
