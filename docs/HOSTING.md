# HFNMS — Panduan Hosting Subdomain (Tanpa Terminal/SSH)

Panduan ini untuk men-deploy **Hinet Fiber Network Management System** ke shared hosting (cPanel, Plesk, dll.) pada **subdomain**, tanpa akses terminal.

## Persyaratan Hosting

| Item | Minimum |
|------|---------|
| PHP | **8.3** (minimal) — kompatibel shared hosting cPanel |
| Ekstensi | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Apache | `mod_rewrite` aktif |
| SSL | Disarankan (HTTPS) |

> **Penting:** PHP bawaan XAMPP 8.2 **tidak** didukung. Pastikan hosting menyediakan PHP 8.3+.

---

## Ringkasan Alur

1. **Di komputer lokal** — build paket upload (sudah termasuk `vendor/` dan `public/build/`)
2. **Di cPanel** — buat subdomain, database MySQL, upload file
3. **Di cPanel** — buat file `.env` dari template
4. **Via browser** — jalankan setup sekali (`hosting-setup.php`)
5. **Di cPanel** — atur cron job, hapus file setup

---

## Langkah 1: Build Paket di Komputer Lokal

Jalankan di terminal komputer Anda (bukan di hosting):

```bash
cd /path/ke/systemmanagemencore
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:$PATH"
chmod +x scripts/build-for-hosting.sh
./scripts/build-for-hosting.sh
```

Atau manual:

```bash
composer run build:hosting --no-interaction
```

Hasil: file ZIP `hfnms-hosting-YYYYMMDD.zip` berisi aplikasi siap upload.

---

## Langkah 2: Subdomain di cPanel

1. **Subdomains** → buat subdomain, misalnya `hfnms.domainanda.com`
2. Catat folder root subdomain, misalnya: `/home/user/hfnms.domainanda.com`
3. **Document Root** — arahkan ke folder **`public`** di dalam aplikasi:

   ```
   /home/user/hfnms.domainanda.com/public
   ```

   > Jika tidak bisa mengubah document root, upload seluruh project ke folder subdomain. File `.htaccess` di root project akan mengarahkan ke `public/`.

4. **Select PHP Version** → pilih **PHP 8.3** atau **8.4**

---

## Langkah 3: Database MySQL

1. **MySQL Databases** → buat database, misalnya `user_hfnms`
2. Buat user MySQL + password
3. **Add User To Database** → beri **ALL PRIVILEGES**
4. Catat: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

---

## Langkah 4: Upload File

1. Upload `hfnms-hosting-*.zip` via **File Manager**
2. Extract di folder subdomain (mis. `/home/user/hfnms.domainanda.com`)
3. Pastikan struktur seperti ini:

   ```
   hfnms.domainanda.com/
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── public/          ← document root subdomain
   │   ├── index.php
   │   ├── .htaccess
   │   ├── build/       ← assets sudah ter-build
   │   ├── hosting-setup.php
   │   └── cron.php
   ├── storage/
   ├── vendor/
   └── ...
   ```

4. Set permission folder (via File Manager → Change Permissions):
   - `storage/` → **755** atau **775** (recursive)
   - `bootstrap/cache/` → **755** atau **775** (recursive)

---

## Langkah 5: File `.env` di Server

1. Di File Manager, salin `.env.hosting.example` menjadi `.env`
2. Edit `.env` dan isi:

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://hfnms.domainanda.com

   DB_HOST=localhost
   DB_DATABASE=user_hfnms
   DB_USERNAME=user_hfnms
   DB_PASSWORD=password_anda

   HOSTING_SETUP_KEY=buat_string_acak_panjang
   CRON_SECRET_KEY=buat_string_acak_lain
   ```

3. Generate kunci acak di komputer lokal:

   ```bash
   openssl rand -hex 32
   ```

4. `APP_KEY` boleh dikosongkan — akan dibuat otomatis saat setup.

---

## Langkah 6: Setup via Browser (Tanpa Terminal)

Buka di browser (ganti domain dan key):

```
https://hfnms.domainanda.com/hosting-setup.php?key=HOSTING_SETUP_KEY_ANDA&seed=1
```

Parameter:
- `key` — wajib, sama dengan `HOSTING_SETUP_KEY` di `.env`
- `seed=1` — buat user awal (sekali saja)
- `force=1` — jalankan ulang jika diperlukan

**User default setelah seed:**

| Email | Password | Role |
|-------|----------|------|
| admin@hinet.local | password | Super Admin |

> **Ganti password admin segera** setelah login pertama.

### Setelah setup berhasil

1. **Hapus** file `public/hosting-setup.php` dari server
2. Buka `https://hfnms.domainanda.com` — aplikasi siap dipakai

---

## Langkah 7: Cron Job (Pengganti Terminal)

Beberapa fitur (notifikasi maintenance terjadwal) membutuhkan scheduler Laravel.

Di cPanel → **Cron Jobs** → tambahkan setiap **1 menit**:

```bash
curl -s "https://hfnms.domainanda.com/cron.php?token=CRON_SECRET_KEY_ANDA" > /dev/null 2>&1
```

Ganti URL dan token sesuai `.env`.

---

## Storage Link (Upload Foto / QR Code)

Setup otomatis mencoba membuat symlink `public/storage` → `storage/app/public`.

Jika gagal (beberapa hosting memblokir symlink):

1. cPanel → **File Manager** → **+ Link** (Symbolic Link)
2. Link: `public/storage`
3. Target: `storage/app/public`

---

## Troubleshooting

### Error 500 / halaman kosong

- Cek `storage/logs/laravel.log` via File Manager
- Pastikan `storage/` dan `bootstrap/cache/` writable
- Pastikan PHP 8.3+

### CSS/JS tidak muncul

- Pastikan folder `public/build/` ter-upload
- Pastikan **tidak ada** file `public/hot`
- `APP_URL` harus sama persis dengan URL subdomain (termasuk `https://`)

### Database connection refused

- `DB_HOST` di shared hosting biasanya `localhost`, bukan `127.0.0.1`
- Pastikan user database sudah di-assign ke database

### Session / login tidak tahan

- Pastikan tabel `sessions` sudah ada (setup menjalankan migrasi)
- `SESSION_DRIVER=database` di `.env`

### Mixed content (HTTP/HTTPS)

- Set `APP_URL=https://subdomain.domainanda.com`
- Aktifkan SSL di cPanel (Let's Encrypt)

---

## Keamanan Production

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Hapus `public/hosting-setup.php` setelah setup
- [ ] Ganti password user default
- [ ] `HOSTING_SETUP_KEY` dan `CRON_SECRET_KEY` unik & panjang
- [ ] Aktifkan HTTPS

---

## Fitur yang Tidak Tersedia di Shared Hosting

| Fitur | Alternatif |
|-------|------------|
| `composer run dev` | Tidak diperlukan di production |
| Laravel Reverb (WebSocket) | Dinonaktifkan (`BROADCAST_CONNECTION=log`) |
| Queue worker | Saat ini tidak ada job kritis; `QUEUE_CONNECTION=database` siap jika diperlukan |
| `php artisan` CLI | Gunakan `hosting-setup.php` dan `cron.php` |

---

## Update Aplikasi ke Versi Baru

1. Build ulang paket di lokal: `./scripts/build-for-hosting.sh`
2. Backup database & `.env` di server
3. Upload file baru (jangan timpa `.env`)
4. Buka: `https://subdomain.../hosting-setup.php?key=...&force=1` (tanpa `seed=1`)
5. Hapus lagi `hosting-setup.php`
