#!/usr/bin/env bash

set -Eeuo pipefail

# Run a temporary copy so a deployment can safely update this script itself.
if [[ -z "${SUNTRACK_DEPLOY_COPY:-}" ]]; then
    deploy_copy="$(mktemp /tmp/suntrack-deploy.XXXXXX)"
    cp "${BASH_SOURCE[0]}" "$deploy_copy"
    chmod 700 "$deploy_copy"
    exec env SUNTRACK_DEPLOY_COPY="$deploy_copy" bash "$deploy_copy" "$@"
fi

readonly APP_DIR="/home/sunrise/suntrack-app"
readonly APP_USER="sunrise"
readonly PHP_BIN="/usr/local/apps/php84/bin/php"
readonly PHP_FPM_BIN="/usr/local/apps/php84/sbin/php-fpm"
readonly COMPOSER_BIN="/usr/local/bin/composer"
readonly APACHE_BIN="/usr/local/apps/apache2/bin/httpd"
readonly DOMAIN="suntrack.sunriseadsacademy.com"
readonly DATABASE="sunrise_suntrack"
readonly BACKUP_DIR="/root/suntrack-deploy-backups"
readonly DEPLOY_STAMP="$(date +%Y%m%d-%H%M%S)"
readonly DATABASE_BACKUP="$BACKUP_DIR/${DATABASE}-${DEPLOY_STAMP}.dump"
readonly ENV_BACKUP="$BACKUP_DIR/.env-${DEPLOY_STAMP}"

maintenance_enabled=0

cleanup()
{
    rm -f "$SUNTRACK_DEPLOY_COPY"
}

deployment_failed()
{
    local exit_code=$?
    local line="$1"

    printf '\nDEPLOY_FAILED line=%s exit=%s\n' "$line" "$exit_code"
    printf 'Database backup: %s\n' "$DATABASE_BACKUP"

    if [[ "$maintenance_enabled" -eq 1 ]]; then
        printf 'Aplikasi masih dalam maintenance mode.\n'
    else
        printf 'Aplikasi tidak berada dalam maintenance mode.\n'
    fi

    exit "$exit_code"
}

trap cleanup EXIT
trap 'deployment_failed "$LINENO"' ERR

require_command()
{
    command -v "$1" >/dev/null
}

if [[ "$EUID" -ne 0 ]]; then
    printf 'Jalankan script sebagai root.\n'
    exit 1
fi

for command_name in curl flock git npm pg_dump pg_restore redis-cli sudo systemctl; do
    require_command "$command_name"
done

exec 9>/var/lock/suntrack-deploy.lock
if ! flock -n 9; then
    printf 'DEPLOY_ALREADY_RUNNING\n'
    exit 1
fi

cd "$APP_DIR"

printf '=== PRE-FLIGHT ===\n'

test -x "$PHP_BIN"
test -x "$PHP_FPM_BIN"
test -f "$COMPOSER_BIN"
test -x "$APACHE_BIN"
test -f .env
grep -Eq '^APP_KEY=.+$' .env
grep -Eq '^APP_ENV=production$' .env
grep -Eq '^DB_CONNECTION=pgsql$' .env
grep -Eq "^DB_DATABASE=${DATABASE}$" .env
test "$(sudo -u "$APP_USER" git branch --show-current)" = "main"
test -z "$(sudo -u "$APP_USER" git status --porcelain)"

php_modules="$($PHP_BIN -m)"
for module_name in bcmath gd igbinary intl mbstring pdo_pgsql pgsql redis zip; do
    grep -qx "$module_name" <<< "$php_modules"
done

redis-cli -h 127.0.0.1 -p 6379 ping | grep -qx PONG
"$PHP_FPM_BIN" -t
"$APACHE_BIN" -t
sudo -u "$APP_USER" "$PHP_BIN" artisan migrate:status >/dev/null

printf '=== CHECK UPDATE ===\n'

sudo -u "$APP_USER" git fetch origin main

current_commit="$(sudo -u "$APP_USER" git rev-parse HEAD)"
target_commit="$(sudo -u "$APP_USER" git rev-parse origin/main)"

printf 'Current: %.7s\n' "$current_commit"
printf 'Target : %.7s\n' "$target_commit"

if [[ "$current_commit" = "$target_commit" ]]; then
    printf 'ALREADY_CURRENT\n'
    exit 0
fi

printf '=== BACKUP ===\n'

