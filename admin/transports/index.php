<?php
$pageTitle = 'Manage Transports';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}
?>
<section class="card">
    <h2>Manage Transports</h2>
    <p>Track transport services, vehicles and availability. This section is prepared for fleet and service management features.</p>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php';
