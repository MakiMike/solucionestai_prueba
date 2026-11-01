import subprocess, pathlib, re, json, uuid
from PIL import Image
root = pathlib.Path(__file__).parent
base = 'https://tienda-prod.test:9443'
cookie = root / 'cookies.txt'
def curl(path, extra=()):
    return subprocess.check_output(['curl.exe','--silent','--show-error','--noproxy','*','--ssl-revoke-best-effort','--max-time','25','-b',str(cookie),'-c',str(cookie),*extra,base+path],text=True,encoding='utf-8')
def csrf():
    html=curl('/login')
    return re.search(r'token="([a-f0-9]+)"',html).group(1)
token=csrf()
curl('/login/submit',['--data-urlencode','email=admin@tienda.test','--data-urlencode','password=12345678','--data-urlencode','csrf_test_name='+token])
names=[]
for fmt, ext in [('PNG','png'),('JPEG','jpg'),('WEBP','webp')]:
    image=root/('fixture.'+ext)
    Image.new('RGB',(16,16),(90,30,120)).save(image,format=fmt)
    name='VERIFICACION_UPLOAD_'+uuid.uuid4().hex
    names.append(name)
    (root/'names.json').write_text(json.dumps(names),encoding='utf-8')
    html=curl('/productos')
    token=re.search(r'token="([a-f0-9]+)"',html).group(1)
    fields={'pla_nombre':name,'pla_descripcion':'Prueba temporal de carga','pla_genero':'H','pla_precio':'100','stock_S':'0','stock_M':'0','stock_L':'0','stock_XL':'0','stock_2XL':'0','csrf_test_name':token}
    args=[]
    for k,v in fields.items(): args+=['--form-string',k+'='+v]
    args+=['--form','pla_imagen=@'+str(image)]
    result=json.loads(curl('/productos/guardar',args))
    print(fmt+': '+json.dumps(result,ensure_ascii=False))
    if not result.get('success'): raise RuntimeError('Falló '+fmt)
