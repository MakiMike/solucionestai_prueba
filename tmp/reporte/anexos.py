from pathlib import Path
import json,csv,shutil,zipfile
O=Path('output/reporte'); A=O/'anexos'; A.mkdir(exist_ok=True)
def csvwrite(name,fields,rows):
 with (A/name).open('w',newline='',encoding='utf-8-sig') as f:
  w=csv.writer(f);w.writerow(fields);w.writerows(rows)
csvwrite('diccionario-mongodb.csv',['Campo','Tipo BSON','Regla','Uso'],[
 ['_id','ObjectId','Generado por MongoDB','Identificador interno'],
 ['evento_id','string','Obligatorio; índice único evento_unico','Idempotencia del outbox'],
 ['pedido_id','int o long','Obligatorio; índice pedido_fecha','Relación lógica con PostgreSQL'],
 ['tipo','string','Obligatorio','reserva_creada, pago_confirmado y cambios de estado'],
 ['fecha','date','Obligatorio; no string','Orden cronológico'],
 ['actor_id','int, long o null','Opcional','Usuario o tarea que originó el evento'],
 ['datos','object','Obligatorio','Detalle del evento; no es la fuente autoritativa de stock']])
schema={'coleccion':'eventos','nota':'Ejemplo sintético, no registro extraído de un usuario real','documento':{'evento_id':'ejemplo-uuid','pedido_id':1,'tipo':'reserva_creada','fecha':{'$date':'2026-10-31T00:00:00Z'},'actor_id':1,'datos':{'estado':'pendiente_pago'}},'validador':{'$jsonSchema':{'bsonType':'object','required':['evento_id','pedido_id','tipo','fecha','datos'],'properties':{'evento_id':{'bsonType':'string'},'pedido_id':{'bsonType':['int','long']},'tipo':{'bsonType':'string'},'fecha':{'bsonType':'date'},'actor_id':{'bsonType':['int','long','null']},'datos':{'bsonType':'object'}}}},'indices':[{'nombre':'_id_','claves':{'_id':1},'unique':True},{'nombre':'evento_unico','claves':{'evento_id':1},'unique':True},{'nombre':'pedido_fecha','claves':{'pedido_id':1,'fecha':-1}}],'consultas':['db.eventos.find({pedido_id:1}).sort({fecha:-1})','db.eventos.find({evento_id:"ejemplo-uuid"})'],'presupuesto':'Clientes con conexiones acotadas; dos réplicas backend en reposo, hasta seis con HPA y Jobs concurrentes. No se midió el máximo real de conexiones. Revisar pools y límites antes de aumentar réplicas.'}
(A/'modelo-mongo.json').write_text(json.dumps(schema,ensure_ascii=False,indent=2),encoding='utf-8')
rows=[
('E01','25–31/10/2026','dev/prod','Dos contextos y topologías sanas','get nodes; get deployments,statefulsets,pvc','1 nodo dev; 3 prod; cargas Ready','Aprobada','Un nodo dev y tres prod Ready; backend/frontend 1/1 dev y 2/2 prod','Captura 1; diagrama 4/5; registros de reanudación'),
('E02','26–31/10/2026','dev/prod/LAN','HTTPS distinto y datos separados','Navegar ambos sitios; comparar datos/PVC; probar teléfono','Acceso autorizado sin cruce de datos','Aprobada','URLs, datos y PVC distintos; usuario confirmó prod accesible y dev bloqueado desde teléfono','31/10: confirmación del usuario, sin captura nueva del teléfono'),
('E03','27/10/2026','dev','CRUD de tres entidades, roles y validaciones desde interfaz y API','Reservar/cancelar/entregar; límite acumulado; pedido ajeno','Operaciones válidas aceptadas; accesos/cantidades inválidas rechazados','Parcial','Estados, stock, límite de dos unidades y autorización comprobados; falta matriz íntegra CRUD/API','resultados/funcionales.md; captura 2'),
('E04','25–27/10/2026','dev/prod/restore','Uso persistente de ambas bases con consultas trazables','Verificar SQL, outbox, sincronizador e historial UI','SQL autoritativo e historial Mongo relacionado','Aprobada','Datos SQL, índices y eventos sincronizados visibles; Job compatibilidad sincronizó dos eventos','Diccionarios; captura 2; verificación SQL'),
('E05','25–27/10/2026','prod/restore','Recrear Pods de datos sin pérdida','Pruebas de persistencia y entorno recuperado','Conteos previos/posteriores iguales','Parcial','PVC y recuperación documentados; no se presenta matriz antes/después completa de ambos Pods productivos','resultados/restauracion; captura 5'),
('E06','26/10/2026','prod','HPA crece y disminuye con series y carga adjuntas','Job 24 concurrentes por 360 s; get hpa --watch','Réplicas >2 y posterior reducción','Parcial','2→4→6 y descenso a 3; mínimo de dos observado posteriormente. Falta serie completa exportada de una misma ventana','Captura 6; 29,360 solicitudes; cero errores; 81.56 req/s'),
('E07','26–31/10/2026','prod','Tráfico por varias réplicas y distribución entre workers','get pods -o wide; tráfico HTTP','Réplicas separadas y solicitudes identificadas por réplica','Parcial','Réplicas entre m02/m03; no hay conteo/identificador explícito de solicitudes por Pod','Diagrama 5 y registros de distribución'),
('E08','25–26/10/2026','prod','Políticas allow/deny con DNS y servicio sano','Comprobar-Red.ps1','Backend accede; frontend no; DNS resuelve','Parcial','PG/Mongo accesibles desde backend; Mongo bloqueado desde frontend con resolución DNS correcta','Captura original Comprobar-Red; falta prueba aislada completa ingress/egress'),
('E09','26–27/10/2026','dev/prod','Roles app, RBAC y cuentas DB limitadas','Comprobar-Permisos.ps1; acceso app ajena; cuenta tienda_app','Mínimo privilegio y rechazos','Parcial','12/12 RBAC, Secret Forbidden, rol app y cuentas limitadas; falta batería negativa DB completa','rbac.csv; resultados/funcionales.md'),
('E10','26–31/10/2026','monitoreo/prod','Paneles y dos alertas activadas/resueltas','Bloquear solo exporters; consultar ALERTS; restablecer NP','Dos alertas firing y posterior recuperación','Parcial','PG/Mongo alertaron y ambas métricas regresaron a 1. Faltan logs centralizados y matriz de cinco alertas requeridas','Captura 4; reglas alertas-bases.yaml; no fue caída real de DB'),
('E11','25/10/2026','prod','Fallo de Pod y mantenimiento de worker medidos','Eliminar un backend; drain con selector app; uncordon','Recuperación y medición de impacto','Parcial','Pod:81 respuestas 200/0 fallos/90.3 s; drain parcial:159 respuestas 200/1 HTTP502/180.3 s','No fue vaciado completo ni fallo físico de worker'),
('E12','26–27/10/2026','prod','Actualizar y volver con versión/función comprobadas','set image; rollout undo --to-revision=6; curl salud','Nueva versión y retorno anterior funcionales','Aprobada','0.1.2→0.1.1→0.1.2; rollout correcto y HTTP200; después se promovieron correcciones hasta 0.1.6','Captura 3; no se revierten migraciones por rollout undo'),
('E13','25–26/10/2026','restore','Restaurar ambos motores y registrar RPO/RTO','Respaldar-Bases.ps1; Restaurar-Prueba.ps1','Conteos, índices, archivos y app recuperados; RPO/RTO medidos','Parcial','SHA256, relaciones, índices y app restaurada comprobados; 7.1 s corresponde solo a continuación','Captura 5; resultados/restauracion; RPO24h/RTO30min son objetivos'),
('E14','31/10/2026','ambos','Reconstruir limpio desde manual sin secretos Git','Seguir D05 en recursos nuevos','Sin pasos tácitos; código etiquetado','Pendiente','Manual y artefactos disponibles; no se ejecutó reconstrucción limpia integral; bootstrap conserva imagen 0.1.0','No destruir perfiles existentes para obtener esta evidencia')]
csvwrite('registro-E01-E14.csv',['ID','Fecha','Ambiente','Requisito','Acción/comando','Esperado','Estado','Observado','Evidencia y limitación'],rows)
B=json.loads((O/'contenido.json').read_text(encoding='utf-8'))
start=next(i for i,x in enumerate(B) if x.get('text','').startswith('D05.'))
end=next(i for i,x in enumerate(B) if x.get('text','').startswith('D06.'))
manual=['# Manual de instalación y operación — TSkull-Shirt','Corte: 31/10/2026. Instrucciones documentadas; no certifican reconstrucción limpia. No ejecutar una instalación nueva sobre los perfiles de la entrega.']
for x in B[start:end]:
 if x['type']=='code':manual+=['```powershell',x['text'].replace('\\\\','\\'),'```']
 elif x['type'] in ('h1','h2'):manual+=['## '+x['text']]
 elif 'text'in x:manual+=[x['text']]
