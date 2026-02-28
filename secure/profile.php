<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$errors   = [];
$success  = '';
$user_id  = $_SESSION['user_id'];

// Fetch current user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Handle profile update
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');

    if (empty($new_username))
        $errors[] = "Username cannot be empty.";
    elseif (strlen($new_username) < 3 || strlen($new_username) > 30)
        $errors[] = "Username must be 3–30 characters.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username))
        $errors[] = "Username can only contain letters, numbers, and underscores.";

    if (empty($new_email))
        $errors[] = "Email cannot be empty.";
    elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    if (empty($errors)) {
        // Check email not taken by another user
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($chk, "si", $new_email, $user_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $errors[] = "That email is already in use by another account.";
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "ssi", $new_username, $new_email, $user_id);
            if (mysqli_stmt_execute($upd)) {
                $_SESSION['username'] = $new_username;
                $success = "Profile updated successfully!";
                $user['username'] = $new_username;
                $user['email']    = $new_email;
            } else {
                $errors[] = "Update failed. Please try again.";
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_pw  = $_POST['current_password'] ?? '';
    $new_pw      = $_POST['new_password'] ?? '';
    $confirm_pw  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_pw, $user['password']))
        $errors[] = "Current password is incorrect.";

    if (strlen($new_pw) < 8 || !preg_match('/[A-Z]/', $new_pw) || !preg_match('/[0-9]/', $new_pw) || !preg_match('/[\W_]/', $new_pw))
        $errors[] = "New password must be 8+ chars with uppercase, number, and special character.";

    if ($new_pw !== $confirm_pw)
        $errors[] = "New passwords do not match.";

    if (empty($errors)) {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $hashed, $user_id);
        if (mysqli_stmt_execute($upd)) {
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Password change failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f8; }
        .page { max-width: 700px; margin: 32px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h2 { color: #1a1a2e; margin-bottom: 4px; }
        .section-label { font-size: 0.78rem; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 5px; }
        input {
            width: 100%; padding: 10px 14px;
            border: 2px solid #e0e0e0; border-radius: 8px;
            font-size: 0.95rem; transition: border 0.2s; outline: none;
        }
        input:focus { border-color: #4f46e5; }
        .hint { font-size: 0.75rem; color: #aaa; margin-top: 3px; }
        .btn { padding: 10px 24px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #4338ca; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; }
        .errors p { color: #dc2626; font-size: 0.85rem; margin-bottom: 3px; }
        .success-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; color: #16a34a; font-size: 0.85rem; }
        .avatar { width: 72px; height: 72px; background: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; font-weight: 700; margin-bottom: 12px; }
        .user-info h2 { margin-bottom: 2px; }
        .user-info p { color: #888; font-size: 0.9rem; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
        hr { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page">
    <?php if (!empty($errors)): ?>
    <div class="errors"><?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="success-box">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Profile info -->
    <div class="card">
        <div class="profile-header">
            <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div class="user-info">
                <h2><?= htmlspecialchars($user['username']) ?></h2>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <?php if ($user['google_id']): ?>
                    <p style="color:#4285F4; font-size:0.8rem; margin-top:4px;">🔗 Linked with Google</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-label">Update Profile Info</div>
        <form method="POST" novalidate>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" minlength="3" maxlength="30" required>
                <div class="hint">3–30 characters, letters/numbers/underscores only</div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <button type="submit" name="update_profile" class="btn">Save Changes</button>
        </form>
    </div>

    <!-- Change password -->
    <?php if (!$user['google_id']): // Only show for non-Google users ?>
    <div class="card">
        <div class="section-label">Change Password</div>
        <form method="POST" novalidate>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
                <div class="hint">8+ chars, uppercase, number, and special character</div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
        </form>
    </div>
    <?php endif; ?>
</div>
</body>
</html>