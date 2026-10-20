<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/messaging.php';
require_login();

$otherId = (int) ($_GET['with'] ?? 0);
$userId = current_user_id();

if (!$otherId || !can_message($userId, $otherId)) {
    http_response_code(403);
    exit;
}

mark_thread_read($userId, $otherId);
$thread = get_message_thread($userId, $otherId);
render_chat_bubbles($thread, $userId);
