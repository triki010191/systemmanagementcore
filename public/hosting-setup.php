<?php

/**
 * HFNMS — Setup sekali jalan untuk hosting tanpa terminal/SSH.
 * Akses: https://subdomain.domainanda.com/hosting-setup.php?key=HOSTING_SETUP_KEY
 * HAPUS file ini setelah setup berhasil.
 */

declare(strict_types=1);

use App\Support\HostingSetup;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

define('LARAVEL_START', microtime(true));

header('Content-Type: text/html; charset=utf-8');

$token = $_GET['key'] ?? '';
$withSeed = isset($_GET['seed']);
$force = isset($_GET['force']);

// Paksa driver file saat setup — tabel session/cache mungkin belum ada
putenv('APP_DEBUG=true');
putenv('SESSION_DRIVER=file');
putenv('CACHE_STORE=file');
$_ENV['APP_DEBUG'] = 'true';
$_ENV['SESSION_DRIVER'] = 'file';
$_ENV['CACHE_STORE'] = 'file';

try {
    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    $app->make(Kernel::class)->bootstrap();
} catch (Throwable $bootstrapException) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Setup Error</title></head><body>';
    echo '<h1>Laravel gagal boot</h1>';
    echo '<p><strong>Pesan:</strong> '.htmlspecialchars($bootstrapException->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
    echo '<p>Coba: hapus <code>bootstrap/cache/config.php</code>, set <code>SESSION_DRIVER=file</code> dan <code>CACHE_STORE=file</code> di .env, lalu refresh.</p>';
    echo '<p>Atau buka <code>diagnose.php?key=...</code> untuk cek detail.</p>';
    echo '</body></html>';
    exit;
}

if (! HostingSetup::validateToken(is_string($token) ? $token : null)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Setup Ditolak</title></head><body>';
    echo '<h1>Akses ditolak</h1>';
    echo '<p>Parameter <code>?key=</code> tidak valid. Isi <code>HOSTING_SETUP_KEY</code> di file <code>.env</code>.</p>';
    echo '</body></html>';
    exit;
}

try {
    $result = HostingSetup::run($app, $withSeed, $force);
} catch (Throwable $runException) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Setup Error</title></head><body>';
    echo '<h1>Setup gagal</h1>';
    echo '<pre>'.htmlspecialchars($runException->getMessage()."\n\n".$runException->getTraceAsString(), ENT_QUOTES, 'UTF-8').'</pre>';
    echo '<p>Coba <a href="/run-migrate.php?key='.urlencode(is_string($token) ? $token : '').'&seed=1">run-migrate.php</a></p>';
    echo '</body></html>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HFNMS — Hosting Setup</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { font-size: 1.5rem; }
        .ok { color: #166534; }
        .err { color: #b91c1c; }
        ul { padding-left: 1.25rem; }
        code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 4px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
    </style>
</head>
<body>
    <h1>HFNMS — Setup Hosting</h1>

    <?php if ($result['ok']) { ?>
        <p class="ok"><strong>Setup berhasil.</strong></p>
    <?php } else { ?>
        <p class="err"><strong>Setup gagal atau sudah pernah dijalankan.</strong></p>
    <?php } ?>

    <?php if ($result['messages'] !== []) { ?>
        <div class="box">
            <h2>Log</h2>
            <ul>
                <?php foreach ($result['messages'] as $message) { ?>
                    <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <?php if ($result['errors'] !== []) { ?>
        <div class="box">
            <h2 class="err">Error</h2>
            <ul>
                <?php foreach ($result['errors'] as $error) { ?>
                    <li class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <div class="box">
        <h2>Langkah berikutnya</h2>
        <ol>
            <li>Hapus file <code>public/hosting-setup.php</code> dari server.</li>
            <li>Buka <a href="/">halaman utama</a> aplikasi.</li>
            <li>Atur cron job di cPanel (lihat <code>docs/HOSTING.md</code>).</li>
            <?php if ($withSeed) { ?>
                <li>Login: <code>admin@hinet.local</code> / <code>password</code> — ganti segera.</li>
            <?php } else { ?>
                <li>Jika belum ada user, jalankan ulang dengan <code>&amp;seed=1</code> (sekali saja).</li>
            <?php } ?>
        </ol>
    </div>
</body>
</html>
