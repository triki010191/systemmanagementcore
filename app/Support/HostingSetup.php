<?php

namespace App\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Throwable;

class HostingSetup
{
    private const COMPLETE_MARKER = 'hosting-setup-complete';

    /**
     * @return array{ok: bool, messages: list<string>, errors: list<string>}
     */
    public static function run(Application $app, bool $withSeed = false, bool $force = false): array
    {
        $messages = [];
        $errors = [];

        if (self::isComplete() && ! $force) {
            return [
                'ok' => false,
                'messages' => [],
                'errors' => ['Setup sudah pernah dijalankan. Hapus file hosting-setup.php dari server.'],
            ];
        }

        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.3.0', '<')) {
            $errors[] = "PHP 8.3+ diperlukan. Versi saat ini: {$phpVersion}";
        } else {
            $messages[] = "PHP {$phpVersion} — OK";
        }

        foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'] as $extension) {
            if (! extension_loaded($extension)) {
                $errors[] = "Ekstensi PHP tidak ditemukan: {$extension}";
            }
        }

        foreach ([
            storage_path(),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            storage_path('app'),
            storage_path('app/public'),
            bootstrap_path('cache'),
        ] as $directory) {
            if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
                $errors[] = "Gagal membuat folder: {$directory}";
            } elseif (! is_writable($directory)) {
                $errors[] = "Folder tidak bisa ditulis (chmod 755/775): {$directory}";
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'messages' => $messages, 'errors' => $errors];
        }

        $messages[] = 'Folder storage & bootstrap/cache — OK';

        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();

        $keyResult = self::ensureAppKey($app);
        $messages = array_merge($messages, $keyResult['messages']);
        $errors = array_merge($errors, $keyResult['errors']);

        if ($errors !== []) {
            return ['ok' => false, 'messages' => $messages, 'errors' => $errors];
        }

        try {
            $app->make('db')->connection()->getPdo();
            $messages[] = 'Koneksi database — OK';
        } catch (Throwable $exception) {
            $errors[] = 'Koneksi database gagal: '.$exception->getMessage();

            return ['ok' => false, 'messages' => $messages, 'errors' => $errors];
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $messages[] = 'Migrasi database — OK';
            $messages[] = trim(Artisan::output()) ?: 'Tidak ada migrasi baru.';
        } catch (Throwable $exception) {
            $errors[] = 'Migrasi gagal: '.$exception->getMessage();

            return ['ok' => false, 'messages' => $messages, 'errors' => $errors];
        }

        if ($withSeed) {
            try {
                Artisan::call('db:seed', ['--force' => true]);
                $messages[] = 'Seeder — OK (user default: admin@hinet.local / password)';
            } catch (Throwable $exception) {
                $errors[] = 'Seeder gagal: '.$exception->getMessage();
            }
        }

        $linkResult = self::ensureStorageLink();
        $messages = array_merge($messages, $linkResult['messages']);
        if ($linkResult['errors'] !== []) {
            $errors = array_merge($errors, $linkResult['errors']);
        }

    foreach (glob(base_path('bootstrap/cache/*.php')) ?: [] as $cacheFile) {
      @unlink($cacheFile);
    }

    $messages[] = 'Config cache dihapus — OK (shared hosting tidak perlu config:cache)';

        if ($errors !== []) {
            return ['ok' => false, 'messages' => $messages, 'errors' => $errors];
        }

        file_put_contents(storage_path('app/'.self::COMPLETE_MARKER), now()->toIso8601String());
        $messages[] = 'Setup selesai. SEGERA hapus file public/hosting-setup.php dari server!';

        return ['ok' => true, 'messages' => $messages, 'errors' => []];
    }

    public static function isComplete(): bool
    {
        return file_exists(storage_path('app/'.self::COMPLETE_MARKER));
    }

    public static function validateToken(?string $token): bool
    {
        $expected = (string) env('HOSTING_SETUP_KEY', '');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    public static function validateCronToken(?string $token): bool
    {
        $expected = (string) env('CRON_SECRET_KEY', '');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /**
     * @return array{messages: list<string>, errors: list<string>}
     */
    private static function ensureAppKey(Application $app): array
    {
        $messages = [];
        $errors = [];

        if ((string) $app->make('config')->get('app.key') !== '') {
            $messages[] = 'APP_KEY sudah ada — OK';

            return ['messages' => $messages, 'errors' => $errors];
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $envPath = base_path('.env');

        if (! is_file($envPath) || ! is_writable($envPath)) {
            $errors[] = 'APP_KEY kosong dan file .env tidak bisa ditulis. Isi APP_KEY manual di .env.';

            return ['messages' => $messages, 'errors' => $errors];
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            $errors[] = 'Tidak bisa membaca file .env';

            return ['messages' => $messages, 'errors' => $errors];
        }

        if (preg_match('/^APP_KEY=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents) ?? $contents;
        } else {
            $contents .= PHP_EOL.'APP_KEY='.$key.PHP_EOL;
        }

        file_put_contents($envPath, $contents);
        $_ENV['APP_KEY'] = $key;
        putenv('APP_KEY='.$key);
        $app->make('config')->set('app.key', $key);

        $messages[] = 'APP_KEY dibuat otomatis — OK';

        return ['messages' => $messages, 'errors' => $errors];
    }

    /**
     * @return array{messages: list<string>, errors: list<string>}
     */
    private static function ensureStorageLink(): array
    {
        $messages = [];
        $errors = [];
        $publicStorage = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($publicStorage) || is_dir($publicStorage)) {
            $messages[] = 'Storage link — OK';

            return ['messages' => $messages, 'errors' => $errors];
        }

        try {
            Artisan::call('storage:link');
            $messages[] = 'Storage link (symlink) — OK';

            return ['messages' => $messages, 'errors' => $errors];
        } catch (Throwable) {
            if (@symlink($target, $publicStorage)) {
                $messages[] = 'Storage link (symlink manual) — OK';

                return ['messages' => $messages, 'errors' => $errors];
            }

            $errors[] = 'Gagal membuat storage link. Di cPanel buat symlink: public/storage → storage/app/public';
        }

        return ['messages' => $messages, 'errors' => $errors];
    }

    public static function generateSecret(): string
    {
        return Str::random(64);
    }
}
