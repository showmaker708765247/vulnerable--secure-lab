<?php require_once 'controllerUserData.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — CW2 Secure</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card { background:#fff; border-radius:16px; padding:36px 32px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
        h2 { text-align:center; color:#1a1a2e; margin-bottom:6px; font-size:1.5rem; }
        .subtitle { text-align:center; color:#888; font-size:0.85rem; margin-bottom:24px; }
        .form-group { margin-bottom:16px; }
        label { display:block; font-size:0.82rem; font-weight:600; color:#444; margin-bottom:5px; }
        input[type="email"] { width:100%; padding:10px 14px; border:2px solid #e0e0e0; border-radius:8px; font-size:0.95rem; outline:none; transition:border 0.2s; }
        input[type="email"]:focus { border-color:#4f46e5; }
        .btn { width:100%; padding:12px; background:#4f46e5; color:#fff; border:none; border-radius:8px; font-size:1rem; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .btn:hover { background:#4338ca; }
        .errors { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 14px; margin-bottom:16px; }
        .errors p { color:#dc2626; font-size:0.85rem; }
        .back-link { display:block; text-align:center; margin-top:16px; font-size:0.83rem; color:#888; text-decoration:none; }
        .back-link:hover { color:#4f46e5; }
    </style>
</head>
<body>
<div class="card">
    <div style="text-align:center;font-size:2.5rem;margin-bottom:12px;">🔑</div>
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your email address and we'll send you a reset code</p>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="forgot-password.php" novalidate>
        <div class="form-group">
            <label for="email">Email Address</label>
            <!-- FIX: was echoing $email unescaped — XSS risk -->
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <button type="submit" name="check-email" class="btn">Send Reset Code</button>
    </form>

    <a href="index.php" class="back-link">← Back to login</a>
</div>
</body>
</html>
