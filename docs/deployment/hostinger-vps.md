# CODFlow production deployment on Hostinger KVM VPS

This guide prepares a fresh Hostinger KVM VPS for CODFlow / Tazri Bio on:

- Ubuntu 24.04 LTS
- Nginx
- PHP 8.3 FPM
- MySQL 8
- Composer
- Node.js 22 LTS
- Redis
- Supervisor
- Certbot SSL
- Laravel queue worker
- Laravel scheduler

> Replace `your-domain.com`, `codflow`, `codflow_user`, passwords, and repository URL with your real values.

## 1. Connect to the VPS

```bash
ssh root@YOUR_SERVER_IP
```

Create an optional deploy user:

```bash
adduser deploy
usermod -aG sudo deploy
su - deploy
```

## 2. Update the server

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y software-properties-common curl unzip git ca-certificates gnupg lsb-release apt-transport-https
```

## 3. Configure firewall (UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

## 4. Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

## 5. Install PHP 8.3 and required extensions

Ubuntu 24.04 ships PHP 8.3 packages.

```bash
sudo apt install -y \
  php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis \
  php8.3-soap php8.3-readline

php -v
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
```

Recommended PHP settings:

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Set or verify:

```ini
upload_max_filesize = 2G
post_max_size = 2G
memory_limit = 512M
max_execution_time = 1800
```

Restart PHP:

```bash
sudo systemctl restart php8.3-fpm
```

## 6. Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
sudo mysql_secure_installation
```

Create database and user:

```bash
sudo mysql
```

```sql
CREATE DATABASE codflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'codflow_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON codflow.* TO 'codflow_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Use a strong password and store it securely.

## 7. Install Composer

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
rm composer-setup.php
```

## 8. Install Node.js 22 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 9. Install Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping
```

Expected response:

```text
PONG
```

## 10. Install Supervisor

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

## 11. Clone CODFlow

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:www-data /var/www
cd /var/www
git clone https://github.com/booostermaroc-cpu/tazribio.git codflow
cd /var/www/codflow
```

If you deploy from a private repository, configure SSH keys or GitHub token access first.

## 12. Configure production environment

```bash
cp .env.production.example .env
nano .env
```

Minimum values to configure:

```env
APP_NAME="Tazri Bio"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=codflow
DB_USERNAME=codflow_user
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
FILESYSTEM_DISK=public

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Generate the app key:

```bash
php artisan key:generate --force
```

Do not commit `.env`.

## 13. First install

You can run:

```bash
chmod +x deploy/scripts/first-install.sh deploy/scripts/deploy.sh deploy/scripts/backup.sh
./deploy/scripts/first-install.sh
```

Or run the commands manually:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 14. Configure permissions

```bash
sudo chown -R www-data:www-data /var/www/codflow
sudo chmod -R 775 /var/www/codflow/storage /var/www/codflow/bootstrap/cache
```

If your deploy user needs write access:

```bash
sudo usermod -aG www-data $USER
sudo find /var/www/codflow -type d -exec chmod 775 {} \;
sudo find /var/www/codflow -type f -exec chmod 664 {} \;
sudo chmod -R 775 /var/www/codflow/storage /var/www/codflow/bootstrap/cache
```

## 15. Configure Nginx

Copy the example config:

```bash
sudo cp deploy/nginx/codflow.conf /etc/nginx/sites-available/codflow
sudo nano /etc/nginx/sites-available/codflow
```

Update:

- `server_name your-domain.com www.your-domain.com;`
- SSL certificate paths after Certbot if needed

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/codflow /etc/nginx/sites-enabled/codflow
sudo nginx -t
sudo systemctl reload nginx
```

## 16. Setup SSL with Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
sudo certbot renew --dry-run
```

Certbot will update the Nginx SSL directives automatically.

## 17. Configure Laravel scheduler

Open crontab:

```bash
sudo crontab -e
```

Add:

```cron
* * * * * cd /var/www/codflow && php artisan schedule:run >> /dev/null 2>&1
```

## 18. Configure Laravel queue worker with Supervisor

Copy config:

```bash
sudo cp deploy/supervisor/codflow-worker.conf /etc/supervisor/conf.d/codflow-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start codflow-worker:*
sudo supervisorctl status
```

Queue command:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --timeout=120
```

## 19. Production deploy workflow

After the first install, deploy updates with:

```bash
cd /var/www/codflow
./deploy/scripts/deploy.sh
```

The script runs:

- `git pull`
- `composer install --no-dev --optimize-autoloader`
- `npm ci`
- `npm run build`
- `php artisan migrate --force`
- `php artisan storage:link`
- cache optimization
- queue restart
- PHP-FPM reload
- Nginx reload

## 20. Production checks

Run:

```bash
php artisan codflow:verify-mvp
php artisan codflow:verify-workflow
```

If a dry-run Ameex sync command exists in your deployed version:

```bash
php artisan codflow:ameex:sync --dry-run
```

In the current codebase, the available command is:

```bash
php artisan codflow:ameex:sync
```

It does not currently expose a `--dry-run` option, so run it only after confirming production Ameex credentials and expected behavior.

## 21. Backups

Configure backup environment variables in `.env` or pass them before running:

```env
BACKUP_DIR=/var/backups/codflow
```

Run:

```bash
./deploy/scripts/backup.sh
```

Add daily cron:

```bash
sudo crontab -e
```

Example daily backup at 02:30:

```cron
30 2 * * * cd /var/www/codflow && /var/www/codflow/deploy/scripts/backup.sh >> /var/log/codflow-backup.log 2>&1
```

The backup script keeps the last 7 backups.

## 22. Security recommendations

- Keep `APP_ENV=production`.
- Keep `APP_DEBUG=false`.
- Never commit `.env`.
- Use a strong MySQL password.
- Protect Ameex API keys and any carrier API keys.
- Rotate Ameex API key if it was exposed.
- Enable UFW and allow only OpenSSH, HTTP, and HTTPS.
- Keep Ubuntu packages updated.
- Run daily database and storage backups.
- Restrict SSH root login if possible.
- Use SSH keys instead of password auth.
- Review file permissions after every manual upload.

## 23. Troubleshooting

Check PHP-FPM:

```bash
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

Check Nginx:

```bash
sudo nginx -t
sudo systemctl status nginx
sudo journalctl -u nginx -n 100 --no-pager
```

Check Laravel logs:

```bash
tail -f /var/www/codflow/storage/logs/laravel.log
```

Check queue:

```bash
sudo supervisorctl status
sudo supervisorctl restart codflow-worker:*
```

Fix permissions:

```bash
sudo chown -R www-data:www-data /var/www/codflow
sudo chmod -R 775 /var/www/codflow/storage /var/www/codflow/bootstrap/cache
```

## Deployment checklist

- [ ] DNS points to the VPS IP.
- [ ] UFW allows OpenSSH, HTTP, HTTPS.
- [ ] PHP 8.3 extensions installed.
- [ ] MySQL database and user created.
- [ ] `.env` configured with production values.
- [ ] `APP_KEY` generated.
- [ ] Composer dependencies installed.
- [ ] Node dependencies installed.
- [ ] Assets built.
- [ ] Migrations executed.
- [ ] Storage link created.
- [ ] Permissions applied.
- [ ] Nginx site enabled.
- [ ] SSL issued with Certbot.
- [ ] Scheduler cron installed.
- [ ] Supervisor queue worker running.
- [ ] Backups configured.
- [ ] `php artisan codflow:verify-mvp` passes.
- [ ] `php artisan codflow:verify-workflow` passes.
