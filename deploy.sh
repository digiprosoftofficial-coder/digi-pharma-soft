#!/usr/bin/env bash
# Shared hosting deploy: run from the project root over SSH.
#   bash deploy.sh
# Override binaries if the host uses versioned PHP, e.g.:
#   PHP=/usr/local/bin/ea-php83 COMPOSER="ea-php83 /opt/cpanel/composer/bin/composer" bash deploy.sh
set -euo pipefail

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
BRANCH="${BRANCH:-main}"

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    echo "ERROR: .env not found. Copy .env.production.example to .env and fill it in first." >&2
    exit 1
fi

echo "==> Maintenance mode on"
$PHP artisan down --retry=30 || true
trap '$PHP artisan up' EXIT

echo "==> Pulling $BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Installing PHP dependencies"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if ! grep -qE '^APP_KEY=.+' .env; then
    echo "==> Generating APP_KEY"
    $PHP artisan key:generate --force
fi

echo "==> Running migrations"
$PHP artisan migrate --force

if [ ! -e public/storage ]; then
    echo "==> Linking storage"
    $PHP artisan storage:link || echo "WARN: storage:link failed (symlinks may be disabled on this host)"
fi

echo "==> Rebuilding caches"
$PHP artisan optimize:clear
$PHP artisan optimize

$PHP artisan queue:restart || true

echo "==> Deploy finished"