manual+=['## Condiciones pendientes antes de reconstruir desde cero',
'Los Jobs inicializar-tienda.yaml/inicializar-mongo.yaml conservan referencias a tienda-backend:0.1.0. El despliegue final usa 0.1.6. Alinear y validar esas referencias en una instalación de prueba; no sustituirlas silenciosamente ni prometer que una imagen antigua contiene todas las migraciones actuales.',
'La migración/semilla se ejecuta por Job, no en cada réplica. No resembrar ni copiar producción hacia desarrollo. El material privado bajo k8s/tls no forma parte del repositorio ni de este paquete.',
'La habilitación inicial de cifrado etcd modifica el API server: requiere su copia protegida de clave y un plan de recuperación. El arranque de un perfil ya cifrado utiliza el parámetro encryption-provider-config documentado. Un perfil nuevo todavía no tiene ese archivo: prepararlo antes de usar ese parámetro.',
'La prueba E14, la etiqueta Git final, la centralización de logs, el máximo de conexiones y la medición integral de RPO/RTO continúan pendientes. No se realizaron instalaciones, despliegues ni cambios de datos para redactar este informe.',
'## Solución de incidencias observadas',
'Ruta YAML inexistente: ubicarse en C:\\laragon\\www\\sistema o usar ruta absoluta. No ejecutar apply desde C:\\Users\\josem con rutas relativas del proyecto.',
'Puerto ocupado: conservar el proceso conocido o elegir puerto libre; restauración usó 9081 en vez de 9080. No terminar procesos desconocidos.',
'PowerShell: primero asignar $revisionAnterior y después validarla en otra línea. Guardar revisión e imagen antes de actualizar. rollout undo no revierte el esquema SQL.',
'Reinicio prolongado: comprobar estado de Calico y DNS. Un error de token del CNI requirió renovar sus Pods mediante un rollout controlado; no borrar datos del perfil.',
'Restauración tar: diferencias de permisos/GID no equivalen a corrupción de contenido. La validación final usa SHA256 de archivos y verifica registros e índices; conserva el intento fallido y no suma solo el tiempo de continuación como RTO total.',
'Exporters Running con pg_up/mongodb_up=0: revisar autenticación, TLS y políticas; Running no acredita acceso al motor. La corrección MongoDB 2.x usa las opciones esperadas por el driver y fue validada mediante Job.',
'Acceso por IP con 404: comprobar Host, regla IngressRoute y proveedor CRD de Traefik. Una conexión local correcta no demuestra por sí sola el acceso LAN ni el bloqueo de desarrollo.']
(A/'manual-instalacion-operacion.md').write_text('\n\n'.join(manual)+'\n',encoding='utf-8')
safe={
 'output/evidencias/funcionales/agente-20261027/RESULTADOS.md':'resultados/funcionales.md',
 'output/evidencias/funcionales/agente-20261027/verificacion-final-dev.txt':'resultados/verificacion-final-dev.txt',
 'output/evidencias/restauracion/20261025-235828/resultado.json':'resultados/restauracion/resultado.json',
 'output/evidencias/restauracion/20261025-235828/conteos-postgres.txt':'resultados/restauracion/conteos-postgres.txt',
 'output/evidencias/restauracion/20261025-235828/verificacion-mongo.txt':'resultados/restauracion/verificacion-mongo.txt',
 'output/evidencias/restauracion/20261025-235828/verificacion-imagenes.txt':'resultados/restauracion/verificacion-imagenes.txt',
 'output/evidencias/tls-postgres/comprobacion-20261026-010203.txt':'resultados/tls-postgres.txt',
 'output/evidencias/tls-mongo/comprobacion-20261026-011113.txt':'resultados/tls-mongo.txt',
 'output/evidencias/imagenes/backend-cves.txt':'resultados/vulnerabilidades/backend-0.1.2.txt',
 'output/evidencias/imagenes/backend-0.1.3-cves.txt':'resultados/vulnerabilidades/backend-0.1.3.txt',
 'output/evidencias/imagenes/frontend-cves.txt':'resultados/vulnerabilidades/frontend-0.1.0.txt',
 'output/evidencias/imagenes/frontend-0.1.1-cves.txt':'resultados/vulnerabilidades/frontend-0.1.1.txt'}
