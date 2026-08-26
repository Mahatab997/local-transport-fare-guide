<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

try {
    ensure_default_admin_exists();
} catch (Throwable $e) {
    error_log('Database init failed: ' . $e->getMessage());
    $_SESSION['db_error'] = 'Database is not available yet. Start MySQL in XAMPP and refresh the page.';
}
