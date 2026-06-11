# Git & Deploy Workflow — HFNMS

Repository: **https://github.com/triki010191/systemmanagementcore**

## Ringkasan alur

```
[Lokal] edit code → git push → [GitHub] → git pull → [Hosting] deploy-server.sh
```

File `.env` **tidak** masuk Git — simpan backup terpisah di server.

---

## 1. Komputer lokal (development)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/systemmanagemencore

# Cek remote
git remote -v

# Setelah selesai coding
git add .
git commit -m "Deskripsi perubahan"
git push origin main
```

### Build assets sebelum push (jika ubah CSS/JS)

```bash
npm run build
git add public/build
git commit -m "Build production assets"
git push origin main
```

> Folder `vendor/` dan `node_modules/` tidak di-commit (ada di `.gitignore`).

---

## 2. Setup Git pertama kali di hosting (sekali saja)

SSH / Terminal cPanel:

```bash
cd ~/smc.laksanatech.com

# Backup .env
cp .env ~/.env.smc.backup

# Inisialisasi Git & hubungkan GitHub
git init
git remote add origin https://github.com/triki010191/systemmanagementcore.git
git fetch origin
git checkout -B main origin/main

# Kembalikan .env production
cp ~/.env.smc.backup .env

# Deploy pertama
chmod +x scripts/deploy-server.sh
./scripts/deploy-server.sh
```

### Autentikasi GitHub di server

GitHub tidak menerima password biasa. Pilih salah satu:

**Opsi A — Personal Access Token (PAT):**

1. GitHub → Settings → Developer settings → Personal access tokens
2. Buat token dengan scope `repo`
3. Saat `git pull`, username = GitHub username, password = token

**Opsi B — Simpan credential:**

```bash
git config --global credential.helper store
git pull origin main
# masukkan username + PAT sekali
```

---

## 3. Update rutin di hosting

Setiap ada perubahan dari lokal:

```bash
cd ~/smc.laksanatech.com
git pull origin main
./scripts/deploy-server.sh
```

Atau manual:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan migrate --force
/opt/cpanel/ea-php83/root/usr/bin/php artisan config:clear
```

---

## 4. File yang tidak di-commit

| File | Alasan |
|------|--------|
| `.env` | Rahasia (DB, API key) |
| `vendor/` | Di-install via `composer install` |
| `node_modules/` | Di-install via `npm ci` |
| `public/hot` | Dev Vite saja |
| `*.zip` | Paket deploy manual |

Template: `.env.example`, `.env.hosting.example`

---

## 5. Cron scheduler (server)

```bash
crontab -e
```

```cron
* * * * * cd /home/laksanat/smc.laksanatech.com && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `git pull` konflik | Backup `.env`, `git stash`, pull, restore `.env` |
| `composer: command not found` | `php /path/composer.phar install` atau install Composer di server |
| `PDO not found` di CLI | Pakai `/opt/cpanel/ea-php83/root/usr/bin/php` |
| CSS hilang setelah pull | Jalankan `npm run build` di lokal, commit `public/build`, push, pull lagi |

---

## 7. Hapus file setup sementara (production)

Setelah Git deploy aktif, hapus dari server:

```bash
rm -f public/tes.php public/fix-cache.php public/run-migrate.php
rm -f public/hosting-setup.php public/diagnose.php public/check-php.php public/show-log.php
```

File `public/cron.php` boleh dihapus jika sudah pakai `artisan schedule:run` di crontab.
