<?php

/**
 * Hapus config cache rusak — TANPA Laravel. Hapus setelah selesai.
 * https://smc.laksanatech.com/fix-cache.php?key=HOSTING_SETUP_KEY
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$token = $_GET['key'] ?? '';
$envPath = $root.'/.env';

function readKey(string $path, string $key): string
{
    if (! is_file($path) || ! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', file_get_contents($path) ?: '', $m)) {
        return '';
    }

    return trim(trim($m[1]), "\"'");
}

if (! hash_equals(readKey($envPath, 'HOSTING_SETUP_KEY'), is_string($token) ? $token : '')) {
    http_response_code(403);
    echo '<h1>Akses ditolak</h1>';
    exit;
}

echo '<h1>Fix Cache</h1><pre>';

$deleted = 0;
foreach (glob($root.'/bootstrap/cache/*.php') ?: [] as $file) {
    if (@unlink($file)) {
        echo "Dihapus: bootstrap/cache/".basename($file)."\n";
        $deleted++;
    }
}

if ($deleted === 0) {
    echo "Tidak ada file cache .php (sudah bersih).\n";
}

$dirs = [
    $root.'/storage/framework/views',
    $root.'/storage/framework/cache',
    $root.'/storage/framework/sessions',
    $root.'/storage/logs',
    $root.'/storage/app/public',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
        echo "Dibuat: {$dir}\n";
    }
    echo (is_writable($dir) ? 'Writable' : 'TIDAK writable').": {$dir}\n";
}

echo "\nSelesai. Sekarang buka:\n";
echo "/run-migrate.php?key=".urlencode($token)."&seed=1\n";
echo '</pre>';
