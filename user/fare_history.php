<?php
$pageTitle = 'Fare History';
require_once __DIR__ . '/../includes/header.php';
require_login();
$fareHistory = [];

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT fh.id, r.name AS route_name, fh.amount, fh.created_at FROM fare_history fh JOIN routes r ON fh.route_id = r.id WHERE fh.user_id = :user_id ORDER BY fh.created_at DESC LIMIT 50');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$fareHistory = $stmt->fetchAll();
?>
<section class="card">
    <h2>Fare History</h2>
    <?php if (empty($fareHistory)): ?>
        <p>You have no recorded fare history yet.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid #d8e2ef;">
                    <th style="padding:10px 8px;">Route</th>
                    <th style="padding:10px 8px;">Amount</th>
                    <th style="padding:10px 8px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fareHistory as $entry): ?>
                    <tr style="border-bottom:1px solid #f0f3f8;">
                        <td style="padding:10px 8px;"><?= sanitize($entry['route_name']) ?></td>
                        <td style="padding:10px 8px;">&dollar;<?= number_format($entry['amount'], 2) ?></td>
                        <td style="padding:10px 8px;"><?= date('M d, Y', strtotime($entry['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
