-- Instalaciones nuevas. Las tablas existentes se conservan.
CREATE TABLE IF NOT EXISTS us_usuarios (
 us_id serial PRIMARY KEY, us_nombre varchar(150) NOT NULL,
 us_correo varchar(254) NOT NULL UNIQUE, us_telefono varchar(15) UNIQUE,
 us_contrasenia varchar(255) NOT NULL, us_rol char(1) NOT NULL DEFAULT 'U',
 us_sexo char(1) NOT NULL DEFAULT 'O', created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
 us_reset_token varchar(255), us_reset_token_expires timestamp,
 CHECK (us_rol IN ('A','U')), CHECK (us_sexo IN ('H','M','O'))
);
CREATE TABLE IF NOT EXISTS tallas (tal_id serial PRIMARY KEY, tal_nombre varchar(10) NOT NULL UNIQUE, tal_orden integer NOT NULL);
CREATE TABLE IF NOT EXISTS playeras (
 pla_id serial PRIMARY KEY, pla_nombre varchar(180) NOT NULL, pla_descripcion text,
 pla_genero char(1) NOT NULL CHECK (pla_genero IN ('H','M','U')),
 pla_precio numeric(12,2) NOT NULL CHECK (pla_precio >= 0), pla_imagen varchar(255),
 pla_activa boolean NOT NULL DEFAULT TRUE, created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS playera_tallas (
 pta_id serial PRIMARY KEY, pla_id integer NOT NULL REFERENCES playeras,
 tal_id integer NOT NULL REFERENCES tallas, pta_stock integer NOT NULL DEFAULT 0 CHECK (pta_stock >= 0),
 UNIQUE(pla_id,tal_id)
);
CREATE TABLE IF NOT EXISTS pedidos (
 ped_id serial PRIMARY KEY, us_id integer NOT NULL REFERENCES us_usuarios,
 ped_fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ped_estado varchar(30) NOT NULL DEFAULT 'pendiente_pago',
 ped_total numeric(12,2) NOT NULL DEFAULT 0 CHECK (ped_total >= 0)
);
CREATE TABLE IF NOT EXISTS pedido_detalle (
 pde_id serial PRIMARY KEY, ped_id integer NOT NULL REFERENCES pedidos,
 pla_id integer NOT NULL REFERENCES playeras, tal_id integer NOT NULL REFERENCES tallas,
 pde_cantidad integer NOT NULL CHECK (pde_cantidad > 0), pde_precio numeric(12,2) NOT NULL CHECK (pde_precio >= 0)
);
