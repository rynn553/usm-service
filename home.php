<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ruang Kerja | Perkhidmatan USM</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
</head>
<body class="workspace">
<header class="workspace-header">
    <div class="brand"><span class="brand-mark">USM</span><div><strong>Perkhidmatan USM</strong><span>Rekod &amp; Jejak Perkhidmatan</span></div></div>
    <form method="post" action="<?= APP_BASE ?>/logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="outline">Logout</button>
    </form>
</header>
<main class="workspace-content">
    <p class="eyebrow">RUANG KERJA STAF IT</p>
    <h1>Selamat datang, <?= e((string) ($_SESSION['user_name'] ?? 'Admin')) ?>.</h1>
    <p>Anda berjaya log masuk.</p>
    <section class="home-card"><h2>Ruang kerja sedang disediakan</h2><p>Dashboard dan modul perkhidmatan akan disambungkan di sini selepas siap.</p></section>
</main>
</body></html>
