<?php
// config.php — database connection using .env for security
// NEVER hardcode credentials here

require_once __DIR__ . '/env_loader.php';

$conn = mysqli_connect(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

if (!$conn) {
    // Log real error server-side, show generic message to user
    error_log("DB Connection failed: " . mysqli_connect_error());
    die("<script>alert('Database connection failed. Please try again later.')</script>");
}

// Set charset to prevent encoding attacks
mysqli_set_charset($conn, 'utf8mb4');
