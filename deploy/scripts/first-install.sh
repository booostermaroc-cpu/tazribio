#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/codflow}"

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "==> Creating .env from .env.production.example"
    cp .env.production.example .env
    echo "==> Edit .env before continuing:"
    echo "    nano $APP_DIR/.env"
    exit 1
fi

echo "==> Installing Composer dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies"
npm ci

echo "==> Building frontend assets"
npm run build

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "==> Generating application key"
    php artisan key:generate --force
fi

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Creating storage link"
php artisan storage:link || true

echo "==> Clearing and rebuilding Laravel caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Applying permissions"
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> First install complete"
echo "Next steps:"
echo "1. Configure Nginx using deploy/nginx/codflow.conf"
echo "2. Configure SSL with Certbot"
echo "3. Configure Supervisor using deploy/supervisor/codflow-worker.conf"
echo "4. Add Laravel scheduler cron"
echo "5. Run production checks"
