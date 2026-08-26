<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('sanitize')) {
    function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ensure_default_admin_exists')) {
    function ensure_default_admin_exists() {
        $pdo = db_connect();
        $email = 'admin@gmai.com';
        $passwordHash = password_hash('admin@gmai.com', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role, created_at)
            VALUES (:name, :email, :password, 'admin', NOW())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                password = VALUES(password),
                role = 'admin',
                updated_at = NOW()"
        );

        $stmt->execute([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $passwordHash,
        ]);
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
        exit;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            redirect('auth/login.php');
        }
    }
}

if (!function_exists('get_current_user_record')) {
    function get_current_user_record() {
        if (!is_logged_in()) {
            return null;
        }
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    }
}
