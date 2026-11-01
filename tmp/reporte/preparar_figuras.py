from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
from html import escape
import math, json, shutil
O=Path('output/reporte'); O.mkdir(parents=True,exist_ok=True)
D=O/'diagramas'; D.mkdir(exist_ok=True)
FONT='C:/Windows/Fonts/arial.ttf'; BOLD='C:/Windows/Fonts/arialbd.ttf'
class Fig:
 def __init__(self,name,title,h=730):
  self.name=name; self.h=h; self.im=Image.new('RGB',(1200,h),'white'); self.d=ImageDraw.Draw(self.im)
  self.svg=[f'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="{h}" viewBox="0 0 1200 {h}"><rect width="1200" height="{h}" fill="white"/>']
  self.text(28,20,title,30,True)
 def text(self,x,y,s,size=23,bold=False,center=False,color='#243247'):
  font=ImageFont.truetype(BOLD if bold else FONT,size)
  for i,line in enumerate(s.split('\n')):
   yy=y+i*(size+7); xx=x-self.d.textlength(line,font=font)/2 if center else x
   self.d.text((xx,yy),line,font=font,fill=color)
   self.svg.append(f'<text x="{xx}" y="{yy+size}" font-family="Arial" font-size="{size}" font-weight="{700 if bold else 400}" fill="{color}">{escape(line)}</text>')
 def box(self,x,y,w,h,title,body='',color='#edf2f7',size=23):
  self.d.rounded_rectangle((x,y,x+w,y+h),10,fill=color,outline='#91a3b7',width=2)
  self.svg.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="10" fill="{color}" stroke="#91a3b7" stroke-width="2"/>')
  self.text(x+w/2,y+10,title,size,True,True)
  if body:self.text(x+w/2,y+47,body,size-1,False,True)
 def arrow(self,pts,label='',labelpos=None):
  self.d.line(pts,fill='#425d78',width=3)
  x,y=pts[-1];px,py=pts[-2];a=math.atan2(y-py,x-px); tri=[(x,y),(x-12*math.cos(a-.5),y-12*math.sin(a-.5)),(x-12*math.cos(a+.5),y-12*math.sin(a+.5))]
  self.d.polygon(tri,fill='#425d78');self.svg.append('<polyline points="'+' '.join(f'{a},{b}' for a,b in pts)+'" fill="none" stroke="#425d78" stroke-width="3"/>');self.svg.append('<polygon points="'+' '.join(f'{a},{b}' for a,b in tri)+'" fill="#425d78"/>')
  if label:self.text(*labelpos,label,19,False,True)
 def save(self):
  self.text(28,self.h-28,'TSkull-Shirt | Minikube | Corte: 31-10-2026 | Diagrama, no captura de pantalla',17)
  self.im.save(D/(self.name+'.png')); (D/(self.name+'.svg')).write_text('\n'.join(self.svg)+'</svg>',encoding='utf-8')

f=Fig('01_aplicacion','1. Arquitectura de la aplicación',660)
for x,t,b in [(25,'Navegador','Cliente / administrador'),(330,'Traefik','TLS y rutas HTTPS'),(635,'Nginx','HTML, CSS y proxy'),(940,'PHP-FPM','CodeIgniter 3.1.13')]:f.box(x,100,235,110,t,b,size=21)
for a,b,l in [(260,330,'9443/9444'),(565,635,'HTTP 8080'),(870,940,'FastCGI 9000')]:f.arrow([(a,155),(b,155)],l,((a+b)/2,215))
f.box(30,395,270,145,'CronJobs','Vencimiento\nSincronizar outbox\nCada minuto',color='#e9f0fa')
f.box(390,395,335,145,'PostgreSQL 17.6','Usuarios, catálogo, stock\nPedidos, sesiones, outbox',color='#e5f2eb')
f.box(825,395,340,145,'MongoDB 8.0.14','Historial de pedidos\nÍndice evento_id único',color='#e5f2eb')
f.text(575,280,'Backend: autenticación, CSRF, catálogo, reservas y estados',22,True,True)
f.arrow([(1055,210),(1055,330),(555,330),(555,395)],'TLS 5432',(675,345))
f.arrow([(1100,210),(1180,210),(1180,365),(1000,365),(1000,395)],'TLS 27017',(1025,340))
f.arrow([(300,470),(390,470)],'SQL',(345,425))
f.arrow([(165,540),(165,560),(1000,560),(1000,540)],'Envío TLS 27017',(760,536))
f.text(50,585,'PostgreSQL decide la compra. MongoDB conserva el historial con consistencia eventual.',23)
f.save()

