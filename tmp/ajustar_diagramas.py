from pathlib import Path
p=Path('tmp/crear_diagramas.py');s=p.read_text(encoding='utf8')
s=s.replace(" def save(self):\n  self.im.save", " def save(self):\n  self.text(55,1065,'Leyenda: morado = aplicación · verde = datos · azul = infraestructura · amarillo = pendiente o excepción',18)\n  self.im.save")
s=s.replace("D.note(1030,'El historial admite", "D.arrow([(1300,270),(1740,270),(1740,860),(1690,860)],'Consultar historial · 27017',labelpos=(1510,320))\nD.note(1030,'El historial admite")
s=s.replace("(3,2,995,'9. Marcar enviado solo tras escritura confirmada')", "(4,3,945,'8b. Escritura confirmada'),(3,2,1010,'9. Marcar enviado en PostgreSQL')")
s=s.replace("D.arrow([(1160,320),(1160,370)])", "D.arrow([(1160,320),(1160,345),(850,345),(850,370)])")
s=s.replace("'1 Pod Nginx','app'", "'Deployment · 1 Pod Nginx','app'").replace("'1 Pod PHP-FPM inicial','app'", "'Deployment · 1 Pod inicial','app'")
p.write_text(s,encoding='utf8')
