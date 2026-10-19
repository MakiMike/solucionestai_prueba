import json
from pathlib import Path
from docx import Document
p='output/documentos/Ficha_Proyecto_Tienda_Ropa_Alternativa.docx'
d=Document(p)
texts=json.loads(Path('tmp/docs/textos_doc_actual.json').read_text(encoding='utf8'))
pars=[x for x in d.paragraphs if x.text]
assert len(pars)==len(texts)
m=dict(json.loads(Path('tmp/docs/cambios_pago.json').read_text(encoding='utf8')))
for par,t in zip(pars,texts): par.text=m.get(t,t)
d.save(p)
