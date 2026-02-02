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

if [ "${RUN_PASSPORT_INSTALL:-true}" = "true" ]; then
  php artisan passport:keys --force || true
  php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); \$client = Laravel\\Passport\\Client::query()->where('password_client', true)->where('revoked', false)->first(); if (! \$client) { \$repo = \$app->make(Laravel\\Passport\\ClientRepository::class); \$repo->createPasswordGrantClient(null, 'Password Grant Client', 'http://localhost'); }" || true
fi

chown -R www-data:www-data storage bootstrap/cache database || true

exec "$@"
