from pathlib import Path
import json
p=Path('tests/http.php');s=p.read_text(encoding='utf-8')
s=s.replace("$env=getenv();$env['DB_SCHEMA']", "$admin=$db->prepare(\"INSERT INTO us_usuarios(us_nombre,us_correo,us_contrasenia,us_rol) VALUES ('Admin HTTP','admin-http@demo.test',?,'A')\");$admin->execute(array(password_hash('PruebaSoloLocal123',PASSWORD_DEFAULT)));\n $env=getenv();$env['MONGO_URI']='mongodb://127.0.0.1:1/tienda?serverSelectionTimeoutMS=100';$env['DB_SCHEMA']")
marker="echo 'TOTAL '.$count.' verificaciones HTTP correctas'.PHP_EOL;"
extra='''// Cliente no puede ejecutar CRUD administrativo.
 list($code,$body)=requestHttp('/usuarios');expectHttp(in_array($code,array(302,303),true),'rol cliente rechazado en administracion');
 // Pedido: segunda reserva y cancelacion desde API real; conserva el pagado.
 list($code,$body)=requestHttp('/carrito');$csrf=csrfFrom($body);
 requestHttp('/carrito/agregar',array('pla_id'=>1,'tal_id'=>1,'cantidad'=>1,'csrf_test_name'=>$csrf));
 list($code,$body)=requestHttp('/carrito');$csrf=csrfFrom($body);preg_match('/pedido_token:\\s*"([a-f0-9]+)"/',$body,$token);
 list($code,$body)=requestHttp('/carrito/procesar',array('pedido_token'=>$token[1],'csrf_test_name'=>$csrf));$pending=json_decode($body,true);
 expectHttp($code===200&&!empty($pending['success']),'pedido pendiente creado por API');
 list($code,$body)=requestHttp('/pedidos');$csrf=csrfFrom($body);preg_match('/name="pedido_token" value="([a-f0-9]+)"/',$body,$token);
 requestHttp('/pedidos/accion',array('ped_id'=>$pending['ped_id'],'accion'=>'cancelar','pedido_token'=>$token[1],'csrf_test_name'=>$csrf));
 expectHttp($db->query('SELECT ped_estado FROM pedidos WHERE ped_id='.(int)$pending['ped_id'])->fetchColumn()==='cancelado','baja de pedido por cancelacion');
 expectHttp((int)$db->query('SELECT pta_stock FROM playera_tallas WHERE pla_id=1 AND tal_id=1')->fetchColumn()===2,'cancelacion API devuelve stock sin devolver el pedido pagado');
 requestHttp('/logout');list($code,$body)=requestHttp('/login');$csrf=csrfFrom($body);
 $userFields=array('nombre'=>'Usuario CRUD','email'=>'crud@demo.test','telefono'=>'','sexo'=>'O','password'=>'PruebaSoloLocal123','csrf_test_name'=>$csrf);
 requestHttp('/register/submit',$userFields);
 $uid=$db->query("SELECT us_id FROM us_usuarios WHERE us_correo='crud@demo.test'")->fetchColumn();expectHttp((int)$uid>0,'usuario creado por registro HTTP');
 requestHttp('/register/submit',$userFields);
 expectHttp((int)$db->query("SELECT COUNT(*) FROM us_usuarios WHERE us_correo='crud@demo.test'")->fetchColumn()===1,'correo duplicado rechazado');
 list($code,$body)=requestHttp('/login');$csrf=csrfFrom($body);
 requestHttp('/login/submit',array('email'=>'admin-http@demo.test','password'=>'PruebaSoloLocal123','csrf_test_name'=>$csrf));
 list($code,$body)=requestHttp('/usuarios');expectHttp($code===200,'interfaz de usuarios disponible para administrador');$csrf=csrfFrom($body);
 list($code,$body)=requestHttp('/usuarios/obtener_usuario',array('us_id'=>$uid,'csrf_test_name'=>$csrf));$u=json_decode($body,true);
 expectHttp(!empty($u['success'])&&empty($u['usuario']['us_contrasenia']),'consulta usuario por API sin exponer hash');
 list($code,$body)=requestHttp('/usuarios/actualizar_usuario',array('us_id'=>$uid,'us_nombre'=>'Usuario editado','us_correo'=>'crud@demo.test','us_telefono'=>'','us_sexo'=>'O','us_rol'=>'U','csrf_test_name'=>$csrf));
 expectHttp(!empty(json_decode($body,true)['success'])&&$db->query('SELECT us_nombre FROM us_usuarios WHERE us_id='.(int)$uid)->fetchColumn()==='Usuario editado','usuario actualizado por API');
 requestHttp('/usuarios/eliminar_usuario',array('us_id'=>$uid,'csrf_test_name'=>$csrf));
 expectHttp(!$db->query('SELECT us_activo FROM us_usuarios WHERE us_id='.(int)$uid)->fetchColumn(),'usuario desactivado sin borrar relaciones');
 list($code,$body)=requestHttp('/productos');expectHttp($code===200,'interfaz de productos disponible');$csrf=csrfFrom($body);
 $product=array('pla_nombre'=>'Prenda CRUD','pla_descripcion'=>'Sintetica','pla_genero'=>'H','pla_precio'=>'175','stock_M'=>5,'csrf_test_name'=>$csrf);
 list($code,$body)=requestHttp('/productos/crear',$product);expectHttp(!empty(json_decode($body,true)['success']),'producto creado por API');
 $pid=$db->query("SELECT pla_id FROM playeras WHERE pla_nombre='Prenda CRUD'")->fetchColumn();
 list($code,$body)=requestHttp('/productos/listar');expectHttp($code===200&&strpos($body,'Prenda CRUD')!==false,'consulta productos por API');
 $product['pla_id']=$pid;$product['pla_nombre']='Prenda actualizada';$product['pla_precio']='190';
 list($code,$body)=requestHttp('/productos/editar',$product);expectHttp(!empty(json_decode($body,true)['success'])&&$db->query('SELECT pla_nombre FROM playeras WHERE pla_id='.(int)$pid)->fetchColumn()==='Prenda actualizada','producto editado por API');
 requestHttp('/productos/eliminar',array('pla_id'=>$pid,'csrf_test_name'=>$csrf));
 expectHttp(!$db->query('SELECT pla_activa FROM playeras WHERE pla_id='.(int)$pid)->fetchColumn(),'producto dado de baja logicamente');
 '''
s=s.replace(marker,extra+marker);p.write_text(s,encoding='utf-8')
# Alinear el diccionario con el validador real del sincronizador.
p=Path('docs/datos/diccionario-mongodb.csv');s=p.read_text(encoding='utf-8-sig').replace('evento_id,string,','evento_id,int o long,');p.write_text(s,encoding='utf-8-sig')
p=Path('docs/datos/modelo-mongo.json');d=json.loads(p.read_text());d['documento']['evento_id']=1;d['validador']['$jsonSchema']['properties']['evento_id']={'bsonType':['int','long']};p.write_text(json.dumps(d,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
