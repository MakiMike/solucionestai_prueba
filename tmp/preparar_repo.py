from pathlib import Path
import re, shutil
root=Path('k8s/minikube')
for name in ['inicializar-tienda.yaml','inicializar-mongo.yaml','verificar-bases.yaml','desarrollo/inicializar-tienda.yaml','desarrollo/verificar-bases.yaml']:
 p=root/name
 p.write_text(re.sub(r'tienda-backend:0\.1\.\d+','tienda-backend:0.1.6',p.read_text(encoding='utf-8')),encoding='utf-8')
s=(root/'desarrollo/Preparar-Credenciales.ps1').read_text(encoding='utf-8')
s=s.replace("'..\\Seguridad-Respaldos.ps1'","'Seguridad-Respaldos.ps1'").replace("'..\\..\\tls\\dev-private'","'..\\tls\\prod-private'")
s=s.replace('desarrollo','produccion').replace('DevAdmin-','ProdAdmin-').replace('DevCliente-','ProdCliente-')
(root/'Preparar-Credenciales.ps1').write_text(s,encoding='utf-8')
p=Path('docs/ACCESO_CELULAR.md')
s=p.read_text(encoding='utf-8').replace('Esto no demuestra aun acceso del telefono.','El 31/10/2026 el usuario confirmo desde el telefono que produccion permite navegar y desarrollo no conecta. Es una confirmacion manual, sin captura nueva adjunta.')
p.write_text(s,encoding='utf-8')
p=root/'desarrollo/README.md';s=p.read_text(encoding='utf-8')
s=s.replace('Frontend 0.1.1 y backend candidato 0.1.4','Frontend 0.1.1 y backend 0.1.6')
s='Manual completo para un clon nuevo: [README principal](../../../README.md).\n\n'+s
p.write_text(s,encoding='utf-8')
for folder, dest in [('output/reporte/diagramas','docs/diagramas'),('output/reporte/anexos','docs/datos')]:
 d=Path(dest);d.mkdir(parents=True,exist_ok=True)
 names=['diccionario-postgresql.csv','restricciones-postgresql.csv','diccionario-mongodb.csv','modelo-mongo.json']
 for p in Path(folder).iterdir():
  if p.suffix=='.svg' or p.name in names:shutil.copy2(p,d/p.name)
print('Manifiestos iniciales, generador y anexos fuente preparados.')
