<?php
$pageTitle = 'Manage Fares';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}
?>
<section class="card">
    <h2>Manage Fares</h2>
    <p>View and adjust fare values for routes in the system. This placeholder area is set for future fare management tools.</p>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php';
