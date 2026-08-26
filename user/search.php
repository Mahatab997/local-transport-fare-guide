<?php
$pageTitle = 'Search Transport';
require_once __DIR__ . '/../includes/header.php';
require_login();
$keyword = sanitize($_GET['q'] ?? '');
$results = [];

if ($keyword !== '') {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT r.id, r.name AS route_name, l_start.name AS start_location, l_end.name AS end_location, r.fare FROM routes r JOIN locations l_start ON r.start_location_id = l_start.id JOIN locations l_end ON r.end_location_id = l_end.id WHERE r.name LIKE :keyword OR l_start.name LIKE :keyword OR l_end.name LIKE :keyword LIMIT 20");
    $stmt->execute(['keyword' => "%$keyword%"]);    
    $results = $stmt->fetchAll();
}
?>
<section class="card">
    <h2>Search Local Transport</h2>
    <form method="get" action="<?= APP_URL ?>/user/search.php">
        <div class="form-group">
            <label for="q">Search routes, start city, or end city</label>
            <input id="q" name="q" type="search" value="<?= sanitize($keyword) ?>" placeholder="e.g. City Circle, East Side">
        </div>
        <input type="submit" value="Search">
    </form>
</section>
<?php if ($keyword !== ''): ?>
    <section class="card">
        <h3>Results for "<?= sanitize($keyword) ?>"</h3>
        <?php if (empty($results)): ?>
            <p>No matching transport options were found.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid #d8e2ef;">
                        <th style="padding:10px 8px;">Route</th>
                        <th style="padding:10px 8px;">From</th>
                        <th style="padding:10px 8px;">To</th>
                        <th style="padding:10px 8px;">Fare</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $route): ?>
                        <tr style="border-bottom:1px solid #f0f3f8;">
                            <td style="padding:10px 8px;"><?= sanitize($route['route_name']) ?></td>
                            <td style="padding:10px 8px;"><?= sanitize($route['start_location']) ?></td>
                            <td style="padding:10px 8px;"><?= sanitize($route['end_location']) ?></td>
                            <td style="padding:10px 8px;">&dollar;<?= number_format($route['fare'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php';
