<?php

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    die('We are unable to connect to the database right now. Please try again later.');
}