install -d -m 0700 "$BACKUP_DIR"
cp -a .env "$ENV_BACKUP"
chmod 600 "$ENV_BACKUP"

sudo -u postgres pg_dump --format=custom --dbname="$DATABASE" > "$DATABASE_BACKUP"
test -s "$DATABASE_BACKUP"
pg_restore --list "$DATABASE_BACKUP" >/dev/null
chmod 600 "$DATABASE_BACKUP"

printf 'Database backup: %s\n' "$DATABASE_BACKUP"

for writable_path in vendor node_modules public/build; do
    if [[ -e "$writable_path" ]]; then
        chown -R "$APP_USER:$APP_USER" "$writable_path"
        chmod -R u+rwX,go+rX "$writable_path"
    fi
done

chown -R "$APP_USER:$APP_USER" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

install -d -o "$APP_USER" -g "$APP_USER" /home/sunrise/.composer
install -d -o "$APP_USER" -g "$APP_USER" /home/sunrise/.npm

printf '=== MAINTENANCE MODE ===\n'

sudo -u "$APP_USER" "$PHP_BIN" artisan down --refresh=15
maintenance_enabled=1
systemctl stop suntrack-queue.service 2>/dev/null || true

printf '=== UPDATE SOURCE ===\n'

sudo -u "$APP_USER" git merge --ff-only origin/main
test "$(sudo -u "$APP_USER" git rev-parse HEAD)" = "$target_commit"

printf '=== COMPOSER ===\n'

sudo -H -u "$APP_USER" env COMPOSER_HOME=/home/sunrise/.composer \
    "$PHP_BIN" "$COMPOSER_BIN" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

printf '=== FRONTEND BUILD ===\n'

sudo -H -u "$APP_USER" npm ci
sudo -H -u "$APP_USER" npm run build

printf '=== DATABASE ===\n'

sudo -u "$APP_USER" "$PHP_BIN" artisan optimize:clear
sudo -u "$APP_USER" "$PHP_BIN" artisan migrate --force
sudo -u "$APP_USER" "$PHP_BIN" artisan db:seed --class=ProductionSeeder --force

if [[ ! -e public/storage ]]; then
    sudo -u "$APP_USER" "$PHP_BIN" artisan storage:link
fi

chown -R "$APP_USER:$APP_USER" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
sudo -u "$APP_USER" "$PHP_BIN" artisan optimize

printf '=== SERVICES ===\n'

install -m 0644 deploy/webuzo/suntrack-queue.service /etc/systemd/system/suntrack-queue.service
install -m 0644 deploy/webuzo/suntrack-scheduler.service /etc/systemd/system/suntrack-scheduler.service
install -m 0644 deploy/webuzo/suntrack-scheduler.timer /etc/systemd/system/suntrack-scheduler.timer

systemctl daemon-reload
systemctl enable suntrack-queue.service >/dev/null
systemctl restart suntrack-queue.service
systemctl enable suntrack-scheduler.timer >/dev/null
systemctl restart suntrack-scheduler.timer
systemctl restart php-fpm84.service

printf '=== ACTIVATE ===\n'

sudo -u "$APP_USER" "$PHP_BIN" artisan up
maintenance_enabled=0
systemctl start suntrack-scheduler.service

printf '=== VERIFY ===\n'

systemctl is-active --quiet php-fpm84.service
systemctl is-active --quiet suntrack-queue.service
systemctl is-active --quiet suntrack-scheduler.timer

if sudo -u "$APP_USER" "$PHP_BIN" artisan migrate:status --no-ansi | grep -q 'Pending'; then
    printf 'PENDING_MIGRATION_FOUND\n'
    exit 1
fi

curl --resolve "${DOMAIN}:443:127.0.0.1" \
    --retry 3 \
    --retry-delay 2 \
    --fail \
    --silent \
    --show-error \
    "https://${DOMAIN}/up"
printf '\n'

curl --resolve "${DOMAIN}:443:127.0.0.1" \
    --retry 3 \
    --retry-delay 2 \
    --fail \
    --silent \
    --show-error \
    "https://${DOMAIN}/api/v1/health"
printf '\n'

test -z "$(sudo -u "$APP_USER" git status --porcelain)"

printf 'Commit: %.7s\n' "$target_commit"
printf 'Database backup: %s\n' "$DATABASE_BACKUP"
printf 'DEPLOY_SUCCESS\n'
