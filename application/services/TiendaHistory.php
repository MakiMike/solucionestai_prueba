<?php

class TiendaHistory
{
    private $db;
    private $mongo;
    private $name;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->name = getenv('MONGO_DATABASE') ?: 'tienda';
    }

    private function mongo()
    {
        if (!$this->mongo) {
            if (!extension_loaded('mongodb')) throw new RuntimeException('La extensión mongodb no está instalada.');
            $this->mongo = new MongoDB\Driver\Manager(getenv('MONGO_URI') ?: 'mongodb://127.0.0.1:27017', array('serverSelectionTimeoutMS' => 1500, 'connectTimeoutMS' => 1500));
        }
        return $this->mongo;
    }

    public function initialize()
    {
        $validator = array('$jsonSchema' => array('bsonType' => 'object', 'required' => array('evento_id','pedido_id','tipo','fecha','datos'), 'properties' => array('evento_id' => array('bsonType' => array('int','long')), 'pedido_id' => array('bsonType' => array('int','long')), 'tipo' => array('bsonType' => 'string'), 'fecha' => array('bsonType' => 'date'), 'datos' => array('bsonType' => 'object'))));
        $collections = $this->mongo()->executeCommand($this->name, new MongoDB\Driver\Command(array('listCollections' => 1, 'filter' => array('name' => 'eventos'))))->toArray();
        $exists = count($collections) > 0;
        $this->mongo()->executeCommand($this->name, new MongoDB\Driver\Command(array(($exists ? 'collMod' : 'create') => 'eventos', 'validator' => $validator)));
        $this->mongo()->executeCommand($this->name, new MongoDB\Driver\Command(array('createIndexes' => 'eventos', 'indexes' => array(array('key' => array('evento_id' => 1), 'name' => 'evento_unico', 'unique' => true), array('key' => array('pedido_id' => 1, 'fecha' => -1), 'name' => 'pedido_fecha')))));
    }

    public function sync($limit = 100)
    {
        $sent = 0;
        for ($i = 0; $i < $limit; $i++) {
            $this->db->beginTransaction();
            try {
                $e = $this->db->query('SELECT * FROM tienda_outbox WHERE enviado_en IS NULL ORDER BY evento_id LIMIT 1 FOR UPDATE SKIP LOCKED')->fetch(PDO::FETCH_ASSOC);
                if (!$e) { $this->db->commit(); break; }
                $payload = array('evento_id' => (int) $e['evento_id'], 'pedido_id' => (int) $e['ped_id'], 'actor_id' => $e['actor_id'] === null ? null : (int) $e['actor_id'], 'tipo' => $e['tipo'], 'fecha' => new MongoDB\BSON\UTCDateTime((int) (strtotime($e['creado_en']) * 1000)), 'datos' => json_decode($e['datos'], false, 512, JSON_THROW_ON_ERROR));
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->update(array('evento_id' => (int) $e['evento_id']), array('$setOnInsert' => $payload), array('upsert' => true));
                $this->mongo()->executeBulkWrite($this->name . '.eventos', $bulk, array('writeConcern' => new MongoDB\Driver\WriteConcern('majority', 3000)));
                $q = $this->db->prepare('UPDATE tienda_outbox SET enviado_en=clock_timestamp(),intentos=intentos+1,ultimo_error=NULL WHERE evento_id=?');
                $q->execute(array($e['evento_id']));
                $this->db->commit(); $sent++;
            } catch (Throwable $error) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                if (isset($e['evento_id'])) {
                    $q = $this->db->prepare('UPDATE tienda_outbox SET intentos=intentos+1,ultimo_error=? WHERE evento_id=?');
                    $q->execute(array(get_class($error), $e['evento_id']));
                }
                throw $error;
            }
        }
        return $sent;
    }

    public function read($id)
    {
        $q = new MongoDB\Driver\Query(array('pedido_id' => (int) $id), array('sort' => array('fecha' => -1, 'evento_id' => -1), 'limit' => 100));
        return $this->mongo()->executeQuery($this->name . '.eventos', $q)->toArray();
    }
}
