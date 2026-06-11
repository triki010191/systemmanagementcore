#!/usr/bin/env bash
# Jalankan di server hosting setelah: git pull origin main
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP="${PHP_BIN:-php}"
if [[ -x /opt/cpanel/ea-php83/root/usr/bin/php ]]; then
    PHP=/opt/cpanel/ea-php83/root/usr/bin/php
fi

echo "==> Deploy HFNMS (server)"
echo "==> PHP: $($PHP -v | sed -n '1p')"

if [[ ! -f .env ]]; then
    echo "ERROR: file .env tidak ditemukan. Buat dari .env.hosting.example"
    exit 1
fi

echo "==> Composer install (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
    echo "==> Build frontend..."
    npm ci --ignore-scripts
    npm run build
else
    echo "==> Lewati npm build (npm tidak tersedia — build di lokal lalu commit public/build)"
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
echo "Cek: php artisan about"
