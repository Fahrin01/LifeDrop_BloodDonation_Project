<?php
/**
 * includes/notifications.php
 * Renders the notification bell dropdown shown in the navbar for logged-in users.
 * Relies on functions already loaded via functions.php.
 */

function render_notification_bell($userId) {
    global $pdo;
    $unread = unread_notification_count($userId);

    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :u ORDER BY created_at DESC LIMIT 8');
    $stmt->execute([':u' => $userId]);
    $items = $stmt->fetchAll();
    ?>
    <div class="notif-wrapper">
        <button class="icon-btn" id="notifBellBtn" type="button" aria-label="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($unread > 0): ?>
                <span class="notif-count"><?= $unread > 9 ? '9+' : e($unread) ?></span>
            <?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-header">
                <span>Notifications</span>
                <?php if ($unread > 0): ?>
                <form action="<?= e(SITE_URL) ?>/includes/mark_notifications_read.php" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-btn">Mark all read</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="notif-dropdown-body">
                <?php if (!$items): ?>
                    <p class="notif-empty">No notifications yet.</p>
                <?php else: ?>
                    <?php foreach ($items as $n): ?>
                        <a class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= e(SITE_URL . ($n['link'] ?? '#')) ?>">
                            <div class="notif-item-title"><?= e($n['title']) ?></div>
                            <div class="notif-item-msg"><?= e($n['message']) ?></div>
                            <div class="notif-item-time"><?= e(time_ago($n['created_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