def sequence(name,title,names,steps,notes):
 f=Fig(name,title,800); xs=[120+i*960/(len(names)-1) for i in range(len(names))]
 for x,n in zip(xs,names):
  f.box(x-105,80,210,62,n,size=20)
  f.d.line((x,145,x,705),fill='#c6cfd9',width=2);f.svg.append(f'<line x1="{x}" y1="145" x2="{x}" y2="705" stroke="#c6cfd9"/>')
 for i,(a,b,t) in enumerate(steps):
  y=175+i*65;f.text(600,y,t,21,False,True);f.arrow([(xs[a],y+36),(xs[b],y+36)])
 f.text(35,720,notes,20); f.save()
sequence('02_crud','2. Secuencia CRUD: modificar una prenda',['Administrador','Nginx','Controlador','PostgreSQL'],[
 (0,1,'1. GET formulario de producto'),(1,2,'2. Validar sesión y rol de administrador'),(2,3,'3. Consultar producto y tallas'),(3,0,'4. Renderizar datos y token CSRF'),(0,2,'5. POST con datos editados y CSRF'),(2,3,'6. Validar campos; transacción de producto y variantes'),(3,2,'7. COMMIT o ROLLBACK'),(2,0,'8. Confirmación o error sin cambios parciales')],
 'Sin rol o CSRF: rechazar. Cantidad inválida: corregir. Error SQL: revertir.')
sequence('03_outbox','3. Reserva y recuperación del historial',['Cliente','Backend','PostgreSQL','CronJob','MongoDB'],[
 (0,1,'1. Crear pedido; el carrito todavía no reserva'),(1,2,'2. Bloquear stock y guardar pedido + evento outbox'),(2,0,'3. COMMIT; reserva válida durante 30 minutos'),(3,2,'4. Leer evento pendiente con bloqueo de fila'),(3,4,'5. Upsert por evento_id; clave única'),(4,3,'6. Si falla MongoDB: conservar evento pendiente'),(3,4,'7. Reintentar el mismo evento al recuperar MongoDB'),(3,2,'8. Solo tras confirmar escritura: marcar enviado')],
 'No hay transacción distribuida. Repetir el envío no duplica el documento.')

f=Fig('04_desarrollo','4. Desarrollo: perfil y contexto tienda-dev',700)
f.box(25,80,1150,95,'Docker Desktop / Windows / WSL 2','Nodo tienda-dev: 192.168.49.2 | Namespace tienda-dev',size=25)
f.box(30,220,330,110,'Entrada local HTTPS','127.0.0.1:9444\nTraefik / Ingress',color='#e9f0fa')
f.box(440,220,300,110,'Frontend x1','Service :8080',color='#f0eafb');f.box(825,220,345,110,'Backend x1','Service :9000',color='#f0eafb')
f.arrow([(360,275),(440,275)]);f.arrow([(740,275),(825,275)])
f.box(40,415,330,135,'PostgreSQL x1','StatefulSet\nPVC 2 GiB / RWO',color='#e5f2eb')
f.box(440,415,300,135,'MongoDB x1','StatefulSet\nPVC 2 GiB / RWO',color='#e5f2eb')
f.box(825,415,345,135,'Archivos uploads','PVC 1 GiB / RWO\nMismo nodo',color='#e5f2eb')
f.text(40,585,'CSI hostpath | Calico | CoreDNS | Metrics Server | CronJobs',23,True)
f.text(40,620,'Un solo nodo y una réplica por capa. Bases internas; HTTPS en la entrada.',22);f.save()

f=Fig('05_produccion','5. Producción: perfil y contexto tienda-prod',850)
f.text(40,75,'Una laptop compartida | Docker Desktop / WSL 2 | Tres nodos contenedor',23)
for x,t,ip in [(25,'Control plane','192.168.58.2'),(420,'Worker m02','192.168.58.3'),(815,'Worker m03','192.168.58.4')]:
 f.box(x,135,360,88,t,ip,size=24)
f.box(25,260,360,160,'tienda-storage','NFS único + PVC 5 GiB\nCSI NFS / shared-rwx\nuploads RWX de 2 GiB',color='#e5f2eb')
f.box(420,260,360,160,'tienda-prod','Frontend x1\nBackend x1\nPG x1 + Mongo x1',color='#f0eafb')
f.box(815,260,360,160,'tienda-prod','Frontend x1\nBackend x1\nRéplicas adicionales: HPA',color='#f0eafb')
f.box(25,465,360,150,'Persistencia de BD','PG: PVC RWO 5 GiB\nMongo: PVC RWO 5 GiB\nCSI hostpath en m02',color='#e5f2eb')
f.box(420,465,360,150,'Entrada y operación','Traefik x2, Ingress\nHPA 2–6, CPU 70 %\nPDB: minAvailable 1',color='#e9f0fa')
f.box(815,465,360,150,'monitoreo','Prometheus + Grafana\nAlertmanager + exporters\nPVC 2 / 1 / 1 GiB',color='#e9f0fa')
f.text(30,660,'La ubicación de las réplicas de aplicación y de las BD coincide con la consulta del corte.',20)
f.text(30,700,'Los Pods de plataforma pueden reprogramarse; las bases locales quedan ligadas al volumen.',20)
f.text(30,745,'Límites: host único, control plane único, NFS único y una instancia por motor.',22,True);f.save()

