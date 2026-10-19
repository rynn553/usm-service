<?php
declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
header('Location: ' . APP_BASE . (empty($_SESSION['user_id']) ? '/login.php' : '/home.php'));
exit;
