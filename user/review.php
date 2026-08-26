<?php
$pageTitle = 'Review';
require_once __DIR__ . '/../includes/header.php';
require_login();
$error = '';
$success = '';
$routes = [];

$pdo = db_connect();
$stmt = $pdo->query('SELECT id, name FROM routes ORDER BY name ASC');
$routes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id = (int)($_POST['route_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comments = sanitize($_POST['comments'] ?? '');

    if ($route_id <= 0 || $rating < 1 || $rating > 5 || $comments === '') {
        $error = 'Please complete the review form correctly.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO reviews (user_id, route_id, rating, comments, created_at) VALUES (:user_id, :route_id, :rating, :comments, NOW())');
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'route_id' => $route_id,
            'rating' => $rating,
            'comments' => $comments,
        ]);
        $success = 'Thank you! Your review has been submitted.';
    }
}
?>
<section class="card">
    <h2>Submit a Review</h2>
    <?php if ($error): ?>
        <div class="alert"><?= sanitize($error) ?></div>
    <?php elseif ($success): ?>
        <div class="card" style="background:#e6f7ef; border-color:#c8e6d9; color:#196f3d; margin-bottom:18px; padding:16px;"><?= sanitize($success) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= APP_URL ?>/user/review.php">
        <div class="form-group">
            <label for="route_id">Route</label>
            <select id="route_id" name="route_id" required>
                <option value="">Select route</option>
                <?php foreach ($routes as $route): ?>
                    <option value="<?= $route['id'] ?>"><?= sanitize($route['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="rating">Rating</label>
            <select id="rating" name="rating" required>
                <option value="">Choose rating</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="comments">Comments</label>
            <textarea id="comments" name="comments" rows="4" required></textarea>
        </div>
        <input type="submit" value="Submit Review">
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php';
