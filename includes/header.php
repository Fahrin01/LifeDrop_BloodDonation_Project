<?php
/**
 * includes/header.php
 * Include this at the top of every page AFTER including auth.php.
 * Optional variables a page can set before including this file:
 *   $pageTitle       - string, shown in <title>
 *   $pageDescription - string, meta description
 */
if (!isset($pdo)) {
    require_once __DIR__ . '/auth.php';
}
$pageTitle = $pageTitle ?? SITE_NAME . ' — Give Blood. Save a Life.';
$pageDescription = $pageDescription ?? 'LifeDrop connects blood donors with people who need blood across Bangladesh.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><text y=%2226%22 font-size=%2226%22>🩸</text></svg>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/style.css">
<script>window.SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="site-main">
<?php
$flashMessages = flash_get_all();
if ($flashMessages):
?>
<div class="flash-container">
    <?php foreach ($flashMessages as $fm): ?>
        <div class="flash flash-<?= e($fm['type']) ?>">
            <i class="fa-solid <?= $fm['type'] === 'success' ? 'fa-circle-check' : ($fm['type'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
            <span><?= e($fm['message']) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
