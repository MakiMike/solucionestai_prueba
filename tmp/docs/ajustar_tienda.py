import json
from docx import Document
from pathlib import Path
p=Path('output/documentos/Ficha_Proyecto_Uniformes_por_Grupo.docx')
d=Document(p)
m=dict(json.loads(Path('tmp/docs/cambios_tienda.json').read_text(encoding='utf-8')))
for par in d.paragraphs:
 if par.text in m: par.text=m[par.text]
d.core_properties.title='Ficha inicial de tienda de ropa alternativa'
d.save('output/documentos/Ficha_Proyecto_Tienda_Ropa_Alternativa.docx')
