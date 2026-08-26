<?php
$pageTitle = 'Favorites';
require_once __DIR__ . '/../includes/header.php';
require_login();
$favorites = [];

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT f.id, r.name AS route_name, l_start.name AS start_location, l_end.name AS end_location FROM favorites f JOIN routes r ON f.route_id = r.id JOIN locations l_start ON r.start_location_id = l_start.id JOIN locations l_end ON r.end_location_id = l_end.id WHERE f.user_id = :user_id ORDER BY f.created_at DESC');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$favorites = $stmt->fetchAll();
?>
<section class="card">
    <h2>Favorites</h2>
    <?php if (empty($favorites)): ?>
        <p>No favorite routes saved yet.</p>
    <?php else: ?>
        <ul style="padding-left:20px; color:#4a5b72;">
            <?php foreach ($favorites as $favorite): ?>
                <li style="margin-bottom:12px;"><strong><?= sanitize($favorite['route_name']) ?></strong> — <?= sanitize($favorite['start_location']) ?> to <?= sanitize($favorite['end_location']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
