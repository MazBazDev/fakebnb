# Fakebnb — Installation locale

Ce guide explique comment lancer l’API Laravel + la SPA Vue en local.

## Prérequis

- PHP 8.2+ (extensions: bcmath, gd, intl, pcntl, pdo_sqlite, sqlite3, zip)
- Composer
- Node.js 18+ / npm
- SQLite
- Redis (pour la queue + Reverb, recommandé)

## 1) API Laravel

```bash
cd apps/api

composer install
cp .env.example .env
php artisan key:generate

# Base SQLite
touch database/database.sqlite

# Migrations + seed
php artisan migrate --seed

# OAuth (PKCE) — récupère l’ID client public
php artisan passport:client --public
```

Garde l’`id` du client OAuth public : il est nécessaire côté SPA (`VITE_OAUTH_CLIENT_ID`).

## 2) SPA Vue

```bash
cd apps/app

npm install
cp .env.example .env
```

Dans `apps/app/.env`, renseigne au minimum :

```
VITE_API_URL=http://localhost:8989/api/v1
VITE_OAUTH_CLIENT_ID=<ID_CLIENT_PUBLIC>
VITE_OAUTH_AUTHORIZE_URL=http://localhost:8989/oauth/authorize
VITE_OAUTH_TOKEN_URL=http://localhost:8989/oauth/token
VITE_OAUTH_REDIRECT_URI=http://localhost:5173/auth/callback
VITE_REVERB_APP_KEY=local
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
```

## 3) Lancer les services (local)

Dans des terminaux séparés :

```bash
# API
cd apps/api
php artisan serve --host=0.0.0.0 --port=8989
```

```bash
# Queue
cd apps/api
php artisan queue:listen --tries=1 --timeout=0
```

```bash
# WebSockets (Reverb)
cd apps/api
php artisan reverb:start --host=0.0.0.0 --port=8080
```

```bash
# Scheduler (pour marquer les réservations terminées)
cd apps/api
php artisan schedule:work
```

```bash
# SPA
cd apps/app
npm run dev -- --host
```

## 4) Accès

- SPA : `http://localhost:5173`
- API : `http://localhost:8989`
- OAuth authorize : `http://localhost:8989/oauth/authorize`

## Comptes de démo (seeders)

Les seeders créent trois comptes :

- Client : `tc@t.fr` / `password`
- Host : `th@t.fr` / `password`
- Co-host : `tch@t.fr` / `password`

Sur la page Laravel `/login`, tu peux te connecter rapidement avec ces comptes via les boutons de démo.

## Dépannage rapide

- **OAuth client manquant** : relance `php artisan passport:client --public`
- **Migrations** : `php artisan migrate:fresh --seed`
- **Reverb** : vérifie `REVERB_*` côté API et `VITE_REVERB_*` côté SPA
