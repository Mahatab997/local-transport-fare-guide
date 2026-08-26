<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}
?>
<section class="card">
    <h2>Manage Users</h2>
    <p>Review and manage user accounts. Admin users can be added here with role assignment and account controls.</p>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php';
