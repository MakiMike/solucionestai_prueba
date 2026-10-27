<?php

/** Dominio compartido por la aplicación web, el worker y las pruebas. */
class TiendaService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("SET TIME ZONE 'UTC'");
        $db->exec("SET lock_timeout = '5s'");
    }

    private function query($sql, array $params = array())
    {
        $q = $this->db->prepare($sql);
        $q->execute($params);
        return $q;
    }

    private function transaction(callable $work)
    {
        $this->db->beginTransaction();
        try {
            $result = $work();
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function event($id, $actor, $type, array $data)
    {
        $this->query('INSERT INTO tienda_outbox (ped_id, actor_id, tipo, datos) VALUES (?, ?, ?, CAST(? AS jsonb))',
            array($id, $actor, $type, json_encode($data, JSON_THROW_ON_ERROR)));
    }

    public function checkout($user, array $cart, $key)
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', (string) $key)) throw new DomainException('Identificador de compra inválido. Recarga el carrito.');
        $this->expireDue();
        return $this->transaction(function () use ($user, $cart, $key) {
            // Serializa los pedidos del mismo cliente para aplicar el límite acumulado.
            if (!$this->query('SELECT us_id FROM us_usuarios WHERE us_id = ? FOR UPDATE', array($user))->fetch()) {
                throw new DomainException('Usuario no válido.');
            }
            $previous = $this->query('SELECT * FROM pedidos WHERE us_id = ? AND ped_clave = ?', array($user, $key))->fetch();
            if ($previous) return $previous;
            if (!$cart || count($cart) > 100) throw new DomainException('El carrito debe contener entre 1 y 100 variantes.');
            $items = array();
            foreach ($cart as $item) {
                foreach (array('pla_id', 'tal_id', 'cantidad') as $field) {
                    if (!isset($item[$field]) || filter_var($item[$field], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 1000000))) === false) {
                        throw new DomainException('Prenda, talla o cantidad inválida.');
                    }
                }
                $variant = sprintf('%010d:%010d', $item['pla_id'], $item['tal_id']);
                if (isset($items[$variant])) throw new DomainException('Variante duplicada en el carrito.');
                $items[$variant] = $item;
            }
            ksort($items);
            $models = array();
            foreach ($items as $item) $models[(int) $item['pla_id']] = true;
            ksort($models);
            foreach ($models as $id => $_) {
                $model = $this->query('SELECT * FROM playeras WHERE pla_id = ? AND pla_activa = TRUE FOR UPDATE', array($id))->fetch();
                if (!$model) throw new DomainException('Una prenda ya no está disponible.');
                $requested = 0;
                foreach ($items as $item) if ((int) $item['pla_id'] === $id) $requested += (int) $item['cantidad'];
                if (in_array($model['pla_edicion_limitada'], array(true, 1, '1', 't'), true)) {
                    $used = $this->query("SELECT COALESCE(SUM(d.pde_cantidad),0) FROM pedido_detalle d JOIN pedidos p USING (ped_id) WHERE p.us_id = ? AND d.pla_id = ? AND p.ped_estado NOT IN ('cancelado','vencido')", array($user, $id))->fetchColumn();
                    if ($requested + (int) $used > 2) throw new DomainException('Edición limitada: máximo dos unidades por cliente, sumando todas las tallas y pedidos.');
                }
                $models[$id] = $model;
            }
            $total = 0;
            foreach ($items as &$item) {
                $stock = $this->query('SELECT pta_stock FROM playera_tallas WHERE pla_id = ? AND tal_id = ? FOR UPDATE', array($item['pla_id'], $item['tal_id']))->fetchColumn();
                if ($stock === false || (int) $stock < (int) $item['cantidad']) throw new DomainException('Existencias insuficientes para una de las tallas.');
                $model = $models[(int) $item['pla_id']];
                $price = (string) $model['pla_precio'];
                if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $price)) throw new DomainException('Precio de catálogo inválido.');
                $parts = explode('.', $price);
                $cents = (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
                $total += $cents * (int) $item['cantidad'];
                $item['precio'] = number_format($cents / 100, 2, '.', '');
                $item['nombre'] = $model['pla_nombre'];
                $item['talla'] = $this->query('SELECT tal_nombre FROM tallas WHERE tal_id = ?', array($item['tal_id']))->fetchColumn();
            }
            unset($item);
            $order = $this->query("INSERT INTO pedidos (us_id,ped_fecha,ped_estado,ped_total,ped_clave,ped_vence_en) VALUES (?,clock_timestamp(),'pendiente_pago',?,?,clock_timestamp() + interval '30 minutes') RETURNING *", array($user, number_format($total / 100, 2, '.', ''), $key))->fetch();
            foreach ($items as $item) {
                $this->query('INSERT INTO pedido_detalle (ped_id,pla_id,tal_id,pde_cantidad,pde_precio,pde_nombre,pde_talla) VALUES (?,?,?,?,?,?,?)', array($order['ped_id'], $item['pla_id'], $item['tal_id'], $item['cantidad'], $item['precio'], $item['nombre'], $item['talla']));
                $this->query('UPDATE playera_tallas SET pta_stock = pta_stock - ? WHERE pla_id = ? AND tal_id = ?', array($item['cantidad'], $item['pla_id'], $item['tal_id']));
            }
            $this->event($order['ped_id'], $user, 'reserva_creada', array('estado' => 'pendiente_pago', 'vence_en' => $order['ped_vence_en'], 'unidades' => array_values($items)));
            return $order;
        });
    }

    private function release(array $order, $state, $actor)
    {
        $details = $this->query('SELECT * FROM pedido_detalle WHERE ped_id = ? ORDER BY pla_id,tal_id', array($order['ped_id']))->fetchAll();
        foreach ($details as $d) {
            $this->query('UPDATE playera_tallas SET pta_stock = pta_stock + ? WHERE pla_id = ? AND tal_id = ?', array($d['pde_cantidad'], $d['pla_id'], $d['tal_id']));
        }
        $this->query('UPDATE pedidos SET ped_estado = ?, ped_actualizado_en = clock_timestamp() WHERE ped_id = ?', array($state, $order['ped_id']));
        $this->event($order['ped_id'], $actor, $state, array('anterior' => $order['ped_estado'], 'estado' => $state, 'unidades_liberadas' => $details));
    }

    public function expireDue($limit = 100)
    {
        return $this->transaction(function () use ($limit) {
            $orders = $this->query("SELECT * FROM pedidos WHERE ped_estado = 'pendiente_pago' AND ped_vence_en <= clock_timestamp() ORDER BY ped_id LIMIT " . max(1, min(1000, (int) $limit)) . ' FOR UPDATE SKIP LOCKED')->fetchAll();
            foreach ($orders as $order) $this->release($order, 'vencido', null);
            return count($orders);
        });
    }

    public function change($id, $user, $action, $admin = false)
    {
        $result = $this->transaction(function () use ($id, $user, $action, $admin) {
            $order = $this->query('SELECT * FROM pedidos WHERE ped_id = ? FOR UPDATE', array($id))->fetch();
            if (!$order || (!$admin && (int) $order['us_id'] !== (int) $user)) throw new DomainException('Pedido no disponible.');
            if ($order['ped_estado'] === 'pendiente_pago' && $this->query('SELECT CAST(? AS timestamptz) <= clock_timestamp()', array($order['ped_vence_en']))->fetchColumn()) {
                $this->release($order, 'vencido', null);
                return array('expired' => true);
            }
            if ($action === 'pagar') {
                if ((int) $order['us_id'] !== (int) $user) throw new DomainException('Solo el comprador puede realizar el pago simulado.');
                if ($order['ped_estado'] === 'confirmado') return array('estado' => 'confirmado');
                $from = 'pendiente_pago'; $to = 'confirmado';
            } elseif ($action === 'cancelar') {
                if ($order['ped_estado'] === 'cancelado') return array('estado' => 'cancelado');
                if ((int) $order['us_id'] !== (int) $user || $order['ped_estado'] !== 'pendiente_pago') throw new DomainException('Solo puedes cancelar tus pedidos pendientes de pago.');
                $this->release($order, 'cancelado', $user);
                return array('estado' => 'cancelado');
            } else {
                if (!$admin) throw new DomainException('La preparación y la entrega requieren permisos de administrador.');
                $states = array('preparar' => array('confirmado', 'en_preparacion'), 'listo' => array('en_preparacion', 'listo_entrega'), 'entregar' => array('listo_entrega', 'entregado'));
                if (!isset($states[$action])) throw new DomainException('Acción no válida.');
                list($from, $to) = $states[$action];
                if ($order['ped_estado'] === $to) return array('estado' => $to);
            }
            if ($order['ped_estado'] !== $from) throw new DomainException('El estado actual del pedido no permite esta operación.');
            $this->query('UPDATE pedidos SET ped_estado = ?, ped_actualizado_en = clock_timestamp() WHERE ped_id = ?', array($to, $id));
            $this->event($id, $user, $action === 'pagar' ? 'pago_confirmado' : 'estado_cambiado', array('anterior' => $from, 'estado' => $to, 'pago_simulado' => $action === 'pagar'));
            return array('estado' => $to);
        });
        if (isset($result['expired'])) throw new DomainException('El plazo de 30 minutos terminó. La reserva fue liberada; crea un pedido nuevo.');
        return $result;
    }

    public function orders($user, $admin, $search = '', $page = 1)
    {
        $this->expireDue();
        $where = $admin ? 'TRUE' : 'p.us_id = ?';
        $params = $admin ? array() : array($user);
        if ($search !== '') {
            $where .= $admin ? ' AND (CAST(p.ped_id AS text) ILIKE ? OR p.ped_estado ILIKE ?)' : ' AND p.ped_estado ILIKE ?';
            if ($admin) $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        return $this->query('SELECT p.*,u.us_nombre, (SELECT COUNT(*) FROM tienda_outbox o WHERE o.ped_id=p.ped_id AND o.enviado_en IS NULL) AS sincronizacion_pendiente FROM pedidos p JOIN us_usuarios u USING (us_id) WHERE ' . $where . ' ORDER BY p.ped_id DESC LIMIT 20 OFFSET ' . ((max(1, (int) $page) - 1) * 20), $params)->fetchAll();
    }

    public function detail($id, $user, $admin)
    {
        $order = $this->query('SELECT * FROM pedidos WHERE ped_id = ?' . ($admin ? '' : ' AND us_id = ?'), $admin ? array($id) : array($id, $user))->fetch();
        if (!$order) throw new DomainException('Pedido no disponible.');
        return $this->query('SELECT d.*,COALESCE(d.pde_nombre,p.pla_nombre) AS nombre,COALESCE(d.pde_talla,t.tal_nombre) AS talla FROM pedido_detalle d JOIN playeras p USING (pla_id) JOIN tallas t USING (tal_id) WHERE d.ped_id = ?', array($id))->fetchAll();
    }
}
