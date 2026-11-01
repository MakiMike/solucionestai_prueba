-- La instalación original no tiene valor por defecto para us_id.
DO $$
BEGIN
 IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=current_schema() AND table_name='us_usuarios' AND column_name='us_id' AND (column_default IS NOT NULL OR is_identity='YES')) THEN
  CREATE SEQUENCE IF NOT EXISTS tienda_usuario_id_seq;
  PERFORM setval('tienda_usuario_id_seq', COALESCE((SELECT MAX(us_id) FROM us_usuarios),0)+1, false);
  ALTER TABLE us_usuarios ALTER COLUMN us_id SET DEFAULT nextval('tienda_usuario_id_seq');
  ALTER SEQUENCE tienda_usuario_id_seq OWNED BY us_usuarios.us_id;
 END IF;
END $$;
