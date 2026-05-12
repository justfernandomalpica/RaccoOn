<?php declare(strict_types=1);

use App\Database\Database;

/**
 * Create the optional mysqli connection used by ActiveRecord models.
 *
 * This file is loaded only when APP_USE_DATABASE=true.
 *
 * @throws RuntimeException When mysqli cannot connect with the configured credentials.
 */
$mysqli = mysqli_connect(
    (string) env('DB_HOST', 'localhost'),
    (string) env('DB_USER', ''),
    (string) env('DB_PASS', ''),
    (string) env('DB_NAME', ''),
    (int) env('DB_PORT', 3306)
);

if (!$mysqli) {
    $error = mysqli_connect_error();
    $errno = mysqli_connect_errno();
    throw new RuntimeException("Database connection failed: {$error} | {$errno}");
}

$db = new Database($mysqli);
