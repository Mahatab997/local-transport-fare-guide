<?php
require_once __DIR__ . '/init.php';
$currentUser = is_logged_in() ? get_current_user_record() : null;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>
<div class="page-shell">
<header class="site-header" style="display:none;"></header>
<?php include __DIR__ . '/navbar.php'; ?>
<?php
if (is_logged_in() && is_admin()) {
    include __DIR__ . '/admin_sidebar.php';
}
?>
<main> 