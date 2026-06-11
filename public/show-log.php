<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$envPath = $root.'/.env';
$token = $_GET['key'] ?? '';

function envKey(string $path, string $key): string
{
    if (! is_file($path) || ! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', file_get_contents($path) ?: '', $m)) {
        return '';
    }

    $value = trim($m[1]);

    return trim($value, "\"'");
}

if (! hash_equals(envKey($envPath, 'HOSTING_SETUP_KEY'), is_string($token) ? $token : '')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$log = $root.'/storage/logs/laravel.log';

if (! is_file($log)) {
    echo 'Belum ada laravel.log';
    exit;
}

$lines = file($log) ?: [];

echo implode('', array_slice($lines, -40));
