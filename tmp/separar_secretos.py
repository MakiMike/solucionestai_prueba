from pathlib import Path
p=Path('tmp/generar_k8s.py');s=p.read_text(encoding='utf8');s=s.replace("def envfrom(c): c['envFrom']=[{'configMapRef':{'name':'tienda-config'}},{'secretRef':{'name':'tienda-secrets'}}];return c", "def envfrom(c): c['envFrom']=[{'configMapRef':{'name':'tienda-config'}}];c['env']=[{'name':key,'valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':key}}} for key in ['DB_PASSWORD','MONGO_URI','APP_ENCRYPTION_KEY']];return c")
s=s.replace("c['command']=['sh','-c','php bin/tienda.php migrate", "c['env'] += [{'name':key,'valueFrom':{'secretKeyRef':{'name':'tienda-secrets','key':key}}} for key in ['SEED_ADMIN_PASSWORD','SEED_CLIENT_PASSWORD']];c['command']=['sh','-c','php bin/tienda.php migrate")
p.write_text(s,encoding='utf8')
p=Path('compose.yaml');s=p.read_text();s=s.replace('    env_file: .env\n','');s=s.replace('    environment: {DB_HOST: postgres, DB_DATABASE: tienda, DB_USERNAME: tienda_app, APP_SESSION_DRIVER: database}', '''    environment: &app-environment
      CI_ENV: production
      APP_BASE_URL: http://localhost:8080/
      APP_COOKIE_SECURE: 'false'
      APP_SESSION_DRIVER: database
      APP_ENCRYPTION_KEY: ${APP_ENCRYPTION_KEY:?Define APP_ENCRYPTION_KEY}
      DB_HOST: postgres
      DB_DATABASE: tienda
      DB_USERNAME: tienda_app
      DB_PASSWORD: ${DB_PASSWORD:?Define DB_PASSWORD}
      MONGO_URI: ${MONGO_URI:?Define MONGO_URI}
      MONGO_DATABASE: tienda''').replace('    environment: {DB_HOST: postgres, DB_DATABASE: tienda, DB_USERNAME: tienda_app}', '    environment: *app-environment');p.write_text(s)
p=Path('.env.example');s=p.read_text();s+='\nDB_OWNER_PASSWORD=replace-owner-password\nMONGO_OWNER_PASSWORD=replace-mongo-owner-password\nMONGO_APP_PASSWORD=replace-locally\nMONGO_OWNER_URI=mongodb://tienda_owner:replace-mongo-owner-password@mongo:27017/tienda?authSource=admin\nSEED_ADMIN_PASSWORD=replace-demo-admin-password\nSEED_CLIENT_PASSWORD=replace-demo-client-password\n';p.write_text(s)
p=Path('docs/KUBERNETES.md');s=p.read_text(encoding='utf8').replace('backend php bin/tienda.php seed','-e SEED_ADMIN_PASSWORD=$env:SEED_ADMIN_PASSWORD -e SEED_CLIENT_PASSWORD=$env:SEED_CLIENT_PASSWORD backend php bin/tienda.php seed');p.write_text(s,encoding='utf8')
