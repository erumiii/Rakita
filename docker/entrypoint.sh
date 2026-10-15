#!/bin/sh
set -e

cd /var/www/html

# First boot: create .env from the example and generate an app key.
if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force
fi

# Persist the compose-provided DB location into .env (idempotent on restart).
if [ -n "${DB_HOST:-}" ]; then
    sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
fi
if [ -n "${DB_PORT:-}" ]; then
    sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
fi

# Migrate. Compose already gates on the db healthcheck; retry a bit anyway.
attempts=0
until php artisan migrate --force; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 15 ]; then
        echo "Database never became ready; aborting." >&2
        exit 1
    fi
    sleep 2
done

# Seed only while the users table is empty, so restarts stay idempotent.
php docker/seed_once.php

# No config/route/view caching here: a baked config cache would freeze env
# values and break phpunit overrides (SESSION_DRIVER etc.), causing CSRF 419s.

# Hand off to the image CMD: "php-fpm" for the app container,
# "php artisan schedule:work" for the scheduler container.
exec "$@"
