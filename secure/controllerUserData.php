<?php
// controllerUserData.php — Password reset controller
// Required by: forgot-password.php, reset-code.php, new-password.php, password-changed.php, user-otp.php, googlelogin.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php'; // loads $conn and $_ENV

$email    = '';
$username = '';
$errors   = [];

// -------------------------------------------------------
// STEP 1: Forgot password — user submits their email
// -------------------------------------------------------
if (isset($_POST['check-email'])) {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // FIX: was raw SQL string — SQL injection risk
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            // FIX: rand(999999, 111111) had max < min — always returned 999999
            // Corrected argument order: min first, max second
            $code = rand(111111, 999999);

            // FIX: was raw SQL — prepared statement
            $upd = mysqli_prepare($conn, "UPDATE users SET code = ? WHERE email = ?");
            mysqli_stmt_bind_param($upd, 'is', $code, $email);

            if (mysqli_stmt_execute($upd)) {
                // FIX: was using PHP mail() with old hardcoded sender address
                // Now uses PHPMailer via your configured sendmail / SMTP
                $subject = 'Password Reset Code';
                $message = 'Your password reset code is: ' . $code
                         . "\n\nThis code expires in 15 minutes. Do not share it with anyone.";
                // Use sendmail (configured in sendmail.ini with your Gmail app password)
                $headers = 'From: ' . $_ENV['OTP_EMAIL'];

                if (mail($email, $subject, $message, $headers)) {
                    $info = "We've sent a password reset code to " . htmlspecialchars($email);
                    $_SESSION['info']  = $info;
                    $_SESSION['email'] = $email;
                    header('Location: reset-code.php');
                    exit();
                } else {
                    $errors['otp-error'] = 'Failed to send the reset code. Please try again.';
                }
            } else {
                $errors['db-error'] = 'Something went wrong. Please try again.';
            }

        } else {
            // FIX: to prevent email enumeration, show same message regardless
            // of whether the email exists or not
            $_SESSION['info']  = "If that email is registered, a reset code has been sent.";
            $_SESSION['email'] = $email;
            header('Location: reset-code.php');
            exit();
        }
    }
}

// -------------------------------------------------------
// STEP 2: OTP verification — user submits the code
// -------------------------------------------------------
if (isset($_POST['check-reset-otp'])) {
    $_SESSION['info'] = '';

    // FIX: was using $con (undefined variable) — corrected to $conn
    $otp_code = trim($_POST['otp'] ?? '');

    if (!ctype_digit($otp_code)) {
        $errors['otp-error'] = 'Please enter a valid numeric code.';
    } else {
        // FIX: was raw SQL — prepared statement
        $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE code = ?");
        mysqli_stmt_bind_param($stmt, 'i', $otp_code);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($row) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['info']  = 'Please create a new password that you don\'t use on any other site.';
            header('Location: new-password.php');
            exit();
        } else {
            $errors['otp-error'] = 'Incorrect code. Please try again.';
        }
    }
}

// -------------------------------------------------------
// STEP 3: Change password — user submits new password
// -------------------------------------------------------
if (isset($_POST['change-password'])) {
    $_SESSION['info'] = '';

    $password  = $_POST['password']  ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    if ($password !== $cpassword) {
        $errors['password'] = 'Passwords do not match.';
    } elseif (strlen($password) < 8
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[\W_]/', $password)) {
        $errors['password'] = 'Password must be 8+ characters with uppercase, lowercase, number, and special character.';
    } else {
        $email   = $_SESSION['email'] ?? '';
        $encpass = password_hash($password, PASSWORD_BCRYPT);

        // FIX: was raw SQL string — prepared statement
        // Also clears the code column so the OTP can't be reused
        $upd = mysqli_prepare($conn, "UPDATE users SET code = NULL, password = ? WHERE email = ?");
        mysqli_stmt_bind_param($upd, 'ss', $encpass, $email);

        if (mysqli_stmt_execute($upd)) {
            $info = 'Your password has been changed. You can now log in with your new password.';
            $_SESSION['info'] = $info;
            // Clear the email session so the flow can't be replayed
            unset($_SESSION['email']);
            header('Location: password-changed.php');
            exit();
        } else {
            $errors['db-error'] = 'Failed to change your password. Please try again.';
        }
    }
}

// -------------------------------------------------------
// STEP 4: Login now button on password-changed page
// -------------------------------------------------------
if (isset($_POST['login-now'])) {
    header('Location: index.php');
    exit();
}
