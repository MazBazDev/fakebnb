#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p database storage bootstrap/cache
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

if [ ! -e public/storage ]; then
  php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --seed
fi

chown -R www-data:www-data storage bootstrap/cache database || true

exec "$@"
