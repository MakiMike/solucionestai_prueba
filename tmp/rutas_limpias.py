from pathlib import Path
p=Path('application/config/config.php');s=p.read_text(encoding='utf8').replace("$config['index_page'] = 'index.php';", "$config['index_page'] = '';");p.write_text(s,encoding='utf8')
