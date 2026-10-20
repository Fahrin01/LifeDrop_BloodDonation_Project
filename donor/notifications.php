<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['donor']);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :id')->execute([':id' => $user['id']]);
    redirect('donor/notifications.php');
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 50');
$stmt->execute([':id' => $user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_donor_sidebar('notifications'); ?>
    <div class="dash-main">
        <div class="panel-header">
            <h1 class="mb-0">Notifications</h1>
            <form method="post" action="<?= e(SITE_URL) ?>/donor/notifications.php">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-light btn-sm">Mark All Read</button>
            </form>
        </div>

        <?php if ($notifications): ?>
        <div class="card" style="padding:0;">
            <?php foreach ($notifications as $n): ?>
                <a href="<?= e(SITE_URL . ($n['link'] ?? '#')) ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="display:block;">
                    <div class="notif-item-title"><?= e($n['title']) ?></div>
                    <div class="notif-item-msg"><?= e($n['message']) ?></div>
                    <div class="notif-item-time"><?= e(time_ago($n['created_at'])) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-bell-slash"></i><p>You have no notifications yet.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
