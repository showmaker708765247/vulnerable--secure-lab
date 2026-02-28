<?php
session_start();
require_once 'config.php';
require_once 'csrf.php';

if (isset($_SESSION['username'])) {
    header("Location: welcome.php");
    exit;
}

$errors = [];
$email  = '';

// Show success message from registration redirect
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_POST['submit'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Input validation
    if (empty($email))
        $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    if (empty($password))
        $errors[] = "Password is required.";

    // reCAPTCHA check
    if (empty($_POST['g-recaptcha-response'])) {
        $errors[] = "Please complete the reCAPTCHA verification.";
    } else {
        $secretKey    = 'KEY HERE';
        $verifyUrl    = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . urlencode($_POST['g-recaptcha-response']);
        $responseKeys = json_decode(file_get_contents($verifyUrl), true);
        if (!$responseKeys["success"])
            $errors[] = "reCAPTCHA verification failed. Please try again.";
    }

    if (empty($errors)) {
        // Use prepared statement — no SQL injection possible
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        // password_verify works with bcrypt hashes
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id']  = $user['id'];
            header("Location: welcome.php");
            exit;
        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { text-align: center; color: #1a1a2e; margin-bottom: 6px; font-size: 1.6rem; }
        .subtitle { text-align: center; color: #888; font-size: 0.85rem; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; position: relative; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 5px; }
        input {
            width: 100%; padding: 10px 14px;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 0.95rem; transition: border 0.2s; outline: none;
        }
        input:focus { border-color: #4f46e5; }
        .errors {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 8px; padding: 12px 14px; margin-bottom: 18px;
        }
        .errors p { color: #dc2626; font-size: 0.85rem; margin-bottom: 3px; }
        .success-box {
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 8px; padding: 12px 14px; margin-bottom: 18px;
            color: #16a34a; font-size: 0.85rem;
        }
        .btn {
            width: 100%; padding: 12px;
            background: #4f46e5; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s; margin-top: 4px;
        }
        .btn:hover { background: #4338ca; }
        .google-btn {
            width: 100%; padding: 11px;
            background: #fff; color: #444;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
            gap: 10px; text-decoration: none; margin-top: 8px;
        }
        .google-btn:hover { border-color: #4285F4; color: #4285F4; }
        .divider {
            display: flex; align-items: center;
            text-align: center; margin: 16px 0; color: #aaa; font-size: 0.85rem;
        }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e0e0e0; }
        .divider::before { margin-right: 10px; }
        .divider::after  { margin-left: 10px; }
        .g-recaptcha { margin: 12px 0; transform: scale(0.9); transform-origin: left; }
        .link-text { text-align: center; margin-top: 16px; font-size: 0.85rem; color: #666; }
        .link-text a { color: #4f46e5; text-decoration: none; font-weight: 600; }
        .forgot { text-align: right; font-size: 0.78rem; margin-top: -8px; margin-bottom: 10px; }
        .forgot a { color: #4f46e5; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your account</p>

    <?php if ($success): ?>
        <div class="success-box">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $e): ?>
            <p>⚠ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="token" value="<?= csrf_token(); ?>">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Your password" required>
        </div>
        <p class="forgot"><a href="forgot-password.php">Forgot password?</a></p>

        <div class="g-recaptcha" data-sitekey="6LdgG3MsAAAAAAs-VBDJjLY-wZVIjS2gICxgSG2O"></div>

        <button type="submit" name="submit" class="btn">Login</button>
    </form>

    <div class="divider">OR</div>

    <a href="login.php" class="google-btn">
        <i class="fa fa-google" style="color:#4285F4"></i> Sign in with Google
    </a>

    <p class="link-text">Don't have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
