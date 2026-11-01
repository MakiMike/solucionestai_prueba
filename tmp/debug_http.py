from pathlib import Path
p=Path('tests/http.php');s=p.read_text();s=s.replace("expectHttp($code===200,'carrito autenticado disponible');", "if($code!==200)file_put_contents(__DIR__.'/../tmp/http-failure.html',$body);expectHttp($code===200,'carrito autenticado disponible HTTP '.$code);");s=s.replace("CURLOPT_COOKIEFILE=>$jar,CURLOPT_TIMEOUT", "CURLOPT_COOKIEFILE=>$jar,CURLOPT_TIMEOUT");p.write_text(s)
p=Path('application/config/config.php');s=p.read_text(encoding='utf8').replace("$config['log_threshold'] = 0;", "$config['log_threshold'] = 1;");p.write_text(s,encoding='utf8')
