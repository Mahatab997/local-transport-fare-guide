<?php
// Application configuration

define('APP_NAME', 'Local Transport Fair Guide');
define('APP_URL', 'http://localhost/LTFG');
define('APP_ENV', 'development');

define('DB_HOST', 'localhost');
define('DB_NAME', 'local_transport_fair_guide');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('ASSETS_URL', APP_URL . '/assets');

ini_set('display_errors', APP_ENV === 'development' ? 1 : 0);
error_reporting(E_ALL);
