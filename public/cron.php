<?php

/**
 * HFNMS — Cron pengganti terminal untuk shared hosting.
 * cPanel → Cron Jobs → setiap menit:
 * curl -s "https://subdomain.domainanda.com/cron.php?token=CRON_SECRET_KEY" > /dev/null 2>&1
 */

declare(strict_types=1);

use App\Support\HostingSetup;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$token = $_GET['token'] ?? '';

if (! HostingSetup::validateCronToken(is_string($token) ? $token : null)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$status = $kernel->call('schedule:run');

header('Content-Type: text/plain; charset=utf-8');
echo $status === 0 ? 'OK' : 'ERROR';
