<?php

// ---- Database configuration ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloodconnect_bd');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Site configuration ----
define('SITE_NAME', 'LifeDrop');
define('SITE_URL', 'http://localhost/bloodconnect-bd'); // change if project folder name differs

// ---- Uploads ----
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// ---- Error display (set to 0 in production) ----
define('APP_DEBUG', 1);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ---- Sessions ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Timezone ----
date_default_timezone_set('Asia/Dhaka');
