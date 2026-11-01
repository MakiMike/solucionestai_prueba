<?php
// Compatibilidad acotada para CodeIgniter 3.1.13 y PHP >= 8.2.
// CI3 inyecta dependencias mediante propiedades dinamicas por diseno.
// Se aplica al instalar dependencias y al construir la imagen, sin ocultar avisos.
$root = dirname(__DIR__) . '/vendor/codeigniter/framework/system';
$versionFile = $root . '/core/CodeIgniter.php';
if (!is_file($versionFile) || !preg_match("/const\\s+CI_VERSION\\s*=\\s*'3\\.1\\.13'/", file_get_contents($versionFile))) {
    fwrite(STDERR, "Se esperaba CodeIgniter 3.1.13; revisar la compatibilidad antes de cambiar de version.\n");
    exit(1);
}
$classes = array(
    'core/URI.php' => 'CI_URI',
    'core/Router.php' => 'CI_Router',
    'core/Controller.php' => 'CI_Controller',
    'core/Loader.php' => 'CI_Loader',
    'core/Model.php' => 'CI_Model',
    'database/DB_driver.php' => 'CI_DB_driver'
);
foreach ($classes as $relative => $class) {
    $path = $root . '/' . $relative;
    $source = file_get_contents($path);
    if ($source === false) throw new RuntimeException('No se pudo leer ' . $relative);
    $pattern = '/^(?:#\[\\\\AllowDynamicProperties\]\r?\n)?((?:abstract )?class ' . preg_quote($class, '/') . '\b)/m';
    $updated = preg_replace_callback($pattern, function ($match) {
        return "#[\\AllowDynamicProperties]\n" . $match[1];
    }, $source, -1, $count);
    if ($updated === null || $count !== 1) throw new RuntimeException('Declaracion inesperada en ' . $relative);
    if ($updated !== $source && file_put_contents($path, $updated) === false) throw new RuntimeException('No se pudo adaptar ' . $relative);
}
echo "Compatibilidad de propiedades dinamicas aplicada a CodeIgniter 3.1.13.\n";
