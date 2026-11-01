<?php
class Compat_probe extends CI_Controller {
 public function index() {
  $this->load->model('Probe_model');
  require_once BASEPATH.'database/DB_driver.php';
  class_alias('CI_DB_driver','CI_DB');
  require_once BASEPATH.'database/drivers/postgre/postgre_driver.php';
  $driver=new CI_DB_postgre_driver(array('connect_timeout'=>5,'compress'=>false,'stricton'=>false,'failover'=>array()));
  if($driver->connect_timeout!==5) throw new RuntimeException('Parametro no conservado');
  $this->load->view('probe');
 }
}
