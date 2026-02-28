<?php
// navbar.php — include this at the top of every protected page
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-brand"> CW2 Secure</div>
    <div class="nav-links">
        <a href="welcome.php"  class="<?= $current === 'welcome.php'  ? 'active' : '' ?>"> Home</a>
        <a href="profile.php"  class="<?= $current === 'profile.php'  ? 'active' : '' ?>"> Profile</a>
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"> Dashboard</a>
        <a href="logout.php" class="nav-logout"> Logout</a>
    </div>
</nav>
<style>
.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #1a1a2e;
    padding: 14px 28px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.nav-brand {
    color: #e0e0ff;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: 1px;
}
.nav-links { display: flex; gap: 6px; align-items: center; }
.nav-links a {
    color: #aab;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.nav-links a:hover { background: #ffffff18; color: #fff; }
.nav-links a.active { background: #4f46e5; color: #fff; }
.nav-logout { background: #7f1d1d22 !important; color: #fca5a5 !important; }
.nav-logout:hover { background: #dc2626 !important; color: #fff !important; }
</style>