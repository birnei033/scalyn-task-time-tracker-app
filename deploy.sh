#!/usr/bin/env bash

set -euo pipefail

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
PHP_BINARY="${PHP_BINARY:-php}"
COMPOSER_BINARY="${COMPOSER_BINARY:-composer}"
NPM_BINARY="${NPM_BINARY:-npm}"

if [[ ! -d .git ]]; then
    echo "This script must be run from the root of the deployed Git repository." >&2
    exit 1
fi

if ! command -v "$PHP_BINARY" >/dev/null 2>&1; then
    echo "PHP binary not found: $PHP_BINARY" >&2
    exit 1
fi

if ! command -v "$COMPOSER_BINARY" >/dev/null 2>&1; then
    echo "Composer binary not found: $COMPOSER_BINARY" >&2
    exit 1
fi

if ! command -v "$NPM_BINARY" >/dev/null 2>&1; then
    echo "npm binary not found: $NPM_BINARY" >&2
    echo "Frontend assets must be built on the cPanel host for this deploy flow." >&2
    exit 1
fi

echo "==> Syncing code from origin/${DEPLOY_BRANCH}"
git fetch origin "${DEPLOY_BRANCH}"
git reset --hard "origin/${DEPLOY_BRANCH}"

echo "==> Installing PHP dependencies"
"$COMPOSER_BINARY" install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Clearing cached Laravel artifacts"
"$PHP_BINARY" artisan optimize:clear

echo "==> Running database migrations"
"$PHP_BINARY" artisan migrate --force --no-interaction

echo "==> Installing frontend dependencies"
"$NPM_BINARY" ci --no-audit --no-fund

echo "==> Building frontend assets"
"$NPM_BINARY" run build

echo "==> Rebuilding Laravel caches"
"$PHP_BINARY" artisan config:cache
"$PHP_BINARY" artisan event:cache
"$PHP_BINARY" artisan view:cache

echo "==> Restarting queue workers if present"
"$PHP_BINARY" artisan queue:restart || true

echo "Deployment complete."
