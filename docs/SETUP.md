# HFNMS - Setup & Development Guide

## Ringkasan Instalasi

| Komponen | Versi |
|----------|-------|
| Laravel | 13.14 |
| PHP | 8.4+ (Homebrew) |
| Node.js | 26+ (Homebrew) |
| MySQL | 8 (XAMPP) |
| Laravel AI SDK | 0.7.x |
| Laravel Boost | 2.4.x |
| Laravel Reverb | 1.10.x |
| Laravel Breeze | 2.4.x (Blade + Alpine + Tailwind) |

## Prasyarat

### 1. PHP 8.4 (wajib — Laravel 13 minimum PHP 8.3)

XAMPP bawaan memakai PHP 8.2. Gunakan PHP Homebrew untuk development:

```bash
brew install php@8.4
export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"
php -v   # harus >= 8.4
```

### 2. Node.js & npm

```bash
brew install node
node -v && npm -v
```

### 3. MySQL (XAMPP)

Pastikan MySQL XAMPP berjalan, lalu buat database:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e \
  "CREATE DATABASE IF NOT EXISTS hfnms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## Konfigurasi Environment

Salin dan sesuaikan `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Variabel penting sudah dikonfigurasi di `.env`:

- `APP_NAME=HFNMS`
- `DB_DATABASE=hfnms` (MySQL)
- `BROADCAST_CONNECTION=reverb`
- `REVERB_*` untuk WebSocket
- `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, dll. untuk AI SDK

## Menjalankan Aplikasi

### Opsi A: Laravel Dev Server (disarankan)

```bash
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:$PATH"
composer run dev
```

Menjalankan sekaligus: HTTP server, queue, logs (Pail), Reverb, dan Vite.

Akses: http://localhost:8000

### Opsi B: XAMPP Apache

Arahkan document root ke folder `public/`:

```
http://localhost/systemmanagemencore/public
```

Pastikan Apache memakai PHP 8.4+ (bukan PHP 8.2 bawaan XAMPP).

## Laravel AI SDK

Sudah terpasang dan dikonfigurasi:

```bash
composer require laravel/ai          # sudah terinstall
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"  # sudah dipublish
php artisan migrate                  # sudah dijalankan
```

### Membuat Agent

```bash
php artisan make:agent NetworkAnalyst
php artisan make:agent NetworkAnalyst --structured
```

### Provider AI

Isi API key di `.env` sesuai provider di `config/ai.php`:

- OpenAI, Anthropic, Gemini, Groq, Cohere, DeepSeek, Mistral, Ollama, OpenRouter, xAI

## Laravel Boost (AI Development)

Boost sudah terinstall untuk Cursor:

- Guidelines AI di `.ai/guidelines/`
- MCP server: `php artisan boost:mcp` (terkonfigurasi di `.cursor/mcp.json`)

```bash
composer require laravel/boost --dev   # sudah terinstall
php artisan boost:install --guidelines --mcp
```

## Laravel Reverb (Realtime)

```bash
# Jalankan server WebSocket terpisah
php artisan reverb:start

# Atau gunakan composer run dev (sudah include Reverb)
```

## Frontend (Blade + Alpine + Tailwind)

Breeze Blade stack sudah terpasang:

```bash
npm install
npm run dev    # development
npm run build  # production
```

## Perintah Berguna

```bash
php artisan migrate          # jalankan migrasi
php artisan make:agent Name  # buat AI agent
php artisan test             # jalankan test
php artisan pint             # format kode PHP
composer run setup           # setup ulang dari awal
```

## Struktur Proyek

```
app/           # Controllers, Models, AI Agents
config/        # ai.php, reverb.php, broadcasting.php
database/      # Migrations (termasuk agent_conversations)
docs/          # PROJECT_OVERVIEW.md, SETUP.md
resources/     # Blade views, CSS, JS
routes/        # web.php, channels.php
public/        # Document root
```

## Langkah Berikutnya

1. Isi API key AI di `.env`
2. Buat modul pertama (auth sudah siap via Breeze)
3. Desain skema Network Graph (nodes + links)
4. Integrasi Leaflet untuk GIS map
