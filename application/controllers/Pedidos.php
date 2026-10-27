<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/MY_Controller.php';
require_once APPPATH . 'services/TiendaConnection.php';
require_once APPPATH . 'services/TiendaService.php';
require_once APPPATH . 'services/TiendaHistory.php';

class Pedidos extends MY_Controller
{
    private $store;
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = TiendaConnection::open();
        $this->store = new TiendaService($this->pdo);
    }

    public function index()
    {
        $user = (int) $this->session->userdata('usuario_id');
        $admin = $this->session->userdata('rol') === 'A';
        $search = substr(trim((string) $this->input->get('q')), 0, 100);
        $page = max(1, (int) $this->input->get('page'));
        $orders = $this->store->orders($user, $admin, $search, $page);
        $details = array(); $events = array(); $historyAvailable = true;
        $selected = (int) $this->input->get('pedido');
        if ($selected > 0) {
            try { $details = $this->store->detail($selected, $user, $admin); }
            catch (DomainException $e) { show_404(); return; }
            try { $events = (new TiendaHistory($this->pdo))->read($selected); }
            catch (Throwable $e) { $historyAvailable = false; }
        }
        $this->load->view('layouts/main', array('title' => 'Mis pedidos', 'content' => 'pedidos', 'orders' => $orders, 'details' => $details, 'events' => $events, 'historyAvailable' => $historyAvailable, 'selected' => $selected, 'admin' => $admin, 'search' => $search, 'page' => $page));
    }

    public function accion()
    {
        if ($this->input->method() !== 'post') { show_error('Método no permitido.', 405); return; }
        // Token de sesión para las operaciones del flujo, también en instalaciones antiguas sin CSRF global.
        $token = (string) $this->input->post('pedido_token');
        $expected = (string) $this->session->userdata('pedido_token');
        if ($expected === '' || !hash_equals($expected, $token)) { show_error('Recarga la página antes de continuar.', 403); return; }
        try {
            $this->store->change((int) $this->input->post('ped_id'), (int) $this->session->userdata('usuario_id'), (string) $this->input->post('accion'), $this->session->userdata('rol') === 'A');
            $this->session->set_flashdata('pedido_aviso', 'Pedido actualizado.');
        } catch (DomainException $e) { $this->session->set_flashdata('pedido_aviso', $e->getMessage()); }
        catch (Throwable $e) { log_message('error', 'Pedido: ' . get_class($e)); $this->session->set_flashdata('pedido_aviso', 'No se pudo completar la operación. Intenta de nuevo.'); }
        redirect('pedidos');
    }
}
