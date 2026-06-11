#!/usr/bin/env bash
# Jalankan di server hosting setelah: git pull origin main
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP="${PHP_BIN:-php}"
if [[ -x /opt/cpanel/ea-php83/root/usr/bin/php ]]; then
    PHP=/opt/cpanel/ea-php83/root/usr/bin/php
fi

COMPOSER=""
for candidate in composer php composer.phar "$ROOT/composer.phar" "$HOME/bin/composer" /usr/local/bin/composer; do
    if command -v "$candidate" >/dev/null 2>&1; then
        COMPOSER="$candidate"
        break
    fi
done

echo "==> Deploy HFNMS (server)"
echo "==> PHP: $($PHP -v | sed -n '1p')"

if [[ ! -f .env ]]; then
    echo "ERROR: file .env tidak ditemukan. Buat dari .env.hosting.example"
    exit 1
fi

if [[ -f vendor/autoload.php ]]; then
    echo "==> vendor/ sudah ada — OK"
elif [[ -n "$COMPOSER" ]]; then
    echo "==> Composer install (production)..."
    if [[ "$COMPOSER" == "composer" ]] || [[ "$COMPOSER" == /usr/local/bin/composer ]] || [[ "$COMPOSER" == "$HOME/bin/composer" ]]; then
        $COMPOSER install --no-dev --optimize-autoloader --no-interaction
    elif [[ "$COMPOSER" == "php" ]] && [[ -f composer.phar ]]; then
        $PHP composer.phar install --no-dev --optimize-autoloader --no-interaction
    else
        $PHP "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
    fi
else
    echo "==> Composer tidak ditemukan. Install vendor/ di lokal lalu upload, atau:"
  echo "    curl -sS https://getcomposer.org/installer | $PHP"
  echo "    $PHP composer.phar install --no-dev --optimize-autoloader"
    exit 1
fi

if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
    echo "==> Build frontend..."
    npm ci --ignore-scripts
    npm run build
else
    echo "==> Lewati npm build (build assets di lokal, commit public/build)"
fi

echo "==> Migrasi database..."
$PHP artisan migrate --force

echo "==> Storage link..."
$PHP artisan storage:link 2>/dev/null || true

echo "==> Bersihkan cache..."
rm -f bootstrap/cache/*.php 2>/dev/null || true
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan cache:clear

echo "==> Permission storage..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "Deploy selesai: $(date)"
echo "Cek: $PHP artisan about"
