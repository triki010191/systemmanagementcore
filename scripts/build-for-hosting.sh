#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:${PATH:-}"

echo "==> Build HFNMS untuk hosting (tanpa terminal di server)"
echo "==> PHP: $(php -v | head -n 1)"

if [[ ! -f .env.hosting.example ]]; then
  echo "File .env.hosting.example tidak ditemukan."
  exit 1
fi

echo "==> Install dependency production..."
composer run build:hosting --no-interaction

echo "==> Bersihkan file development..."
rm -f public/hot
rm -f bootstrap/cache/*.php 2>/dev/null || true
rm -rf node_modules .phpunit.cache

ARCHIVE="hfnms-hosting-$(date +%Y%m%d).zip"

echo "==> Membuat arsip upload: ${ARCHIVE}"
zip -r "$ARCHIVE" . \
  -x "*.git*" \
  -x "*node_modules/*" \
  -x "*tests/*" \
  -x "*.env" \
  -x "*public/hot" \
  -x "*.DS_Store" \
  -x "*storage/logs/*" \
  -x "*storage/framework/cache/data/*" \
  -x "*storage/framework/sessions/*" \
  -x "*storage/framework/views/*" \
  -x "*storage/pail/*" \
  -x "*.zip"

echo ""
echo "Selesai: ${ROOT}/${ARCHIVE}"
echo ""
echo "Langkah upload:"
echo "  1. Upload & extract ZIP ke folder subdomain di hosting"
echo "  2. Arahkan document root subdomain ke folder public/"
echo "  3. Buat database MySQL di cPanel"
echo "  4. Salin .env.hosting.example → .env, isi database & APP_URL"
echo "  5. Buka: https://subdomain.domainanda.com/hosting-setup.php?key=...&seed=1"
echo "  6. Hapus hosting-setup.php setelah berhasil"
echo ""
echo "Panduan lengkap: docs/HOSTING.md"
