<?php
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    global $pdo;
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :u');
    $stmt->execute([':u' => current_user_id()]);
}

$back = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/index.php');
header('Location: ' . $back);
exit;
