<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('login_user')) {
    function login_user($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
    }
}

if (!function_exists('logout_user')) {
    function logout_user() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
