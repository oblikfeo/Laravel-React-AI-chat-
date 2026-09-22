#!/usr/bin/env bash
#
# Выкатка на боевой сервер.
#
# Запускается на сервере: bash /var/www/uncensia/scripts/deploy.sh
# Либо с машины разработчика одной командой:
#   ssh -i credentials/ssh/uncensia_deploy root@154.41.135.175 \
#       'bash /var/www/uncensia/scripts/deploy.sh'
#
# Скрипт безопасно повторять: он не ломает ничего при повторном запуске.

set -euo pipefail

APP_DIR=/var/www/uncensia
SRV_DIR="${APP_DIR}/server"

echo "==> Забираем изменения"
cd "${APP_DIR}"
git fetch --quiet origin
git reset --hard origin/main --quiet
git log --oneline -1

cd "${SRV_DIR}"
export COMPOSER_ALLOW_SUPERUSER=1

echo "==> Зависимости PHP"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "==> Зависимости и сборка фронтенда"
npm ci --silent --no-audit --no-fund
npm run build

echo "==> Миграции"
php artisan migrate --force

echo "==> Права доступа"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Пересобираем кеши"
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

echo "==> Перезапускаем PHP"
systemctl reload php8.3-fpm

echo "==> Проверка"

# Проверяем по тому же адресу, что и пользователь. Обращение по http
# вернуло бы 301: оно переадресуется на защищённое соединение.
SITE_URL="${SITE_URL:-https://154-41-135-175.sslip.io}"
code=$(curl -sL -o /dev/null -w '%{http_code}' "${SITE_URL}/")

if [ "$code" = "200" ]; then
    echo "Готово. Сайт отвечает: ${SITE_URL}"
else
    echo "ВНИМАНИЕ: сайт вернул ${code}, смотрите логи:"
    echo "  tail -30 ${SRV_DIR}/storage/logs/laravel.log"
    echo "  tail -30 /var/log/nginx/error.log"
    exit 1
fi