for src,dst in safe.items():
 s=Path(src)
 if s.exists():
  d=A/dst;d.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(s,d)
for src in ['output/evidencias/funcionales/agente-20261027/04-historial-entrega-cliente.jpg','output/evidencias/cierre-20261027/produccion-pedidos-cliente.jpg']:
 shutil.copy2(src,O/'capturas'/Path(src).name)
# Registrar la confirmación humana sin convertirla en una medición automatizada.
p=Path('output/evidencias/acceso-celular/resultado-local.json');status=json.loads(p.read_text(encoding='utf-8-sig'))
status.update({'PruebaTelefono':'Confirmada por el usuario: producción accesible y desarrollo bloqueado','FechaConfirmacionUsuario':'2026-10-31','FuenteConfirmacion':'Mensaje del usuario en esta conversación; sin captura nueva adjunta'})
p.write_text(json.dumps(status,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
shutil.copy2(p,A/'resultados/acceso-telefono.json')
(A/'LEEME.md').write_text('''# Anexos del reporte TSkull-Shirt

Corte 31/10/2026. Acompañan al documento Kubernetes Practica. El registro E01–E14 distingue aprobaciones, alcances parciales y pendientes. Las imágenes son capturas históricas de la ejecución, no pruebas nuevas de la versión 0.1.6.

- diagramas/: siete diagramas Minikube en SVG editable y PNG; son esquemas, no capturas.
- capturas/: siete imágenes esenciales usadas en el informe.
- diccionario-postgresql.csv / restricciones-postgresql.csv: metadatos de columnas y restricciones; sin filas de clientes ni contraseñas.
- diccionario-mongodb.csv / modelo-mongo.json: estructura y ejemplo sintético identificado como tal.
- manual-instalacion-operacion.md: procedimientos y limitaciones del manual.
- registro-E01-E14.csv: fecha, ambiente, requisito, acción, esperado, observado y limitación.
- rendimiento-catalogo.csv / rbac.csv / resultados/: salidas seleccionadas originales. Los análisis de CVE no certifican la imagen final 0.1.6.

No contiene dumps de bases, archivos de credenciales, Secret YAML/JSON, claves TLS, kubeconfig ni respaldos descifrados. Los respaldos reales permanecen privados en la computadora del autor. No es un paquete de despliegue completo ni una evidencia de reconstrucción limpia.
''',encoding='utf-8')
with zipfile.ZipFile(O/'Anexos_TSkull_Shirt.zip','w',zipfile.ZIP_DEFLATED) as z:
 for folder in [A,O/'diagramas',O/'capturas']:
  for p in sorted(folder.rglob('*')):
   if p.is_file():z.write(p,p.relative_to(O))
print('Anexos:',len(list(A.rglob('*'))),'ZIP bytes:',(O/'Anexos_TSkull_Shirt.zip').stat().st_size)
