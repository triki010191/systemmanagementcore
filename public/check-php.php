<?php

/**
 * Cek versi PHP yang dipakai Apache untuk folder ini.
 * TIDAK memuat Laravel/Composer. Hapus file ini setelah dicek.
 */

header('Content-Type: text/html; charset=utf-8');

$version = PHP_VERSION;
$versionId = PHP_VERSION_ID;
$ok = $versionId >= 80300;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cek PHP — HFNMS</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 560px; margin: 2rem auto; padding: 0 1rem; }
        .ok { color: #166534; font-weight: bold; }
        .bad { color: #b91c1c; font-weight: bold; }
        code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Cek versi PHP</h1>
    <p>Versi PHP saat ini: <code><?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></code></p>
    <p>PHP_VERSION_ID: <code><?= $versionId ?></code></p>
    <p>Minimal dibutuhkan: <code>8.3.0</code> (ID &gt;= 80300)</p>
    <p>Status:
        <?php if ($ok) : ?>
            <span class="ok">OK — bisa lanjut hosting-setup.php</span>
        <?php else : ?>
            <span class="bad">TERLALU RENDAH — ubah PHP ke 8.3 di cPanel atau perbaiki .htaccess</span>
        <?php endif; ?>
    </p>
    <p><small>Hapus file <code>check-php.php</code> setelah selesai.</small></p>
</body>
</html>
