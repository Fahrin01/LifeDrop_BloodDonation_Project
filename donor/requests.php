<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['donor']);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $donorRequestId = (int) ($_POST['donor_request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT dr.*, br.seeker_id, br.request_code, br.blood_group, br.hospital_name
         FROM donor_requests dr JOIN blood_requests br ON br.id = dr.blood_request_id
         WHERE dr.id = :id AND dr.donor_id = :donor_id LIMIT 1'
    );
    $stmt->execute([':id' => $donorRequestId, ':donor_id' => $user['id']]);
    $dr = $stmt->fetch();

    if (!$dr) {
        flash_set('danger', 'Request not found.');
    } elseif ($dr['status'] !== 'pending') {
        flash_set('warning', 'You have already responded to this request.');
    } elseif (in_array($action, ['accepted', 'declined'], true)) {
        $update = $pdo->prepare('UPDATE donor_requests SET status = :s, responded_at = NOW() WHERE id = :id');
        $update->execute([':s' => $action, ':id' => $donorRequestId]);

        if ($action === 'accepted') {
            $pdo->prepare("UPDATE blood_requests SET status = 'donor_found' WHERE id = :id AND status != 'fulfilled'")
                ->execute([':id' => $dr['blood_request_id']]);
            create_notification(
                $dr['seeker_id'], 'request_accepted', 'Donor Found!',
                $user['full_name'] . ' has accepted your request ' . $dr['request_code'] . '.',
                '/seeker/my-requests.php'
            );
            flash_set('success', 'You accepted the request. The seeker has been notified.');
        } else {
            create_notification(
                $dr['seeker_id'], 'request_declined', 'Donor Declined',
                $user['full_name'] . ' is unable to fulfil request ' . $dr['request_code'] . '.',
                '/seeker/my-requests.php'
            );
            flash_set('info', 'You declined the request.');
        }
    }
    redirect('donor/requests.php');
}

$filter = clean($_GET['status'] ?? '');
$sql = "SELECT dr.*, br.request_code, br.blood_group, br.units_needed, br.hospital_name, br.district,
               br.area, br.request_type, br.required_date, br.required_time, br.patient_name,
               u.full_name AS seeker_name, u.phone AS seeker_phone
        FROM donor_requests dr
        JOIN blood_requests br ON br.id = dr.blood_request_id
        JOIN users u ON u.id = br.seeker_id
        WHERE dr.donor_id = :donor_id";
$params = [':donor_id' => $user['id']];
if (in_array($filter, ['pending', 'accepted', 'declined'], true)) {
    $sql .= ' AND dr.status = :status';
    $params[':status'] = $filter;
}
$sql .= ' ORDER BY dr.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Blood Requests — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_donor_sidebar('requests'); ?>
    <div class="dash-main">
        <div class="panel-header">
            <h1 class="mb-0">Blood Requests Received</h1>
        </div>
        <div class="flex gap-1 mb-2">
            <a href="?status=" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-light' ?>">All</a>
            <a href="?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-light' ?>">Pending</a>
            <a href="?status=accepted" class="btn btn-sm <?= $filter === 'accepted' ? 'btn-primary' : 'btn-light' ?>">Accepted</a>
            <a href="?status=declined" class="btn btn-sm <?= $filter === 'declined' ? 'btn-primary' : 'btn-light' ?>">Declined</a>
        </div>

        <?php if ($requests): ?>
            <?php foreach ($requests as $r): ?>
                <div class="card mb-1">
                    <div class="panel-header" style="margin-bottom:8px;">
                        <div>
                            <?php if ($r['request_type'] === 'emergency'): ?>
                                <span class="badge badge-emergency"><i class="fa-solid fa-triangle-exclamation"></i> Emergency</span>
                            <?php else: ?>
                                <span class="badge badge-info">Normal</span>
                            <?php endif; ?>
                            <strong style="margin-left:8px;"><?= e($r['blood_group']) ?> · <?= e($r['units_needed']) ?> bag(s)</strong>
                        </div>
                        <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                    </div>
                    <p style="margin:0 0 4px;"><i class="fa-solid fa-hospital"></i> <?= e($r['hospital_name']) ?> — <?= e($r['area'] ? $r['area'] . ', ' : '') . e($r['district']) ?></p>
                    <p style="margin:0 0 4px;" class="text-muted">Patient: <?= e($r['patient_name']) ?> · Needed by <?= e(date('d M Y', strtotime($r['required_date']))) ?></p>
                    <?php if ($r['status'] === 'accepted'): ?>
                        <p style="margin:0 0 4px;"><i class="fa-solid fa-user"></i> Seeker: <?= e($r['seeker_name']) ?> · <i class="fa-solid fa-phone"></i> <?= e($r['seeker_phone']) ?></p>
                    <?php else: ?>
                        <p style="margin:0 0 4px;" class="text-muted"><i class="fa-solid fa-user"></i> Seeker: <?= e($r['seeker_name']) ?> · <?= e(mask_phone($r['seeker_phone'])) ?></p>
                    <?php endif; ?>
                    <p class="text-muted" style="font-size:0.8rem;">Received <?= e(time_ago($r['created_at'])) ?></p>

                    <?php if ($r['status'] === 'pending'): ?>
                    <div class="flex gap-1 mt-1">
                        <form method="post" action="<?= e(SITE_URL) ?>/donor/requests.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="donor_request_id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="action" value="accepted">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Accept</button>
                        </form>
                        <form method="post" action="<?= e(SITE_URL) ?>/donor/requests.php" data-confirm="Are you sure you want to decline this request?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="donor_request_id" value="<?= e($r['id']) ?>">
                            <input type="hidden" name="action" value="declined">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i> Decline</button>
                        </form>
                    </div>
                    <?php elseif ($r['status'] === 'accepted'): ?>
                    <div class="mt-1">
                        <a href="<?= e(SITE_URL) ?>/donor/messages.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-comments"></i> Message Seeker</a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No requests found for this filter.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
