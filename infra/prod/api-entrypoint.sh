#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p database storage bootstrap/cache
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

if [ -z "${APP_KEY:-}" ]; then
  if [ -f .env ]; then
    php artisan key:generate --force
    export APP_KEY="$(grep '^APP_KEY=' .env | cut -d= -f2-)"
  else
    echo "APP_KEY is not set. Set APP_KEY in env or provide a .env file."
    exit 1
  fi
fi

if [ ! -e public/storage ]; then
  php artisan storage:link || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  seed_needed="0"
  seed_check=$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); try { echo (int) Illuminate\\Support\\Facades\\DB::table('migrations')->count(); } catch (Throwable \$e) { echo -1; }" || echo -1)
  if [ "${seed_check}" = "-1" ] || [ "${seed_check}" = "0" ]; then
    seed_needed="1"
  fi

  php artisan migrate --force

  if [ "${RUN_SEED_ON_FIRST_BOOT:-true}" = "true" ] && [ "${seed_needed}" = "1" ]; then
    php artisan db:seed --force
  fi
fi

if [ "${RUN_PASSPORT_INSTALL:-true}" = "true" ]; then
  php artisan passport:keys --force || true
  php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); \$client = Laravel\\Passport\\Client::query()->where('password_client', true)->where('revoked', false)->first(); if (! \$client) { \$repo = \$app->make(Laravel\\Passport\\ClientRepository::class); \$repo->createPasswordGrantClient(null, 'Password Grant Client', 'http://localhost'); }" || true
fi

chown -R www-data:www-data storage bootstrap/cache database || true

exec "$@"
