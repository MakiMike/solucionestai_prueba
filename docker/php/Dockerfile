FROM composer:2.8 AS dependencies
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --ignore-platform-reqs

FROM php:8.3-fpm-bookworm AS backend
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get upgrade -y --no-install-recommends; \
    apt-get install -y --no-install-recommends libpq-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install pdo_pgsql pgsql mbstring gd zip; \
    pecl install mongodb-2.1.0; \
    docker-php-ext-enable mongodb; \
    rm -rf /tmp/pear; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    extDir="$(php -r 'echo ini_get("extension_dir");')"; \
    ldd "$extDir"/*.so \
      | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
      | sort -u \
      | xargs -r dpkg-query --search \
      | cut -d: -f1 | sort -u | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    php -r 'foreach (["pdo_pgsql", "pgsql", "mbstring", "gd", "zip", "mongodb"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing extension: $ext\n"); exit(1); } }'
WORKDIR /var/www/html
COPY --from=dependencies /build/vendor ./vendor
COPY --chown=www-data:www-data . .
RUN php bin/compat-codeigniter.php
COPY docker/php/tienda.ini /usr/local/etc/php/conf.d/tienda.ini
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/zz-tienda.conf
RUN mkdir -p uploads/playeras application/logs application/cache && chown -R www-data:www-data uploads application/logs application/cache
USER www-data
EXPOSE 9000

FROM nginxinc/nginx-unprivileged:1.28-alpine AS frontend
USER root
RUN apk upgrade --no-cache
USER 101
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY assets /var/www/html/assets
COPY DataTables /var/www/html/DataTables
EXPOSE 8080