f=Fig('06_red','6. Red y recorrido de una solicitud',820)
f.box(25,85,1150,95,'LAN de casa','Gateway 192.168.100.1 | PC Ethernet 192.168.100.3 | Sin VLAN ni VPN configurada',size=22)
f.box(25,230,550,145,'Windows Firewall + port-forward','Prod: 127.0.0.1 y LAN :9443\nDev: solo 127.0.0.1:9444\nGrafana: solo 127.0.0.1:3000',color='#e9f0fa')
f.box(650,230,525,145,'Docker Desktop / NAT','Red prod: 192.168.58.0/24\nRed dev: 192.168.49.0/24\nAPI: puertos locales de Docker',color='#e9f0fa')
f.arrow([(575,300),(650,300)])
f.box(25,440,550,160,'Clústeres independientes','Pods: 10.244.0.0/16\nServices: 10.96.0.0/12\nCalico: IP-in-IP; DNS UDP/TCP 53',color='#e9f0fa')
f.box(650,440,525,160,'Ruta de producción','TLS → Traefik → frontend:8080\nFastCGI → backend:9000\nTLS → PG:5432 / Mongo:27017',color='#f0eafb')
f.text(30,650,'Exporters: PG 9187 y Mongo 9216; NFS TCP 2049. Acceso interno por políticas explícitas.',21)
f.text(30,695,'Los mismos CIDR de Pods/Services se repiten en redes Docker separadas; no se enrutan entre sí.',20)
f.text(30,740,'No hay VIP externa ni balanceador de nube. El port-forward depende de la PC y de la API.',22,True);f.save()

f=Fig('07_datos','7. Modelo relacional y vínculo con el historial',800)
f.box(25,100,330,140,'us_usuarios','PK us_id | correo UNIQUE\nRol A/U | baja lógica',color='#e5f2eb')
f.box(435,100,330,140,'pedidos','PK ped_id | FK us_id\nEstado, total, plazo\nClave idempotente',color='#e5f2eb')
f.box(845,100,330,140,'tienda_outbox','PK evento_id | FK ped_id\nJSONB, fecha, enviado\nIntentos y error',color='#e5f2eb')
f.box(25,380,330,140,'playeras','PK pla_id | precio ≥ 0\nEdición limitada\nActiva / baja lógica',color='#e5f2eb')
f.box(435,380,330,140,'pedido_detalle','PK pde_id\nFK pedido, prenda, talla\nCantidad > 0 y precio',color='#e5f2eb')
f.box(845,380,330,140,'eventos en MongoDB','evento_id UNIQUE\npedido_id + fecha\nTipo, actor y datos',color='#f0eafb')
f.box(25,625,330,95,'tallas','PK tal_id | nombre UNIQUE',color='#e5f2eb')
f.box(435,625,330,110,'playera_tallas','FK pla_id + tal_id UNIQUE\nStock ≥ 0',color='#e5f2eb',size=21)
f.arrow([(355,165),(435,165)],'1:N',(395,125));f.arrow([(765,165),(845,165)],'1:N',(805,125))
f.arrow([(600,240),(600,380)],'1:N',(645,300));f.arrow([(355,450),(435,450)],'1:N',(395,410))
f.arrow([(190,520),(190,570),(600,570),(600,625)])
f.arrow([(355,675),(435,675)],'1:N',(395,635));f.arrow([(1010,240),(1010,380)],'Outbox',(1060,300))
f.arrow([(355,650),(405,650),(405,500),(435,500)])
f.text(830,620,'El historial no sustituye\nlas relaciones ni la\ntransacción PostgreSQL.',21);f.save()

# Contactos para seleccionar originales sin alterar las capturas.
files=sorted(Path(r'C:\Users\josem\AppData\Local\Temp').glob('codex-clipboard-*.png'),key=lambda p:p.stat().st_mtime)
selected=files[:24]+files[-48:]
Path('tmp/reporte/contact-map.txt').write_text('\n'.join(f'{i+1} {p}' for i,p in enumerate(selected)),encoding='utf-8')
for start in range(0,len(selected),24):
 im=Image.new('RGB',(1200,1760),'#eeeeee'); dr=ImageDraw.Draw(im); font=ImageFont.truetype(FONT,18)
 for k,p in enumerate(selected[start:start+24]):
  thumb=Image.open(p).convert('RGB'); thumb.thumbnail((385,183)); x=(k%3)*400+7;y=(k//3)*220+25
  im.paste(thumb,(x,y));dr.text((x,y-22),str(start+k+1)+' '+p.stem[-8:],font=font,fill='black')
 im.save(f'tmp/reporte/contact-{start//24+1}.jpg')
print('Diagramas PNG/SVG:',len(list(D.glob('*.svg'))),'Contactos:',len(selected))
