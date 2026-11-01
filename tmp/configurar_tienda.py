from pathlib import Path
p=Path('application/config/database.php'); s=p.read_text(encoding='utf8'); start=s.index("$db['default'] = array("); s=s[:start]+'''$localFile = __DIR__ . '/database.local.php';
$local = is_file($localFile) ? require $localFile : array();
$setting = function ($name, $key, $default) use ($local) {
    $value = getenv($name);
    return $value !== false ? $value : (isset($local[$key]) ? $local[$key] : $default);
};
$db['default'] = array(
    'dsn' => '',
    'hostname' => $setting('DB_HOST', 'hostname', '127.0.0.1'),
    'username' => $setting('DB_USERNAME', 'username', 'tienda_app'),
    'password' => $setting('DB_PASSWORD', 'password', ''),
    'database' => $setting('DB_DATABASE', 'database', 'tienda'),
    'dbdriver' => 'postgre',
    'port' => $setting('DB_PORT', 'port', '5432'),
    'dbprefix' => '', 'pconnect' => FALSE,
    'db_debug' => ENVIRONMENT === 'development',
    'cache_on' => FALSE, 'cachedir' => '', 'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci', 'swap_pre' => '', 'encrypt' => FALSE,
    'compress' => FALSE, 'stricton' => FALSE, 'failover' => array(),
    'save_queries' => ENVIRONMENT === 'development'
);
// libpq aplica estos parámetros también al driver nativo de CodeIgniter.
if (getenv('DB_SSLMODE')) putenv('PGSSLMODE=' . getenv('DB_SSLMODE'));
if (getenv('DB_SSLROOTCERT')) putenv('PGSSLROOTCERT=' . getenv('DB_SSLROOTCERT'));
'''; p.write_text(s,encoding='utf8')
p=Path('application/config/config.php'); s=p.read_text(encoding='utf8'); start=s.index("//$config['base_url']"); end=s.index('/*',start); s=s[:start]+'''$config['base_url'] = getenv('APP_BASE_URL') ?: 'http://localhost/sistema/';

'''+s[end:]; s=s.replace("$config['sess_driver'] = 'files';", "$config['sess_driver'] = getenv('APP_SESSION_DRIVER') ?: 'database';").replace("$config['sess_save_path'] = NULL;", "$config['sess_save_path'] = $config['sess_driver'] === 'database' ? 'ci_sessions' : sys_get_temp_dir();").replace("$config['encryption_key'] = '';", "$config['encryption_key'] = getenv('APP_ENCRYPTION_KEY') ?: '';").replace("$config['cookie_secure']\t= FALSE;", "$config['cookie_secure'] = getenv('APP_COOKIE_SECURE') === 'true';").replace("$config['cookie_httponly'] \t= FALSE;", "$config['cookie_httponly'] = TRUE;"); p.write_text(s,encoding='utf8')
p=Path('index.php'); s=p.read_text(encoding='utf8').replace("isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development'", "getenv('CI_ENV') ?: 'production'"); s=s.replace("$system_path = 'system';", "$system_path = is_dir(__DIR__ . '/vendor/codeigniter/framework/system') ? __DIR__ . '/vendor/codeigniter/framework/system' : 'system';"); p.write_text(s,encoding='utf8')
p=Path('application/models/Venta_model.php'); s=p.read_text(encoding='utf8').replace("->count_all_results('pedido_detalle');", "->join('pedidos', 'pedidos.ped_id = pedido_detalle.ped_id')->where_in('pedidos.ped_estado', array('confirmado','en_preparacion','listo_entrega','entregado','pagado'))->count_all_results('pedido_detalle');"); s=s.replace("$this->db->from('pedido_detalle pd');", "$this->db->from('pedido_detalle pd');\n    $this->db->where_in('p.ped_estado', array('confirmado','en_preparacion','listo_entrega','entregado','pagado'));"); s=s.replace('pl.pla_nombre,','COALESCE(pd.pde_nombre, pl.pla_nombre) AS pla_nombre,').replace('t.tal_nombre,','COALESCE(pd.pde_talla, t.tal_nombre) AS tal_nombre,'); p.write_text(s,encoding='utf8')
