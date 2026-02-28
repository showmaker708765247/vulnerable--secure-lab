<?php
require_once 'controllerUserData.php';

// FIX: was using == false (truthy) with no exit — page rendered anyway
if (empty($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Reset Code — CW2 Secure</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card { background:#fff; border-radius:16px; padding:36px 32px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; }
        h2 { color:#1a1a2e; font-size:1.5rem; margin-bottom:6px; }
        .subtitle { color:#888; font-size:0.85rem; margin-bottom:24px; }
        .form-group { margin-bottom:16px; text-align:left; }
        label { display:block; font-size:0.82rem; font-weight:600; color:#444; margin-bottom:5px; }
        input[type="number"] {
            width:100%; padding:12px 16px; border:2px solid #e0e0e0; border-radius:8px;
            font-size:1.4rem; font-weight:700; text-align:center; letter-spacing:6px; outline:none; transition:border 0.2s;
        }
        input[type="number"]:focus { border-color:#4f46e5; }
        .btn { width:100%; padding:12px; background:#4f46e5; color:#fff; border:none; border-radius:8px; font-size:1rem; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .btn:hover { background:#4338ca; }
        .info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; margin-bottom:16px; color:#1d4ed8; font-size:0.85rem; text-align:left; }
        .errors { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 14px; margin-bottom:16px; text-align:left; }
        .errors p { color:#dc2626; font-size:0.85rem; }
        .back-link { display:block; margin-top:16px; font-size:0.83rem; color:#888; text-decoration:none; }
        .back-link:hover { color:#4f46e5; }
    </style>
</head>
<body>
<div class="card">
    <div style="font-size:2.5rem;margin-bottom:12px;">📧</div>
    <h2>Check Your Email</h2>
    <!-- FIX: was echoing $_SESSION directly — XSS risk -->
    <p class="subtitle">Enter the code sent to <strong><?= htmlspecialchars($_SESSION['email']) ?></strong></p>

    <?php if (!empty($_SESSION['info'])): ?>
        <div class="info-box">ℹ <?= htmlspecialchars($_SESSION['info']) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="reset-code.php" novalidate>
        <div class="form-group">
            <label for="otp">6-digit Reset Code</label>
            <input type="number" id="otp" name="otp" placeholder="000000"
                   min="111111" max="999999" required autofocus>
        </div>
        <button type="submit" name="check-reset-otp" class="btn">Verify Code</button>
    </form>

    <a href="forgot-password.php" class="back-link">← Request a new code</a>
</div>
</body>
</html>
