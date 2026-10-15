# syntax=docker/dockerfile:1

########## Stage 1: PHP dependencies ##########
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: artisan isn't available in this stage; package discovery
# runs in the runtime stage below.
RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

########## Stage 2: Frontend assets (Vite + Tailwind) ##########
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY . .
# Vendor is copied in so Tailwind's @source directives can scan the
# framework's pagination blade views (see resources/css/app.css).
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

########## Stage 3: Runtime (PHP-FPM) ##########
FROM php:8.3-fpm-alpine

# pdo_pgsql/pgsql for PostgreSQL (libpq stays installed as a runtime lib).
RUN apk add --no-cache postgresql-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

# package:discover is skipped during composer install (no app code there),
# so run it here to write bootstrap/cache/packages.php.
RUN php artisan package:discover --ansi \
    && cp docker/entrypoint.sh /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm -f public/phpinfo-check.php

USER www-data
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
