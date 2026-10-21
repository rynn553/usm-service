<?php

declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Gunakan butang Logout untuk log keluar.');
}
if (!valid_csrf()) {
    http_response_code(403);
    exit('Sesi borang tidak sah. Muat semula halaman dan cuba lagi.');
}
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'],
]);
session_destroy();
header('Location: ' . APP_BASE . '/login.php?logged_out=1');
exit;
