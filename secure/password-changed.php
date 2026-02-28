<?php
require_once 'controllerUserData.php';

// FIX: was == false with no exit — page rendered even without a valid session
if (empty($_SESSION['info'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed — CW2 Secure</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card { background:#fff; border-radius:16px; padding:44px 36px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; }
        .icon { font-size:3.5rem; margin-bottom:16px; }
        h2 { color:#1a1a2e; font-size:1.5rem; margin-bottom:8px; }
        .info-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px; margin:20px 0; color:#16a34a; font-size:0.88rem; line-height:1.5; }
        .btn {
            display:inline-block; padding:12px 32px;
            background:#4f46e5; color:#fff; border:none; border-radius:8px;
            font-size:1rem; font-weight:600; cursor:pointer;
            text-decoration:none; transition:background 0.2s;
        }
        .btn:hover { background:#4338ca; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✅</div>
    <h2>Password Changed!</h2>

    <!-- FIX: was echoing $_SESSION['info'] unescaped — XSS risk -->
    <div class="info-box"><?= htmlspecialchars($_SESSION['info']) ?></div>

    <?php unset($_SESSION['info']); // clear after displaying ?>

    <!-- FIX: was a POST form with a login-now button — redirect handled in controller
         Simplified to a direct link so no form POST needed -->
    <a href="index.php" class="btn">Login Now</a>
</div>
</body>
</html>
