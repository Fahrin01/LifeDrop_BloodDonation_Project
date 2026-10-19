<?php
/**
 * includes/messaging.php
 * Simple donor <-> seeker messaging. Two users may only message each other
 * after a donor has accepted a specific blood request connecting them.
 */

/** All contacts (with basic info) that $userId is allowed to message. */
function get_message_contacts($userId) {
    global $pdo;
    // Case 1: current user is the seeker on requests with an accepted donor
    // Case 2: current user is the donor who accepted a request
    $stmt = $pdo->prepare(
        "SELECT DISTINCT
            CASE WHEN br.seeker_id = :uid1 THEN dr.donor_id ELSE br.seeker_id END AS other_id,
            u.full_name AS other_name, u.profile_photo AS other_photo,
            br.id AS blood_request_id, br.request_code, br.blood_group, br.hospital_name
         FROM donor_requests dr
         JOIN blood_requests br ON br.id = dr.blood_request_id
         JOIN users u ON u.id = (CASE WHEN br.seeker_id = :uid2 THEN dr.donor_id ELSE br.seeker_id END)
         WHERE dr.status = 'accepted' AND (br.seeker_id = :uid3 OR dr.donor_id = :uid4)
         ORDER BY br.id DESC"
    );
    $stmt->execute([':uid1' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId]);
    return $stmt->fetchAll();
}

/** Whether $userId and $otherId are allowed to message each other. */
function can_message($userId, $otherId) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT dr.id FROM donor_requests dr
         JOIN blood_requests br ON br.id = dr.blood_request_id
         WHERE dr.status = 'accepted'
           AND ((br.seeker_id = :a1 AND dr.donor_id = :b1) OR (br.seeker_id = :b2 AND dr.donor_id = :a2))
         LIMIT 1"
    );
    $stmt->execute([':a1' => $userId, ':b1' => $otherId, ':b2' => $userId, ':a2' => $otherId]);
    return (bool) $stmt->fetch();
}

/** Fetch the message thread between two users, oldest first. */
function get_message_thread($userId, $otherId, $limit = 200) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM messages
         WHERE (sender_id = :a1 AND receiver_id = :b1) OR (sender_id = :b2 AND receiver_id = :a2)
         ORDER BY created_at ASC LIMIT :lim"
    );
    $stmt->bindValue(':a1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':b1', $otherId, PDO::PARAM_INT);
    $stmt->bindValue(':b2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':a2', $otherId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mark_thread_read($userId, $otherId) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = :me AND sender_id = :other');
    $stmt->execute([':me' => $userId, ':other' => $otherId]);
}

function send_message($senderId, $receiverId, $text, $bloodRequestId = null) {
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO messages (blood_request_id, sender_id, receiver_id, message) VALUES (:br, :s, :r, :m)'
    );
    $stmt->execute([':br' => $bloodRequestId, ':s' => $senderId, ':r' => $receiverId, ':m' => $text]);
}

/** Renders chat bubbles for a thread (used by both the page and the AJAX poll endpoint). */
function render_chat_bubbles($thread, $userId) {
    foreach ($thread as $m) {
        $cls = $m['sender_id'] == $userId ? 'sent' : 'received';
        echo '<div class="chat-bubble ' . $cls . '">' . nl2br(e($m['message'])) . '<div style="font-size:0.68rem;opacity:0.7;margin-top:4px;">' . e(date('h:i A', strtotime($m['created_at']))) . '</div></div>';
    }
}
