<aside class="card" style="padding:18px; margin-bottom:18px;">
    <h3>Navigation</h3>
    <nav style="display:flex; flex-direction:column; gap:10px;">
        <?php if (is_admin()): ?>
            <a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a>
            <a href="<?= APP_URL ?>/admin/reports/index.php">Reports</a>
            <a href="<?= APP_URL ?>/admin/settings/index.php">Settings</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/user/dashboard.php">Dashboard</a>
            <a href="<?= APP_URL ?>/user/search.php">Search Transport</a>
            <a href="<?= APP_URL ?>/user/favorite.php">Favorites</a>
            <a href="<?= APP_URL ?>/user/fare_history.php">Fare History</a>
            <a href="<?= APP_URL ?>/user/review.php">Reviews</a>
            <a href="<?= APP_URL ?>/user/profile.php">Profile</a>
        <?php endif; ?>
    </nav>
</aside>
