#!/usr/bin/env sh
set -e

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
INFRA_DIR="$ROOT_DIR/infra"
ENV_FILE="$INFRA_DIR/.env"
APP_ENV_FILE="$ROOT_DIR/apps/app/.env"

if [ ! -f "$ENV_FILE" ]; then
  if command -v openssl >/dev/null 2>&1; then
    APP_KEY="base64:$(openssl rand -base64 32)"
  else
    APP_KEY="base64:$(docker run --rm alpine:3.20 sh -c \"apk add --no-cache openssl >/dev/null && openssl rand -base64 32\")"
  fi

  cat > "$ENV_FILE" <<EOF
APP_KEY=$APP_KEY
APP_URL=http://localhost:8989
VITE_API_URL=http://localhost:8989/api/v1
VITE_REVERB_APP_KEY=local
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
VITE_REVERB_AUTH_ENDPOINT=http://localhost:8989/broadcasting/auth
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
EOF
  echo "Generated infra/.env"
fi

if [ ! -f "$APP_ENV_FILE" ]; then
  cat > "$APP_ENV_FILE" <<EOF
VITE_API_URL=http://localhost:8989/api/v1
VITE_REVERB_APP_KEY=local
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
VITE_REVERB_AUTH_ENDPOINT=http://localhost:8989/broadcasting/auth
EOF
  echo "Generated apps/app/.env"
fi
