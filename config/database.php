<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

// Local XAMPP defaults. Override using environment variables if needed.
$dbHost = getenv('USM_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('USM_DB_PORT') ?: '3306';
$dbName = 'usm-service';
$dbUser = getenv('USM_DB_USER') ?: 'root';
$dbPassword = getenv('USM_DB_PASSWORD');
if ($dbPassword === false) {
    $dbPassword = '';
}

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$pdo->exec("SET time_zone = '+08:00'");
unset($dbPassword);
