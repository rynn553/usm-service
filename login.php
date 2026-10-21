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
            if (
                $user && (int) $user['is_active'] === 1 && is_string($user['password_hash'])
                && password_verify($password, $user['password_hash'])
            ) {
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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#32165e">
<title>Log Masuk | e-Servis IT@AMDI</title>
<style>
:root{--purple:#38196c;--purple2:#5b2c95;--gold:#f5b743;--ink:#241a35;--muted:#817b8b;--border:#e8e4ef}
*{box-sizing:border-box}html{min-height:100%}body{margin:0;min-height:100vh;font-family:Inter,"Segoe UI",Arial,sans-serif;color:var(--ink);background:#f8f7fc}
.login-layout{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(420px,.95fr)}
.brand-panel{position:relative;overflow:hidden;background:linear-gradient(145deg,#2c1059 0%,#4b2080 58%,#66359c 100%);display:flex;align-items:center;justify-content:center;padding:64px 42px;color:#fff}
.brand-panel:before{content:"";position:absolute;width:570px;height:570px;border:1px solid rgba(255,255,255,.12);border-radius:50%;left:-300px;top:-330px;box-shadow:0 0 0 85px rgba(255,255,255,.025),0 0 0 175px rgba(255,255,255,.02)}
.brand-panel:after{content:"";position:absolute;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(244,183,67,.14),transparent 67%);right:-170px;bottom:-190px}
.brand-content{position:relative;z-index:1;text-align:center;max-width:510px;animation:rise .65s ease-out both}
.logo-frame{display:flex;align-items:center;justify-content:center;margin:0 auto 38px;padding:22px 26px;width:min(100%,340px);min-height:210px;background:#fff;border:1px solid rgba(255,255,255,.5);border-radius:22px;box-shadow:0 22px 52px rgba(16,4,40,.25)}
.logo-frame img{display:block;width:100%;max-width:286px;height:auto;object-fit:contain}
.brand-content h1{font-size:clamp(32px,3.8vw,50px);line-height:1.13;letter-spacing:-1.7px;margin:0 0 16px;font-weight:800}
.brand-subtitle{font-size:13px;line-height:1.8;font-weight:650;letter-spacing:2px;color:#eee4fa;margin:0}
.brand-rule{width:54px;height:4px;border-radius:5px;background:var(--gold);margin:26px auto 0}
.brand-bottom{position:absolute;bottom:29px;left:24px;right:24px;text-align:center;color:#d8c9ea;font-size:12px;letter-spacing:.2px}
.form-panel{display:flex;align-items:center;justify-content:center;padding:58px clamp(24px,5vw,78px);background:radial-gradient(circle at 100% 0%,#f0eafb,transparent 44%),#fcfbfe}
.form-wrap{width:100%;max-width:410px;animation:rise .75s .09s ease-out both}
.form-kicker{display:inline-flex;align-items:center;gap:9px;color:var(--purple2);font-size:12px;font-weight:750;letter-spacing:1.3px;text-transform:uppercase}
.form-kicker:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--gold)}
.form-wrap h2{margin:18px 0 9px;font-size:clamp(30px,3.2vw,39px);font-weight:800;letter-spacing:-1.1px;color:#2b1945}
.form-description{font-size:14px;line-height:1.7;color:var(--muted);margin:0 0 38px}
.form-wrap form{display:block}
.field{margin-bottom:23px}.field label{display:block;font-size:13px;font-weight:700;color:#3b3150;margin:0 0 10px}
.field input{width:100%;height:52px;padding:0 16px;border-radius:12px;border:1px solid #dfd9e9;background:#fff;color:#2c2040;font:inherit;font-size:14px;transition:border-color .18s,box-shadow .18s}
.field input::placeholder{color:#aaa3b3}.field input:focus{outline:none;border-color:#8055ba;box-shadow:0 0 0 4px rgba(100,58,154,.1)}
.password-field{position:relative}.password-field input{padding-right:110px}
.password-toggle{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:transparent;border:0;padding:8px;color:#593087;font-size:12px;font-weight:750;cursor:pointer}
.password-toggle[hidden]{display:none}
.login-submit{display:flex;align-items:center;justify-content:center;gap:11px;width:100%;min-height:54px;border:0;border-radius:12px;background:linear-gradient(110deg,#3a196d,#633397);color:#fff;font-size:15px;font-weight:750;cursor:pointer;box-shadow:0 12px 25px rgba(66,29,112,.18);transition:transform .18s,box-shadow .18s}
.login-submit:hover{transform:translateY(-2px);box-shadow:0 16px 30px rgba(66,29,112,.25)}.login-submit:focus-visible,.password-toggle:focus-visible{outline:3px solid #b693e4;outline-offset:3px}
.footnote{font-size:12px;color:#9890a4;text-align:center;margin:27px 0 0}
.alert,.notice{padding:13px 15px;margin:0 0 22px;border-radius:11px;font-size:13px;line-height:1.5}
.alert{background:#fff0f0;color:#9d2431;border:1px solid #f4c9cf}.notice{background:#eefaf1;color:#25683b;border:1px solid #c9e9d0}
.mobile-brand{display:none}
@keyframes rise{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:900px){.login-layout{grid-template-columns:minmax(0,1fr) minmax(380px,1fr)}.brand-panel{padding:42px 24px}.logo-frame{width:min(100%,285px);min-height:175px;padding:18px}.brand-content h1{font-size:34px}.brand-subtitle{font-size:11px;letter-spacing:1.2px}.form-panel{padding:42px 30px}}
@media(max-width:700px){.login-layout{display:block}.brand-panel{min-height:0;padding:34px 20px 32px}.brand-content{width:100%}.logo-frame{width:188px;min-height:0;padding:12px;border-radius:14px;margin-bottom:20px}.brand-content h1{font-size:28px;letter-spacing:-.7px;margin-bottom:9px}.brand-subtitle{font-size:10px;letter-spacing:1px}.brand-rule{margin-top:16px;width:40px;height:3px}.brand-bottom{display:none}.form-panel{min-height:calc(100vh - 290px);padding:40px 22px 50px}.form-wrap{max-width:440px}.form-wrap h2{font-size:32px}.form-description{margin-bottom:28px}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body>
<main class="login-layout">
  <section class="brand-panel" aria-label="Identiti sistem">
    <div class="brand-content">
      <div class="logo-frame">
        <img src="<?= APP_BASE ?>/usm-logo.png" alt="Logo rasmi Universiti Sains Malaysia" width="286" height="232">
      </div>
      <h1>e-Servis IT@AMDI</h1>
      <p class="brand-subtitle">SISTEM PENGURUSAN PERKHIDMATAN IT</p>
      <div class="brand-rule" aria-hidden="true"></div>
    </div>
    <p class="brand-bottom">Universiti Sains Malaysia · AMDI</p>
  </section>
  <section class="form-panel" aria-labelledby="login-title">
    <div class="form-wrap">
      <span class="form-kicker">Portal Staf IT</span>
      <h2 id="login-title">Selamat Datang</h2>
      <p class="form-description">Log masuk menggunakan akaun staf IT anda.</p>
      <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
      <?php if (isset($_GET['logged_out'])): ?><p class="notice" role="status">Anda telah log keluar.</p><?php endif; ?>
      <form method="post" action="<?= APP_BASE ?>/login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" maxlength="100" placeholder="Masukkan nama pengguna" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata laluan" required>
            <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" hidden>Tunjuk</button>
          </div>
        </div>
        <button class="login-submit" type="submit">Log Masuk <span aria-hidden="true">→</span></button>
      </form>
      <p class="footnote">Akses untuk staf IT yang dibenarkan sahaja.</p>
    </div>
  </section>
</main>
<script>
(function () {
  const toggle = document.querySelector('.password-toggle');
  const password = document.getElementById('password');
  if (!toggle || !password) return;
  toggle.hidden = false;
  toggle.addEventListener('click', function () {
    const show = password.type === 'password';
    password.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'Sembunyi' : 'Tunjuk';
    toggle.setAttribute('aria-pressed', String(show));
  });
})();
</script>
</body>
</html>
