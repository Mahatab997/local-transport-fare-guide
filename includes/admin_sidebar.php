<?php
// Shared admin sidebar include
// Expects $currentUser to be available (set in header.php)
$adminName = $currentUser['name'] ?? ($currentUser['username'] ?? 'Admin');
?>
<!-- ====== SIDEBAR ====== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo"><i class="fas fa-bus"></i> LocalFare</div>
        <div class="sub">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/dashboard.php') !== false) ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="<?= APP_URL ?>/admin/routes/index.php"><i class="fas fa-road"></i> Add Routes</a>
        
        <div class="nav-label" style="margin-top:16px;">System</div>
        <a href="<?= APP_URL ?>/admin/profile/index.php" class="nav-link"><i class="fas fa-user-cog"></i> My Profile</a>
        <a href="<?= APP_URL ?>/admin/reports/index.php"><i class="fas fa-file-alt"></i> Reports</a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
            <div class="admin-details">
                <div class="name"><?= htmlspecialchars($adminName) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <form method="post" action="<?= APP_URL ?>/auth/logout.php" style="margin:0;">
            <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </form>
    </div>
</aside>
