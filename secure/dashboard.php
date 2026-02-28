<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$user_id  = $_SESSION['user_id'] ?? 0;

// Fetch user info
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch comment count for this user
$cc = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM comments WHERE email = ?");
mysqli_stmt_bind_param($cc, "s", $user['email']);
mysqli_stmt_execute($cc);
$comment_count = mysqli_fetch_assoc(mysqli_stmt_get_result($cc))['cnt'] ?? 0;

// Fetch recent comments
$rc = mysqli_prepare($conn, "SELECT * FROM comments WHERE email = ? ORDER BY id DESC LIMIT 5");
mysqli_stmt_bind_param($rc, "s", $user['email']);
mysqli_stmt_execute($rc);
$recent_comments = mysqli_stmt_get_result($rc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f8; }
        .page { max-width: 900px; margin: 32px auto; padding: 0 16px; }
        h1 { color: #1a1a2e; margin-bottom: 4px; font-size: 1.5rem; }
        .greeting { color: #888; font-size: 0.9rem; margin-bottom: 28px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .stat-icon { font-size: 2rem; margin-bottom: 8px; }
        .stat-num { font-size: 2rem; font-weight: 800; color: #1a1a2e; }
        .stat-label { font-size: 0.8rem; color: #888; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 20px; }
        .card h2 { color: #1a1a2e; font-size: 1rem; margin-bottom: 16px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .comment-item { padding: 12px 0; border-bottom: 1px solid #f4f4f4; }
        .comment-item:last-child { border-bottom: none; }
        .comment-item p { color: #444; font-size: 0.88rem; line-height: 1.5; }
        .comment-item .meta { font-size: 0.75rem; color: #aaa; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 700; }
        .badge-google { background: #e8f0fe; color: #1a73e8; }
        .badge-email  { background: #e8f5e9; color: #2e7d32; }
        .empty { color: #bbb; font-size: 0.9rem; text-align: center; padding: 20px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f4f4f4; font-size: 0.9rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #888; }
        .info-value { color: #1a1a2e; font-weight: 600; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
    <h1>Dashboard</h1>
    <p class="greeting">Hello, <?= htmlspecialchars($user['username']) ?>! Here's your account overview.</p>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-num"><?= $comment_count ?></div>
            <div class="stat-label">Comments Posted</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <div class="stat-num"><?= $user['google_id'] ? 'Google' : 'Email' ?></div>
            <div class="stat-label">Login Method</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-num">Active</div>
            <div class="stat-label">Account Status</div>
        </div>
    </div>

    <!-- Account info -->
    <div class="card">
        <h2>Account Information</h2>
        <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Login Method</span>
            <span class="info-value">
                <?php if ($user['google_id']): ?>
                    <span class="badge badge-google">🔵 Google OAuth</span>
                <?php else: ?>
                    <span class="badge badge-email">✉ Email & Password</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Account ID</span>
            <span class="info-value">#<?= $user['id'] ?></span>
        </div>
    </div>

    <!-- Recent comments -->
    <div class="card">
        <h2>Your Recent Comments</h2>
        <?php if (mysqli_num_rows($recent_comments) > 0): ?>
            <?php while ($c = mysqli_fetch_assoc($recent_comments)): ?>
            <div class="comment-item">
                <p><?= htmlspecialchars($c['comment']) ?></p>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">No comments yet. <a href="welcome.php">Post your first one!</a></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
