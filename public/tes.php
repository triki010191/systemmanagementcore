<?php

/**
 * Tes hosting — baca kredensial dari .env. Hapus setelah selesai.
 */

echo 'PHP: '.PHP_VERSION.'<br>';

$root = dirname(__DIR__);
if (! file_exists($root.'/.env') && file_exists(__DIR__.'/../.env')) {
    $root = dirname(__DIR__);
}

$envPath = $root.'/.env';

function envValue(string $path, string $key): string
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

echo file_exists($envPath) ? '.env: ADA<br>' : '.env: TIDAK ADA<br>';
echo file_exists($root.'/vendor/autoload.php') ? 'vendor: ADA<br>' : 'vendor: TIDAK ADA<br>';
echo is_writable($root.'/storage') ? 'storage: writable<br>' : 'storage: TIDAK writable<br>';

$host = envValue($envPath, 'DB_HOST') ?: 'localhost';
$port = envValue($envPath, 'DB_PORT') ?: '3306';
$database = envValue($envPath, 'DB_DATABASE');
$username = envValue($envPath, 'DB_USERNAME');
$password = envValue($envPath, 'DB_PASSWORD');

echo 'DB_HOST: '.htmlspecialchars($host, ENT_QUOTES, 'UTF-8').'<br>';
echo 'DB_DATABASE: '.htmlspecialchars($database, ENT_QUOTES, 'UTF-8').'<br>';
echo 'DB_USERNAME: '.htmlspecialchars($username, ENT_QUOTES, 'UTF-8').'<br>';
echo 'DB_PASSWORD: '.($password !== '' ? '(terisi)' : '(kosong)').'<br>';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    echo '<strong>Database: OK</strong>';
} catch (Throwable $e) {
    echo '<strong>Database: GAGAL</strong> - '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
