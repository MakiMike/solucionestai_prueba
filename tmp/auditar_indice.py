import subprocess,re,json
from pathlib import Path
names=subprocess.check_output(['git','ls-files','-z']).decode('utf-8').split('\0')
keys=[];private=[];uri=[]
names=list(filter(None,names))
raw=subprocess.check_output(['git','cat-file','--batch'],input=''.join(':'+n+'\n' for n in names).encode('utf-8'))
pos=0
for name in names:
 p=Path(name)
 if p.suffix.lower() in ('.key','.pem','.p12','.pfx','.dpapi','.dump','.backup') or p.name in ('.env','secrets.env','credentials.env','kubeconfig'):
  private.append(name)
 header_end=raw.index(b'\n',pos)
 size=int(raw[pos:header_end].split()[-1])
 data=raw[header_end+1:header_end+1+size]
 pos=header_end+size+2
 if re.search(rb'^-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----',data,re.M):keys.append(name)
 if re.search(rb'(?:mongodb|postgres(?:ql)?)://[^\s/:]+:[^\s@]+@',data) and not name.endswith('.example'):
  uri.append(name)
result={'archivos_en_indice':len([n for n in names if n]),'archivos_privados':private,'claves_privadas_PEM':keys,'archivos_con_URI_para_revisar':uri}
print(json.dumps(result,ensure_ascii=False))
Path('tmp/auditoria-indice.json').write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf-8')
if keys or private:raise SystemExit(1)
