<?php
$pageTitle = 'Manage Locations';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}
?>
<section class="card">
    <h2>Manage Locations</h2>
    <p>Manage service locations and stations used across transport routes. Expand this page with create/edit/delete forms as needed.</p>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php';
