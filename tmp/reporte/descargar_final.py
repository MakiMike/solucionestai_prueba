import urllib.request
from pathlib import Path
from pypdf import PdfReader
url="https://sdmntpreastus2.oaiusercontent.com/files/00000000-7648-81f6-86d6-b67c048f2fd6/raw?se=2026-11-01T21%3A21%3A06Z&sp=r&sv=2026-02-06&sr=b&scid=b126d7eb-08f0-48e6-828d-b66bdeda1411&skoid=c36a4874-d531-4a8d-99fc-7da0d9143d9c&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2026-11-01T15%3A10%3A15Z&ske=2026-11-02T15%3A10%3A15Z&sks=b&skv=2026-02-06&sig=CwRTxmWF20EDveCNnpYIEfOx5deqLBL%2BjavIUfwsv8k%3D"
p=Path('output/reporte/Reporte_TSkull_Shirt_Final.pdf')
urllib.request.urlretrieve(url,p)
r=PdfReader(p)
t='\n'.join(x.extract_text() or '' for x in r.pages)
assert '391.9' in t and '1iaUIon3ougGB8KE0JrsFtXMlf_ln_yZL' in t
print('PDF final:',len(r.pages),'paginas; restauracion y enlace de guia verificados')
