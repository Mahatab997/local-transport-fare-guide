<?php
require_once __DIR__ . '/config.php';

function db_connect() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('The PDO MySQL extension is not enabled. Enable it in php.ini and restart Apache.');
    }

    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new RuntimeException(
            'Database connection failed. Start MySQL in XAMPP, make sure the database exists, and verify your DB credentials. ' . $e->getMessage(),
            0,
            $e
        );
    }

    return $pdo;
}
