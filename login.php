<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/home.php');
    exit;
}
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!valid_csrf()) {
        http_response_code(403);
        $error = 'Sesi borang tidak sah. Sila cuba lagi.';
    } elseif ($username === '' || $password === '') {
        $error = 'Sila masukkan username dan password.';
    } elseif (strlen($username) > 100 || strlen($password) > 4096) {
        $error = 'Username atau password tidak sah.';
    } else {
        try {
            require __DIR__ . '/config/database.php';
            $statement = $pdo->prepare('SELECT id, name, password_hash, is_active FROM users WHERE username = :username LIMIT 1');
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
            if ($user && (int) $user['is_active'] === 1 && is_string($user['password_hash'])
                && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION = ['user_id' => (int) $user['id'], 'user_name' => $user['name']];
                csrf_token();
                header('Location: ' . APP_BASE . '/home.php');
                exit;
            }
            $error = 'Username atau password tidak sah.';
        } catch (PDOException $exception) {
            error_log('USM login database error: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Sistem tidak dapat dihubungi. Sila cuba semula sebentar lagi.';
        }
    }
    unset($password);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Masuk | Perkhidmatan USM</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
    <script src="<?= APP_BASE ?>/assets/js/login.js" defer></script>
</head>
<body class="login-page">
<main class="login-shell">
    <div class="brand"><span class="brand-mark" aria-label="USM">USM</span><div><strong>Universiti Sains Malaysia</strong><span>Service Recording &amp; Tracking System</span></div></div>
    <section class="login-card" aria-labelledby="login-title">
        <div class="gold-line"></div><p class="eyebrow">RUANG KERJA STAF IT</p>
        <h1 id="login-title">TRYYY Permintaan perkhidmatan,<br>diurus dengan jelas.</h1>
        <p class="intro">Log masuk untuk merekod perkhidmatan, menjejak kemajuan dan menyemak permintaan yang selesai.</p>
        <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if (isset($_GET['logged_out'])): ?><p class="notice" role="status">Anda telah log keluar.</p><?php endif; ?>
        <form method="post" action="<?= APP_BASE ?>/login.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" maxlength="100" required>
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" hidden>Tunjuk</button>
            </div>
            <button class="primary login-submit" type="submit">Log Masuk <span aria-hidden="true">&rarr;</span></button>
        </form>
        <p class="footnote">Akses untuk pentadbiran perkhidmatan IT.</p>
    </section>
</main>
</body></html>
