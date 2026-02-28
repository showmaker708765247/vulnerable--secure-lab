<?php
session_start();

// FIX: missing exit after redirect — page continued executing without it
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

require_once 'config.php';

$errors  = [];
$success = '';

if (isset($_POST['submit'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    // FIX: input validation was completely missing
    if (empty($name))
        $errors[] = "Name is required.";
    elseif (strlen($name) > 100)
        $errors[] = "Name must be under 100 characters.";

    if (empty($email))
        $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    if (empty($comment))
        $errors[] = "Comment cannot be empty.";
    elseif (strlen($comment) > 1000)
        $errors[] = "Comment must be under 1000 characters.";

    if (empty($errors)) {
        // FIX: was raw string interpolation (SQL injection). Now uses prepared statement.
        $stmt = mysqli_prepare($conn, "INSERT INTO comments (name, email, comment) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $comment);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Comment posted successfully!";
        } else {
            $errors[] = "Failed to post comment. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home — CW2 Secure</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f8; }
        .page { max-width: 700px; margin: 32px auto; padding: 0 16px; }
        h1 { color: #1a1a2e; margin-bottom: 4px; font-size: 1.4rem; }
        .greeting { color: #888; font-size: 0.9rem; margin-bottom: 28px; }
        .card { background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 24px; }
        .card h2 { color: #1a1a2e; font-size: 1rem; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 5px; }
        input, textarea {
            width: 100%; padding: 10px 14px;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 0.9rem; transition: border 0.2s; outline: none;
            font-family: inherit;
        }
        input:focus, textarea:focus { border-color: #4f46e5; }
        textarea { resize: vertical; min-height: 90px; }
        .hint { font-size: 0.73rem; color: #bbb; margin-top: 3px; }
        .btn { padding: 10px 24px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #4338ca; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
        .errors p { color: #dc2626; font-size: 0.85rem; margin-bottom: 3px; }
        .success-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; color: #16a34a; font-size: 0.85rem; }
        .comment-item { padding: 14px 0; border-bottom: 1px solid #f4f4f4; }
        .comment-item:last-child { border-bottom: none; }
        .comment-item h4 { color: #1a1a2e; font-size: 0.92rem; }
        .comment-item .email { color: #4f46e5; font-size: 0.8rem; margin: 2px 0 6px; }
        .comment-item p { color: #555; font-size: 0.88rem; line-height: 1.5; }
        .empty { color: #bbb; font-size: 0.9rem; text-align: center; padding: 20px; }
        @media (max-width: 520px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h1>
    <p class="greeting">Share a comment below.</p>

    <!-- Comment form -->
    <div class="card">
        <h2>Post a Comment</h2>

        <?php if (!empty($errors)): ?>
            <div class="errors"><?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-box">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" placeholder="Your name" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="you@example.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="comment">Comment</label>
                <textarea name="comment" id="comment" placeholder="Write your comment here..." maxlength="1000" required></textarea>
                <div class="hint">Max 1000 characters</div>
            </div>
            <button type="submit" name="submit" class="btn">Post Comment</button>
        </form>
    </div>

    <!-- Previous comments -->
    <div class="card">
        <h2>Comments</h2>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM comments ORDER BY id DESC");
        if ($result && mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
        ?>
        <div class="comment-item">
            <h4><?= htmlspecialchars($row['name']) ?></h4>
            <!-- FIX: email was echoed unescaped — XSS risk fixed -->
            <div class="email"><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></div>
            <p><?= htmlspecialchars($row['comment']) ?></p>
        </div>
        <?php endwhile; else: ?>
            <div class="empty">No comments yet. Be the first!</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>