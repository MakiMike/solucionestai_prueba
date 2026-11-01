from pathlib import Path
p=Path('docker/php/Dockerfile');s=p.read_text().replace('COPY composer.json ./','COPY composer.json composer.lock ./');p.write_text(s)
p=Path('bin/tienda.php');s=p.read_text();s=s.replace("    } elseif ($action === 'expire') {", """    } elseif ($action === 'grant') {
        $db->exec('GRANT USAGE ON SCHEMA public TO tienda_app; GRANT SELECT,INSERT,UPDATE,DELETE ON ALL TABLES IN SCHEMA public TO tienda_app; GRANT USAGE,SELECT ON ALL SEQUENCES IN SCHEMA public TO tienda_app; REVOKE ALL ON tienda_migrations FROM tienda_app');
        echo 'Permisos de ejecución aplicados.' . PHP_EOL;
    } elseif ($action === 'expire') {""");p.write_text(s)
