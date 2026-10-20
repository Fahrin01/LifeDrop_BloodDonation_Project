<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM blood_requests WHERE id = :id AND seeker_id = :seeker LIMIT 1');
    $stmt->execute([':id' => $requestId, ':seeker' => $user['id']]);
    $request = $stmt->fetch();

    if (!$request) {
        flash_set('danger', 'Request not found.');
    } elseif ($action === 'cancel' && in_array($request['status'], ['pending', 'searching', 'donor_found'], true)) {
        $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = :id")->execute([':id' => $requestId]);
        flash_set('info', 'Request cancelled.');
    } elseif ($action === 'fulfilled' && $request['status'] !== 'fulfilled') {
        $pdo->prepare("UPDATE blood_requests SET status = 'fulfilled' WHERE id = :id")->execute([':id' => $requestId]);
        flash_set('success', 'Marked as fulfilled. Thank you for confirming!');
    }
    redirect('seeker/my-requests.php');
}

$filter = clean($_GET['status'] ?? '');
$sql = 'SELECT * FROM blood_requests WHERE seeker_id = :id';
$params = [':id' => $user['id']];
if (in_array($filter, ['pending', 'searching', 'donor_found', 'fulfilled', 'cancelled', 'expired'], true)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $filter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'My Requests — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('my-requests'); ?>
    <div class="dash-main">
        <div class="panel-header">
            <h1 class="mb-0">My Requests</h1>
            <a href="<?= e(SITE_URL) ?>/seeker/create-request.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-circle-plus"></i> New Request</a>
        </div>

        <div class="flex gap-1 mb-2" style="flex-wrap:wrap;">
            <a href="?status=" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-light' ?>">All</a>
            <a href="?status=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-light' ?>">Pending</a>
            <a href="?status=searching" class="btn btn-sm <?= $filter === 'searching' ? 'btn-primary' : 'btn-light' ?>">Searching</a>
            <a href="?status=donor_found" class="btn btn-sm <?= $filter === 'donor_found' ? 'btn-primary' : 'btn-light' ?>">Donor Found</a>
            <a href="?status=fulfilled" class="btn btn-sm <?= $filter === 'fulfilled' ? 'btn-primary' : 'btn-light' ?>">Fulfilled</a>
            <a href="?status=cancelled" class="btn btn-sm <?= $filter === 'cancelled' ? 'btn-primary' : 'btn-light' ?>">Cancelled</a>
        </div>

        <?php if ($requests): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Request</th><th>Blood Group</th><th>Hospital</th><th>Required</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= e($r['request_code']) ?></td>
                        <td><span class="badge badge-muted"><?= e($r['blood_group']) ?></span> x<?= e($r['units_needed']) ?></td>
                        <td><?= e($r['hospital_name']) ?></td>
                        <td><?= e(date('d M Y', strtotime($r['required_date']))) ?></td>
                        <td><?= $r['request_type'] === 'emergency' ? '<span class="badge badge-emergency">Emergency</span>' : '<span class="badge badge-info">Normal</span>' ?></td>
                        <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucwords(str_replace('_',' ',$r['status']))) ?></span></td>
                        <td class="table-actions">
                            <a href="<?= e(SITE_URL) ?>/seeker/matched-donors.php?request_id=<?= e($r['id']) ?>" class="btn btn-light btn-sm">Matches</a>
                            <?php if (in_array($r['status'], ['pending','searching','donor_found'], true)): ?>
                                <form method="post" action="<?= e(SITE_URL) ?>/seeker/my-requests.php" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= e($r['id']) ?>">
                                    <input type="hidden" name="action" value="fulfilled">
                                    <button type="submit" class="btn btn-success btn-sm">Mark Fulfilled</button>
                                </form>
                                <form method="post" action="<?= e(SITE_URL) ?>/seeker/my-requests.php" style="display:inline;" data-confirm="Cancel this request?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= e($r['id']) ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-list"></i><p>No requests found for this filter.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
