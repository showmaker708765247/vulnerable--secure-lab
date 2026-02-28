<?php


function loadEnv($path) {
    if (!file_exists($path)) {
        die("FATAL: .env file not found at: $path");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        if (!strpos($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Load .env from one directory above web root 
loadEnv(__DIR__ . '/../.env');