<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['username'])) {
    header("Location: welcome.php");
    exit;
}

$errors = [];
$username = $email = '';

function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 8)
        $errors[] = "At least 8 characters long";
    if (!preg_match('/[A-Z]/', $password))
        $errors[] = "At least one uppercase letter (A-Z)";
    if (!preg_match('/[a-z]/', $password))
        $errors[] = "At least one lowercase letter (a-z)";
    if (!preg_match('/[0-9]/', $password))
        $errors[] = "At least one number (0-9)";
    if (!preg_match('/[\W_]/', $password))
        $errors[] = "At least one special character (!@#\$%^&*)";
    return $errors;
}

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // Username validation
    if (empty($username))
        $errors[] = "Username is required.";
    elseif (strlen($username) < 3 || strlen($username) > 30)
        $errors[] = "Username must be 3–30 characters.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        $errors[] = "Username can only contain letters, numbers, and underscores.";

    // Email validation
    if (empty($email))
        $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    // Password strength validation
    $pwErrors = validatePassword($password);
    if (!empty($pwErrors)) {
        $errors[] = "Password must have: " . implode(', ', $pwErrors) . ".";
    }

    // Confirm password
    if ($password !== $cpassword)
        $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // Check email not already taken — prepared statement
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "That email is already registered. <a href='index.php'>Login instead?</a>";
        } else {
            // Hash password with bcrypt
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "sss", $username, $email, $hashed);

            if (mysqli_stmt_execute($stmt2)) {
                $_SESSION['success'] = "Account created successfully! Please log in.";
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { text-align: center; color: #1a1a2e; margin-bottom: 6px; font-size: 1.6rem; }
        .subtitle { text-align: center; color: #888; font-size: 0.85rem; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 5px; }
        input {
            width: 100%; padding: 10px 14px;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 0.95rem; transition: border 0.2s;
            outline: none;
        }
        input:focus { border-color: #4f46e5; }
        input.error-input { border-color: #dc2626; }
        input.valid-input { border-color: #16a34a; }

        /* Password strength meter */
        .strength-meter { margin-top: 6px; }
        .strength-bar { height: 4px; border-radius: 4px; background: #e0e0e0; margin-bottom: 4px; }
        .strength-fill { height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s, background 0.3s; }
        .strength-label { font-size: 0.75rem; color: #888; }

        /* Password requirements checklist */
        .pw-rules { margin-top: 8px; display: none; }
        .pw-rules.show { display: block; }
        .pw-rule { font-size: 0.78rem; color: #999; display: flex; align-items: center; gap: 6px; margin-bottom: 3px; }
        .pw-rule.met { color: #16a34a; }
        .pw-rule .icon { font-size: 0.7rem; }

        .errors {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 8px; padding: 12px 14px; margin-bottom: 18px;
        }
        .errors p { color: #dc2626; font-size: 0.85rem; margin-bottom: 3px; }

        .btn {
            width: 100%; padding: 12px;
            background: #4f46e5; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            margin-top: 8px;
        }
        .btn:hover { background: #4338ca; }
        .link-text { text-align: center; margin-top: 16px; font-size: 0.85rem; color: #666; }
        .link-text a { color: #4f46e5; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h2>Create Account</h2>
    <p class="subtitle">Fill in the details below to register</p>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $e): ?>
            <p>⚠ <?= $e ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   placeholder="3–30 chars, letters/numbers/_"
                   value="<?= htmlspecialchars($username) ?>"
                   minlength="3" maxlength="30" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Create a strong password" required
                   oninput="checkStrength(this.value)">
            <div class="strength-meter">
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <span class="strength-label" id="strengthLabel"></span>
            </div>
            <div class="pw-rules" id="pwRules">
                <div class="pw-rule" id="rule-len"><span class="icon">○</span> At least 8 characters</div>
                <div class="pw-rule" id="rule-upper"><span class="icon">○</span> One uppercase letter (A-Z)</div>
                <div class="pw-rule" id="rule-lower"><span class="icon">○</span> One lowercase letter (a-z)</div>
                <div class="pw-rule" id="rule-num"><span class="icon">○</span> One number (0-9)</div>
                <div class="pw-rule" id="rule-special"><span class="icon">○</span> One special character (!@#$%)</div>
            </div>
        </div>
        <div class="form-group">
            <label for="cpassword">Confirm Password</label>
            <input type="password" id="cpassword" name="cpassword"
                   placeholder="Repeat your password" required
                   oninput="checkMatch()">
            <div style="font-size:0.78rem; margin-top:4px;" id="matchMsg"></div>
        </div>
        <button type="submit" name="submit" class="btn">Create Account</button>
    </form>
    <p class="link-text">Already have an account? <a href="index.php">Login here</a></p>
</div>

<script>
function checkStrength(pw) {
    document.getElementById('pwRules').classList.add('show');
    const rules = {
        'rule-len':     pw.length >= 8,
        'rule-upper':   /[A-Z]/.test(pw),
        'rule-lower':   /[a-z]/.test(pw),
        'rule-num':     /[0-9]/.test(pw),
        'rule-special': /[\W_]/.test(pw),
    };
    let score = 0;
    for (const [id, met] of Object.entries(rules)) {
        const el = document.getElementById(id);
        if (met) { el.classList.add('met'); el.querySelector('.icon').textContent = '✓'; score++; }
        else      { el.classList.remove('met'); el.querySelector('.icon').textContent = '○'; }
    }
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    const colors = ['#dc2626','#f97316','#eab308','#84cc16','#16a34a'];
    const labels = ['Very Weak','Weak','Fair','Strong','Very Strong'];
    fill.style.width  = (score * 20) + '%';
    fill.style.background = colors[score - 1] || '#e0e0e0';
    label.textContent = score > 0 ? labels[score - 1] : '';
}

function checkMatch() {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('cpassword').value;
    const msg = document.getElementById('matchMsg');
    if (!cpw) { msg.textContent = ''; return; }
    if (pw === cpw) { msg.textContent = '✓ Passwords match'; msg.style.color = '#16a34a'; }
    else            { msg.textContent = '✗ Passwords do not match'; msg.style.color = '#dc2626'; }
}
</script>
</body>
</html>