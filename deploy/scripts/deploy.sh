#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/codflow}"
BRANCH="${BRANCH:-main}"

cd "$APP_DIR"

echo "==> Pulling latest code from $BRANCH"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Installing Composer dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies"
npm ci

echo "==> Building frontend assets"
npm run build

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Ensuring storage symlink"
php artisan storage:link || true

echo "==> Clearing and rebuilding Laravel caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Applying permissions"
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> Reloading services"
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "==> Deployment complete"
