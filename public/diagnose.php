<?php

/**
 * Diagnosa hosting tanpa memuat Laravel penuh. Hapus setelah selesai.
 * Akses: https://smc.laksanatech.com/diagnose.php?key=HOSTING_SETUP_KEY
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root.'/.env';
$token = $_GET['key'] ?? '';

function readEnvValue(string $path, string $key): ?string
{
    if (! is_file($path)) {
        return null;
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
        return null;
    }

    if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) !== 1) {
        return null;
    }

    $value = trim($matches[1]);

    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"'))
        || (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    return $value;
}

$expectedKey = readEnvValue($envPath, 'HOSTING_SETUP_KEY');

if ($expectedKey === null || $expectedKey === '' || ! is_string($token) || ! hash_equals($expectedKey, $token)) {
    http_response_code(403);
    echo '<h1>Akses ditolak</h1><p>Tambahkan <code>?key=HOSTING_SETUP_KEY</code> dari file .env</p>';
    exit;
}

$checks = [];

$checks[] = ['PHP version', PHP_VERSION, PHP_VERSION_ID >= 80300];

$checks[] = ['File .env ada', is_file($envPath) ? 'ya' : 'tidak', is_file($envPath)];
$checks[] = ['Folder vendor/', is_dir($root.'/vendor') ? 'ya' : 'tidak', is_dir($root.'/vendor')];
$checks[] = ['public/build/', is_dir(__DIR__.'/build') ? 'ya' : 'tidak', is_dir(__DIR__.'/build')];
$checks[] = ['storage writable', is_writable($root.'/storage') ? 'ya' : 'tidak', is_writable($root.'/storage')];
$checks[] = ['bootstrap/cache writable', is_writable($root.'/bootstrap/cache') ? 'ya' : 'tidak', is_writable($root.'/bootstrap/cache')];

$appKey = readEnvValue($envPath, 'APP_KEY');
$checks[] = ['APP_KEY terisi', ($appKey !== null && $appKey !== '') ? 'ya' : 'kosong', $appKey !== null && $appKey !== ''];

$configCache = $root.'/bootstrap/cache/config.php';
$checks[] = ['config.php cache', is_file($configCache) ? 'ADA (hapus jika error)' : 'tidak ada', true];

$dbHost = readEnvValue($envPath, 'DB_HOST') ?? 'localhost';
$dbName = readEnvValue($envPath, 'DB_DATABASE') ?? '';
$dbUser = readEnvValue($envPath, 'DB_USERNAME') ?? '';
$dbPass = readEnvValue($envPath, 'DB_PASSWORD') ?? '';

$dbOk = false;
$dbMessage = 'belum dicek';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $dbOk = true;
    $dbMessage = 'koneksi OK';
} catch (Throwable $exception) {
    $dbMessage = $exception->getMessage();
}

$checks[] = ['Koneksi MySQL', $dbMessage, $dbOk];

$logFile = $root.'/storage/logs/laravel.log';
$lastLog = is_file($logFile) ? implode("\n", array_slice(file($logFile) ?: [], -15)) : 'belum ada log';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Diagnosa HFNMS</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
        .ok { color: #166534; }
        .bad { color: #b91c1c; }
        pre { background: #111; color: #eee; padding: 1rem; overflow: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Diagnosa Hosting HFNMS</h1>
    <table>
        <tr><th>Cek</th><th>Hasil</th><th>Status</th></tr>
        <?php foreach ($checks as [$label, $value, $ok]) { ?>
            <tr>
                <td><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'GAGAL' ?></td>
            </tr>
        <?php } ?>
    </table>

    <h2>Log terakhir (laravel.log)</h2>
    <pre><?= htmlspecialchars($lastLog, ENT_QUOTES, 'UTF-8') ?></pre>

    <h2>Jika masih error 500</h2>
    <ol>
        <li>Hapus <code>bootstrap/cache/config.php</code> jika ada</li>
        <li>Sementara di .env: <code>SESSION_DRIVER=file</code> dan <code>CACHE_STORE=file</code></li>
        <li>Jalankan <a href="/hosting-setup.php?key=<?= urlencode($token) ?>&seed=1">hosting-setup.php</a></li>
        <li>Hapus diagnose.php setelah selesai</li>
    </ol>
</body>
</html>
