<?php
error_reporting(E_ALL); ini_set('display_errors','1');
define('ENVIRONMENT','development');
define('FCPATH','C:/laragon/www/sistema/');
define('BASEPATH',FCPATH.'vendor/codeigniter/framework/system/');
define('APPPATH','C:/laragon/www/sistema/tmp/ci-compat-check/');
define('VIEWPATH',APPPATH.'views/');
define('SYSDIR','system');
require BASEPATH.'core/CodeIgniter.php';
