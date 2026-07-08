#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/codflow}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/codflow}"
DATE="$(date +%Y%m%d-%H%M%S)"
DEST="$BACKUP_DIR/$DATE"

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "Missing .env file in $APP_DIR"
    exit 1
fi

mkdir -p "$DEST"

get_env() {
    local key="$1"
    grep -E "^${key}=" .env | tail -n 1 | cut -d '=' -f2- | sed -e 's/^"//' -e 's/"$//'
}

DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"
DB_HOST="$(get_env DB_HOST)"
DB_PORT="$(get_env DB_PORT)"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo "DB_DATABASE or DB_USERNAME is missing in .env"
    exit 1
fi

echo "==> Backing up MySQL database"
MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_DATABASE" | gzip > "$DEST/database.sql.gz"

echo "==> Backing up public storage"
if [ -d "$APP_DIR/storage/app/public" ]; then
    tar -czf "$DEST/storage-public.tar.gz" -C "$APP_DIR/storage/app" public
else
    echo "storage/app/public does not exist, skipping"
fi

echo "==> Writing backup metadata"
cat > "$DEST/README.txt" <<EOF
CODFlow backup
Date: $DATE
App: $APP_DIR
Database: $DB_DATABASE
EOF

echo "==> Keeping last 7 backups"
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d | sort -r | tail -n +8 | xargs -r rm -rf

echo "Backup created: $DEST"
