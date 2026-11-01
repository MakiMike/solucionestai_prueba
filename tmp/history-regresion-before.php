<?php
// Contrato del driver 2.x simulado: no usa credenciales ni bases de datos.
namespace MongoDB\Driver {
    class WriteConcern {
        public $w; public $timeout;
        public function __construct($w, $timeout) { $this->w=$w; $this->timeout=$timeout; }
    }
    class BulkWrite {
        public $filter; public $update; public $options;
        public function update($filter,$update,$options) { $this->filter=$filter; $this->update=$update; $this->options=$options; }
    }
}
namespace MongoDB\BSON {
    class UTCDateTime { public function __construct($milliseconds) {} }
}
namespace {
    require 'C:/laragon/www/sistema/tmp/history-before.php';
    class HistoryStatement extends \PDOStatement {
        private $callback; private $row;
        public function __construct($callback=null,$row=false) { $this->callback=$callback; $this->row=$row; }
        public function fetch(int $mode=\PDO::FETCH_DEFAULT,int $orientation=\PDO::FETCH_ORI_NEXT,int $offset=0): mixed { return $this->row; }
        public function execute(?array $params=null): bool { ($this->callback)($params); return true; }
    }
    class HistoryDatabase extends \PDO {
        public $pending=true; public $sent=false; public $attempts=0; public $rolledBack=false; private $active=false;
        public function __construct() {}
        public function beginTransaction(): bool { $this->active=true; return true; }
        public function commit(): bool { $this->active=false; return true; }
        public function rollBack(): bool { $this->active=false; $this->rolledBack=true; return true; }
        public function inTransaction(): bool { return $this->active; }
        public function query(string $query,?int $fetchMode=null,mixed ...$args): \PDOStatement|false {
            return new HistoryStatement(null,$this->pending ? array('evento_id'=>1,'ped_id'=>1,'actor_id'=>2,'tipo'=>'confirmado','creado_en'=>'2026-10-26 20:00:00+00','datos'=>'{"prueba":true}') : false);
        }
        public function prepare(string $query,array $options=[]): \PDOStatement|false {
            return new HistoryStatement(function($params) use ($query) {
                $this->attempts++;
                if (strpos($query,'enviado_en=clock_timestamp()')!==false) { $this->pending=false; $this->sent=true; }
            });
        }
    }
    class HistoryManager {
        public $fail=false; public $documents=array(); public $calls=0;
        // Un objeto WriteConcern directo debe fallar este contrato como en driver 2.x.
        public function executeBulkWrite(string $namespace, \MongoDB\Driver\BulkWrite $bulk, array $options) {
            $this->calls++;
            if ($namespace!=='tienda.eventos' || !isset($options['writeConcern']) || $options['writeConcern']->w!=='majority' || $options['writeConcern']->timeout!==3000) throw new \RuntimeException('Contrato de escritura incorrecto.');
            if ($this->fail) throw new \RuntimeException('Fallo simulado al escribir.');
            if ($bulk->options!==array('upsert'=>true) || !isset($bulk->update['$setOnInsert'])) throw new \RuntimeException('Se perdio el upsert idempotente.');
            $id=$bulk->filter['evento_id'];
            if (!isset($this->documents[$id])) $this->documents[$id]=$bulk->update['$setOnInsert'];
        }
    }
    putenv('MONGO_DATABASE=tienda');
    $db=new HistoryDatabase(); $manager=new HistoryManager(); $history=new \TiendaHistory($db);
    $property=new \ReflectionProperty(\TiendaHistory::class,'mongo'); $property->setAccessible(true); $property->setValue($history,$manager);
    if ($history->sync()!==1 || !$db->sent || $db->pending || $db->inTransaction()) throw new \RuntimeException('No confirma el evento tras escribir.');
    if ($history->sync()!==0 || $manager->calls!==1) throw new \RuntimeException('Reenvio innecesario sin pendientes.');
    // Simula reentrega del mismo evento tras perder el acuse: conserva un documento.
    $db->pending=true;
    if ($history->sync()!==1 || count($manager->documents)!==1) throw new \RuntimeException('Duplico el evento al reintentar.');
    $db->pending=true; $db->sent=false; $manager->fail=true; $before=$db->attempts;
    try { $history->sync(); throw new \LogicException('No propago el fallo.'); }
    catch (\RuntimeException $error) { if ($error->getMessage()!=='Fallo simulado al escribir.') throw $error; }
    if (!$db->pending || $db->sent || !$db->rolledBack || $db->attempts!==$before+1) throw new \RuntimeException('No conservo el pendiente para reintento.');
    echo 'OK: opciones driver 2.x, envio confirmado, reintento sin duplicar y rollback al fallar.'.PHP_EOL;
}
