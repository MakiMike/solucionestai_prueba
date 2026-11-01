from pathlib import Path
import urllib.request, re, textwrap, html
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Preformatted, Table, TableStyle
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from pypdf import PdfReader
root=Path(r'C:\laragon\www\sistema')
out=root/'output/reporte';out.mkdir(parents=True,exist_ok=True)
url='https://sdmntpreastus2.oaiusercontent.com/files/00000000-4e18-81f6-a895-abb395abf7f0/raw?se=2026-11-01T21%3A15%3A44Z&sp=r&sv=2026-02-06&sr=b&scid=46d948c3-acc7-46b8-be2d-668f618e8b22&skoid=c36a4874-d531-4a8d-99fc-7da0d9143d9c&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2026-11-01T15%3A08%3A59Z&ske=2026-11-02T15%3A08%3A59Z&sks=b&skv=2026-02-06&sig=TlOYmuXZI5bRtmfs9Tvkg75R/hwOZpj1zLgpdBtR0tM%3D'
urllib.request.urlretrieve(url,out/'Reporte_TSkull_Shirt_Final.pdf')
styles=getSampleStyleSheet()
styles.add(ParagraphStyle(name='BodyGuide',fontName='Helvetica',fontSize=10,leading=14,spaceAfter=7))
styles.add(ParagraphStyle(name='CodeGuide',fontName='Courier',fontSize=7.2,leading=9.3,leftIndent=6,rightIndent=6,backColor=colors.HexColor('#f1f5f9'),spaceBefore=2,spaceAfter=2))
styles.add(ParagraphStyle(name='CellGuide',fontName='Helvetica',fontSize=8.5,leading=11,spaceAfter=3))
for n in ['Heading1','Heading2']:styles[n].textColor=colors.HexColor('#172d48')
def fmt(s):
 s=html.escape(s)
 s=re.sub(r'`([^`]+)`',r'<font name="Courier">\1</font>',s)
 s=re.sub(r'\*\*([^*]+)\*\*',r'<b>\1</b>',s)
 return s
lines=(root/'docs/GUIA_EXPOSICION.md').read_text(encoding='utf-8').splitlines()
flow=[];i=0
while i<len(lines):
 line=lines[i]
 if line.startswith('```'):
  i+=1
  while i<len(lines) and not lines[i].startswith('```'):
   # Each command may wrap visually; executable source is the accompanying Markdown.
   chunks=textwrap.wrap(lines[i],width=106,replace_whitespace=False,drop_whitespace=False,break_long_words=True,break_on_hyphens=False) or [' ']
   for chunk in chunks:flow.append(Preformatted(chunk,styles['CodeGuide']))
   i+=1
  flow.append(Spacer(1,7))
 elif line.startswith('|'):
  rows=[]
  while i<len(lines) and lines[i].startswith('|'):
   cells=[x.strip() for x in lines[i].strip('|').split('|')]
   if not all(re.fullmatch(r'[: -]+',x) for x in cells):rows.append([Paragraph(fmt(x),styles['CellGuide']) for x in cells])
   i+=1
  tab=Table(rows,colWidths=[115,380],repeatRows=1,hAlign='LEFT')
  tab.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#e4ecf5')),('VALIGN',(0,0),(-1,-1),'TOP'),('LINEBELOW',(0,0),(-1,0),.5,colors.HexColor('#7890a6')),('BOTTOMPADDING',(0,0),(-1,-1),6),('TOPPADDING',(0,0),(-1,-1),5)]))
  flow.extend([tab,Spacer(1,9)]);continue
 elif line.startswith('# '):flow.append(Paragraph(fmt(line[2:]),styles['Title']))
 elif line.startswith('## '):flow.append(Paragraph(fmt(line[3:]),styles['Heading2']))
 elif line.strip():flow.append(Paragraph(fmt(line),styles['BodyGuide']))
 i+=1
def footer(c,d):
 c.setFont('Helvetica',8);c.setFillColor(colors.HexColor('#526478'));c.drawString(50,28,'TSkull-Shirt | Guía de exposición | 01/11/2026');c.drawRightString(545,28,str(d.page))
SimpleDocTemplate(str(out/'Guia_Exposicion_TSkull_Shirt.pdf'),pagesize=(595.28,841.89),rightMargin=50,leftMargin=50,topMargin=44,bottomMargin=45,title='TSkull-Shirt - Guia de exposicion').build(flow,onFirstPage=footer,onLaterPages=footer)
for name in ['Reporte_TSkull_Shirt_Final.pdf','Guia_Exposicion_TSkull_Shirt.pdf']:
 r=PdfReader(out/name)
 (out/(name+'.txt')).write_text('\n\n'.join(p.extract_text() or '' for p in r.pages),encoding='utf-8')
 print(name,len(r.pages),'paginas')
