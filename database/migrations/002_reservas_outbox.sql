ALTER TABLE playeras ADD COLUMN IF NOT EXISTS pla_edicion_limitada boolean NOT NULL DEFAULT FALSE;
ALTER TABLE pedidos ALTER COLUMN ped_estado TYPE varchar(30);
ALTER TABLE pedidos DROP CONSTRAINT IF EXISTS chk_pedido_estado;
ALTER TABLE pedidos ADD CONSTRAINT chk_pedido_estado CHECK (ped_estado IN ('pendiente_pago','confirmado','en_preparacion','listo_entrega','entregado','cancelado','vencido','pendiente','pagado','procesando','enviado'));
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS ped_clave varchar(32);
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS ped_vence_en timestamptz;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS ped_actualizado_en timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE pedido_detalle ADD COLUMN IF NOT EXISTS pde_nombre varchar(180);
ALTER TABLE pedido_detalle ADD COLUMN IF NOT EXISTS pde_talla varchar(10);
CREATE UNIQUE INDEX IF NOT EXISTS pedidos_clave_cliente ON pedidos(us_id,ped_clave) WHERE ped_clave IS NOT NULL;
CREATE INDEX IF NOT EXISTS pedidos_vencimiento ON pedidos(ped_vence_en) WHERE ped_estado='pendiente_pago';
CREATE INDEX IF NOT EXISTS pedidos_usuario_estado ON pedidos(us_id,ped_estado);
CREATE INDEX IF NOT EXISTS detalles_pedido ON pedido_detalle(ped_id);
CREATE UNIQUE INDEX IF NOT EXISTS stock_variante_unica ON playera_tallas(pla_id,tal_id);
UPDATE pedido_detalle d SET pde_nombre = p.pla_nombre FROM playeras p WHERE p.pla_id=d.pla_id AND d.pde_nombre IS NULL;
UPDATE pedido_detalle d SET pde_talla = t.tal_nombre FROM tallas t WHERE t.tal_id=d.tal_id AND d.pde_talla IS NULL;
-- La tienda anterior registraba todas las compras como pagadas.
UPDATE pedidos SET ped_estado='confirmado' WHERE ped_estado='pagado';
CREATE TABLE IF NOT EXISTS tienda_outbox (
 evento_id bigserial PRIMARY KEY, ped_id integer NOT NULL REFERENCES pedidos,
 actor_id integer REFERENCES us_usuarios, tipo varchar(40) NOT NULL, datos jsonb NOT NULL,
 creado_en timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
 enviado_en timestamptz, intentos integer NOT NULL DEFAULT 0, ultimo_error varchar(120)
);
CREATE INDEX IF NOT EXISTS outbox_pendientes ON tienda_outbox(evento_id) WHERE enviado_en IS NULL;
CREATE TABLE IF NOT EXISTS ci_sessions (id varchar(128) PRIMARY KEY, ip_address varchar(45) NOT NULL, timestamp bigint NOT NULL DEFAULT 0, data text NOT NULL);
CREATE INDEX IF NOT EXISTS ci_sessions_timestamp ON ci_sessions(timestamp);
