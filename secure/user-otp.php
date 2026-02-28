<?php
session_start();
require_once 'config.php';

// FIX: was requiring controllerUserData.php which doesn't exist in your project
// FIX: was using == false which is truthy-falsy (use empty() instead)
if (empty($_SESSION['otp_email'])) {
    header('Location: index.php');
    exit;
}

$errors  = [];
$success = $_SESSION['otp_info'] ?? '';
unset($_SESSION['otp_info']);

if (isset($_POST['check'])) {
    $entered = trim($_POST['otp'] ?? '');

    // Validate OTP format
    if (empty($entered) || !ctype_digit($entered)) {
        $errors[] = "Please enter a valid numeric code.";
    } else {
        $email = $_SESSION['otp_email'];

        // FIX: use prepared statement
        $stmt = mysqli_prepare($conn, "SELECT code, reset_expires FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$row || $row['code'] !== $entered) {
            $errors[] = "Invalid code. Please check and try again.";
        } elseif (!empty($row['reset_expires']) && strtotime($row['reset_expires']) < time()) {
            $errors[] = "This code has expired. Please request a new one.";
        } else {
            // OTP valid
            $_SESSION['otp_verified'] = true;
            // Clear OTP
            $clr = mysqli_prepare($conn, "UPDATE users SET code = NULL, reset_expires = NULL WHERE email = ?");
            mysqli_stmt_bind_param($clr, "s", $email);
            mysqli_stmt_execute($clr);
            header('Location: new-password.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Verification</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
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
            max-width: 380px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        h2 { color: #1a1a2e; margin-bottom: 6px; }
        .subtitle { color: #888; font-size: 0.85rem; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; text-align: left; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 5px; }
        input[type="number"] {
            width: 100%; padding: 12px 16px;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 1.4rem; font-weight: 700;
            text-align: center; letter-spacing: 8px;
            outline: none; transition: border 0.2s;
        }
        input[type="number"]:focus { border-color: #4f46e5; }
        input[type="submit"] {
            width: 100%; padding: 12px;
            background: #4f46e5; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        input[type="submit"]:hover { background: #4338ca; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; text-align: left; }
        .errors p { color: #dc2626; font-size: 0.85rem; }
        .success-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; color: #16a34a; font-size: 0.85rem; }
        .resend { margin-top: 16px; font-size: 0.82rem; color: #888; }
        .resend a { color: #4f46e5; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <div style="font-size:2.5rem;margin-bottom:12px;">📧</div>
    <h2>Enter Your Code</h2>
    <p class="subtitle">We sent a verification code to <strong><?= htmlspecialchars($_SESSION['otp_email']) ?></strong></p>

    <?php if ($success): ?>
        <div class="success-box">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="form-group">
            <label for="otp">Verification Code</label>
            <input type="number" name="otp" id="otp" placeholder="000000" min="0" max="999999" required autofocus>
        </div>
        <input type="submit" name="check" value="Verify Code">
    </form>
    <p class="resend">Didn't get it? <a href="forgot-password.php">Request a new code</a></p>
</div>
</body>
</html>