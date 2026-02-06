#!/usr/bin/env bash
set -euo pipefail

REGISTRY_HOST="${REGISTRY_HOST:?REGISTRY_HOST is required}"
REGISTRY_USERNAME="${REGISTRY_USERNAME:?REGISTRY_USERNAME is required}"
REGISTRY_PASSWORD="${REGISTRY_PASSWORD:?REGISTRY_PASSWORD is required}"

IMAGE_API="${IMAGE_API:?IMAGE_API is required}"
IMAGE_APP="${IMAGE_APP:?IMAGE_APP is required}"

API_ENV_FILE="${API_ENV_FILE:-infra/prod/api/.env}"
APP_ENV_FILE="${APP_ENV_FILE:-infra/prod/app/.env}"

REGISTRY_HOST_CLEAN="${REGISTRY_HOST#http://}"
REGISTRY_HOST_CLEAN="${REGISTRY_HOST_CLEAN#https://}"
REGISTRY_USERNAME_CLEAN="$(printf '%s' "${REGISTRY_USERNAME}" | tr -d '\r\n')"
REGISTRY_PASSWORD_CLEAN="$(printf '%s' "${REGISTRY_PASSWORD}" | tr -d '\r\n')"

echo "Logging into registry: ${REGISTRY_HOST_CLEAN} (user: ${REGISTRY_USERNAME_CLEAN})"
printf '%s' "${REGISTRY_PASSWORD_CLEAN}" | docker login "${REGISTRY_HOST_CLEAN}" -u "${REGISTRY_USERNAME_CLEAN}" --password-stdin

echo "Deploying API stack"
IMAGE_API="${IMAGE_API}" docker compose -f infra/prod/api/compose.yml --env-file "${API_ENV_FILE}" pull
IMAGE_API="${IMAGE_API}" docker compose -f infra/prod/api/compose.yml --env-file "${API_ENV_FILE}" up -d --remove-orphans

echo "Deploying App stack"
IMAGE_APP="${IMAGE_APP}" docker compose -f infra/prod/app/compose.yml --env-file "${APP_ENV_FILE}" pull
IMAGE_APP="${IMAGE_APP}" docker compose -f infra/prod/app/compose.yml --env-file "${APP_ENV_FILE}" up -d --remove-orphans
