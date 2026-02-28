<?php
// login.php — Google OAuth handler
// Flow: index.php "Sign in with Google" → this page → Google → back here with ?code= → welcome.php

session_start();

// FIX: Regenerate session ID on first visit to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

require_once 'config.php'; // loads .env — must come before autoload

// FIX 1: Force HTTPS BEFORE loading the Google library.
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// Redirect already-logged-in users
if (isset($_SESSION['username']) || isset($_SESSION['login_id'])) {
    header('Location: welcome.php');
    exit;
}

// Load Google library AFTER HTTPS check so redirect always fires first
require 'google-api/vendor/autoload.php';

// -------------------------------------------------------
// Google OAuth client setup
// -------------------------------------------------------
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

// FIX: Better SSL handling - check if cert file exists, fallback to system
$certPath = 'C:/xampp/apache/bin/curl-ca-bundle.crt';
if (file_exists($certPath)) {
    $client->setHttpClient(new GuzzleHttp\Client([
        'verify' => $certPath,
    ]));
}

$google_error = '';

// Catch success message set by register.php
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// -------------------------------------------------------
// Handle Google OAuth callback (?code= in URL)
// -------------------------------------------------------
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    } catch (Exception $e) {
        $google_error = 'Connection to Google failed. Please check your SSL configuration.';
        error_log('Google token exception: ' . $e->getMessage());
        $token = ['error' => true];
    }

    if (!isset($token['error']) || $token['error'] === false) {
        $client->setAccessToken($token['access_token']);
        $google_oauth        = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        // FIX: Sanitize all Google data before use
        $google_id   = htmlspecialchars($google_account_info->id);
        $full_name   = htmlspecialchars(trim($google_account_info->name));
        $email       = filter_var($google_account_info->email, FILTER_SANITIZE_EMAIL);
        $profile_pic = filter_var($google_account_info->picture, FILTER_VALIDATE_URL) ?: '';

        // Check if Google account already registered
        $stmt = mysqli_prepare($conn, "SELECT id, username FROM users WHERE google_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $google_id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user) {
            // Returning Google user — log in directly
            session_regenerate_id(true);
            $_SESSION['login_id'] = $google_id;
            $_SESSION['username'] = $user['username'] ?? $full_name;
            $_SESSION['user_id']  = $user['id'];
            header('Location: welcome.php');
            exit;

        } else {
            // Check if they have a manual account with the same email
            $chk = mysqli_prepare($conn, "SELECT id, username FROM users WHERE email = ?");
            mysqli_stmt_bind_param($chk, 's', $email);
            mysqli_stmt_execute($chk);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));

            if ($existing) {
                // Link Google ID to their existing manual account
                $upd = mysqli_prepare($conn, "UPDATE users SET google_id = ?, profile_image = ? WHERE email = ?");
                mysqli_stmt_bind_param($upd, 'sss', $google_id, $profile_pic, $email);
                mysqli_stmt_execute($upd);

                session_regenerate_id(true);
                $_SESSION['login_id'] = $google_id;
                $_SESSION['username'] = $existing['username'] ?? $full_name;
                $_SESSION['user_id']  = $existing['id'];
                header('Location: welcome.php');
                exit;

            } else {
                // Brand new user — create account from Google profile
                $ins = mysqli_prepare($conn,
                    "INSERT INTO users (google_id, username, name, email, profile_image) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins, 'sssss', $google_id, $full_name, $full_name, $email, $profile_pic);

                if (mysqli_stmt_execute($ins)) {
                    session_regenerate_id(true);
                    $_SESSION['login_id'] = $google_id;
                    $_SESSION['username'] = $full_name;
                    $_SESSION['user_id']  = mysqli_insert_id($conn);
                    header('Location: welcome.php');
                    exit;
                } else {
                    $google_error = 'Account creation failed. Please try again.';
                    error_log('Google insert failed: ' . mysqli_error($conn));
                }
            }
        }

    } elseif (empty($google_error)) {
        $google_error = 'Google authentication failed. Please try again.';
        error_log('Google token error: ' . json_encode($token));
    }
}

// Generate the URL the Google button will link to
$google_login_url = $client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in with Google — CW2 Secure</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 44px 36px;
            width: 100%; max-width: 380px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 14px; }
        h2 { color: #1a1a2e; font-size: 1.6rem; margin-bottom: 6px; }
        .subtitle { color: #888; font-size: 0.85rem; margin-bottom: 8px; }
        .ssl-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a;
            font-size: 0.72rem; font-weight: 600; padding: 4px 12px;
            border-radius: 20px; margin-bottom: 24px;
        }
        .error-box {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 12px 14px; margin-bottom: 20px; color: #dc2626;
            font-size: 0.88rem; text-align: left;
        }
        .success-box {
            background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
            padding: 12px 14px; margin-bottom: 20px; color: #16a34a; font-size: 0.88rem;
        }
        .google-btn {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; padding: 13px 20px; background: #fff; color: #444;
            border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem;
            font-weight: 600; text-decoration: none; transition: all 0.2s;
        }
        .google-btn:hover {
            border-color: #4285F4; color: #4285F4;
            box-shadow: 0 2px 12px rgba(66,133,244,0.15);
        }
        .google-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
        .divider {
            display: flex; align-items: center; margin: 20px 0;
            color: #ccc; font-size: 0.82rem;
        }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #eee; }
        .divider::before { margin-right: 10px; }
        .divider::after  { margin-left:  10px; }
        .back-link {
            display: inline-block; color: #888; font-size: 0.83rem;
            text-decoration: none; transition: color 0.2s;
        }
        .back-link:hover { color: #4f46e5; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🔐</div>
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in with your Google account</p>
    <div class="ssl-badge">🔒 Secured with SSL / HTTPS</div>

    <?php if ($success): ?>
        <div class="success-box">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($google_error): ?>
        <div class="error-box">⚠ <?= htmlspecialchars($google_error) ?></div>
    <?php endif; ?>

    <a href="<?= htmlspecialchars($google_login_url) ?>" class="google-btn">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Sign in with Google
    </a>

    <div class="divider">or</div>
    <a href="index.php" class="back-link">← Sign in with email &amp; password</a>
</div>
</body>
</html>