from pathlib import Path
p=Path('tmp/generar_k8s.py');s=p.read_text(encoding='utf8').replace('import yaml', '''import json
class yaml:
 @staticmethod
 def safe_dump(obj,**kwargs): return json.dumps(obj,indent=2)+'\\n'
 @staticmethod
 def safe_dump_all(objs,**kwargs): return '\\n---\\n'.join(json.dumps(o,indent=2) for o in objs)+'\\n'
''');p.write_text(s,encoding='utf8')
