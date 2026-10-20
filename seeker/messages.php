<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);
require_once __DIR__ . '/../includes/messaging.php';

$user = current_user();
$contacts = get_message_contacts($user['id']);

$activeId = (int) ($_GET['with'] ?? ($contacts[0]['other_id'] ?? 0));
$isAllowed = $activeId && can_message($user['id'], $activeId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $toId = (int) ($_POST['to_id'] ?? 0);
    $text = clean($_POST['message'] ?? '');
    if ($toId && $text !== '' && can_message($user['id'], $toId)) {
        send_message($user['id'], $toId, $text);
        create_notification(
            $toId, 'new_message', 'New Message',
            $user['full_name'] . ' sent you a message.', '/donor/messages.php?with=' . $user['id']
        );
    }
    redirect('seeker/messages.php?with=' . $toId);
}

if ($isAllowed) {
    mark_thread_read($user['id'], $activeId);
    $thread = get_message_thread($user['id'], $activeId);
}

$activeContact = null;
foreach ($contacts as $c) {
    if ((int) $c['other_id'] === $activeId) { $activeContact = $c; break; }
}

$pageTitle = 'Messages — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('messages'); ?>
    <div class="dash-main">
        <h1>Messages</h1>
        <p class="dash-welcome">You can message a donor once they've accepted your blood request.</p>

        <?php if (!$contacts): ?>
            <div class="empty-state"><i class="fa-solid fa-comments"></i><p>No conversations yet. Once a donor accepts your request, you can chat here.</p></div>
        <?php else: ?>
        <div class="chat-wrap">
            <div class="chat-list">
                <?php foreach ($contacts as $c): ?>
                    <a href="?with=<?= e($c['other_id']) ?>" class="chat-list-item <?= (int) $c['other_id'] === $activeId ? 'active' : '' ?>">
                        <?php if (!empty($c['other_photo'])): ?>
                            <img class="avatar-sm" style="width:40px;height:40px;" src="<?= e(UPLOAD_URL . $c['other_photo']) ?>" alt="">
                        <?php else: ?>
                            <span class="avatar-sm avatar-fallback" style="width:40px;height:40px;"><?= e(mb_substr($c['other_name'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:0.9rem;"><?= e($c['other_name']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;"><?= e($c['request_code']) ?> · <?= e($c['blood_group']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="chat-panel">
                <?php if ($activeContact): ?>
                    <div class="panel-header" style="padding:14px 18px;border-bottom:1px solid var(--ink-100);margin:0;">
                        <div>
                            <strong><?= e($activeContact['other_name']) ?></strong>
                            <div class="text-muted" style="font-size:0.78rem;"><?= e($activeContact['hospital_name']) ?></div>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages" data-poll-url="<?= e(SITE_URL) ?>/includes/messages_poll.php?with=<?= e($activeId) ?>">
                        <?php render_chat_bubbles($thread, $user['id']); ?>
                    </div>
                    <form class="chat-input-row" method="post" action="<?= e(SITE_URL) ?>/seeker/messages.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="to_id" value="<?= e($activeId) ?>">
                        <input type="text" name="message" placeholder="Type a message..." required>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                <?php else: ?>
                    <div class="empty-state"><i class="fa-solid fa-comments"></i><p>Select a conversation to start messaging.</p></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
