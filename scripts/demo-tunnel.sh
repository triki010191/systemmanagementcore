#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:${PATH:-}"

TUNNEL_URL="${1:-}"

if [[ -z "$TUNNEL_URL" ]]; then
  echo "Usage: ./scripts/demo-tunnel.sh https://your-subdomain.trycloudflare.com"
  exit 1
fi

echo ">> Building frontend assets..."
npm run build

echo ">> Disabling Vite dev mode..."
rm -f public/hot

if grep -q '^APP_URL=' .env; then
  sed -i '' "s|^APP_URL=.*|APP_URL=${TUNNEL_URL}|" .env
else
  echo "APP_URL=${TUNNEL_URL}" >> .env
fi

php artisan config:clear --no-interaction

echo ""
echo "Demo siap. Jalankan di 2 terminal terpisah:"
echo "  1) php artisan serve --host=127.0.0.1 --port=8013"
echo "  2) cloudflared tunnel --url http://127.0.0.1:8013"
echo ""
echo "APP_URL sudah diset ke: ${TUNNEL_URL}"
