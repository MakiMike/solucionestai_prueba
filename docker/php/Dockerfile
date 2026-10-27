FROM composer:2.8 AS dependencies
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --ignore-platform-reqs

FROM php:8.3-fpm-bookworm AS backend
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql mbstring gd zip \
    && pecl install mongodb-2.1.0 && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*
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
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY assets /var/www/html/assets
COPY DataTables /var/www/html/DataTables
EXPOSE 8080
