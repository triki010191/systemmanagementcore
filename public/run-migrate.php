<?php

/**
 * Migrasi database untuk hosting tanpa terminal.
 * https://smc.laksanatech.com/run-migrate.php?key=HOSTING_SETUP_KEY&seed=1
 * HAPUS setelah berhasil.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root.'/.env';
$token = $_GET['key'] ?? '';
$withSeed = isset($_GET['seed']);

function envFromFile(string $path, string $key): string
{
    if (! is_file($path)) {
        return '';
    }

    $contents = file_get_contents($path);

    if ($contents === false || preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $m) !== 1) {
        return '';
    }

    $value = trim($m[1]);

    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"'))
        || (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    return $value;
}

$expectedKey = envFromFile($envPath, 'HOSTING_SETUP_KEY');

if ($expectedKey === '' || ! is_string($token) || ! hash_equals($expectedKey, $token)) {
    http_response_code(403);
    echo '<h1>Akses ditolak</h1><p>Parameter <code>?key=</code> tidak valid.</p>';
    exit;
}

// Paksa driver file saat setup — tabel session/cache belum ada
putenv('APP_DEBUG=true');
putenv('SESSION_DRIVER=file');
putenv('CACHE_STORE=file');
$_ENV['APP_DEBUG'] = 'true';
$_ENV['SESSION_DRIVER'] = 'file';
$_ENV['CACHE_STORE'] = 'file';

echo '<h1>HFNMS — Migrasi Database</h1><pre>';

foreach (glob($root.'/bootstrap/cache/*.php') ?: [] as $cacheFile) {
    @unlink($cacheFile);
    echo 'Hapus cache: '.basename($cacheFile)."\n";
}

foreach ([
    $root.'/storage/framework/views',
    $root.'/storage/framework/cache',
    $root.'/storage/framework/sessions',
    $root.'/storage/logs',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
        echo 'Buat folder: '.$dir."\n";
    }
}

try {
    define('LARAVEL_START', microtime(true));

    require $root.'/vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once $root.'/bootstrap/app.php';

    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "Laravel boot: OK\n";

    if ((string) $app->make('config')->get('app.key') === '') {
        \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
        echo "APP_KEY dibuat\n";
    }

    $app->make('db')->connection()->getPdo();
    echo "Database: OK\n";

    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "Migrasi:\n".\Illuminate\Support\Facades\Artisan::output()."\n";

    if ($withSeed) {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo "Seeder: OK (admin@hinet.local / password)\n";
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        echo "Storage link: OK\n";
    } catch (Throwable) {
        @symlink($root.'/storage/app/public', $root.'/public/storage');
        echo "Storage link: symlink manual\n";
    }

    file_put_contents($root.'/storage/app/hosting-setup-complete', date('c'));

    echo "\n=== SELESAI ===\n";
    echo "Hapus run-migrate.php dan hosting-setup.php\n";
    echo "Buka: https://smc.laksanatech.com/\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "\nERROR: ".$e->getMessage()."\n\n";
    echo $e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString();
}

echo '</pre>';
