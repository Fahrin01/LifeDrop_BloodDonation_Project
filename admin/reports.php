<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $adminNotes = clean($_POST['admin_notes'] ?? '');

    if (in_array($status, ['pending', 'reviewed', 'resolved', 'rejected'], true)) {
        $stmt = $pdo->prepare('UPDATE reports SET status = :s, admin_notes = :n WHERE id = :id');
        $stmt->execute([':s' => $status, ':n' => $adminNotes ?: null, ':id' => $reportId]);
        flash_set('success', 'Report updated.');
    }
    redirect('admin/reports.php');
}

$statusFilter = clean($_GET['status'] ?? '');
$sql = "SELECT r.*, ru.full_name AS reporter_name, tu.full_name AS reported_name, br.request_code
        FROM reports r
        JOIN users ru ON ru.id = r.reporter_id
        LEFT JOIN users tu ON tu.id = r.reported_user_id
        LEFT JOIN blood_requests br ON br.id = r.reported_request_id
        WHERE 1=1";
$params = [];
if (in_array($statusFilter, ['pending', 'reviewed', 'resolved', 'rejected'], true)) {
    $sql .= ' AND r.status = :status';
    $params[':status'] = $statusFilter;
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$reasonLabels = [
    'fake_request' => 'Fake blood request', 'fake_donor' => 'Fake donor', 'spam' => 'Spam',
    'harassment' => 'Harassment', 'incorrect_info' => 'Incorrect information',
    'suspicious_activity' => 'Suspicious activity', 'other' => 'Other',
];

$pageTitle = 'Reports — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('reports'); ?>
    <div class="dash-main">
        <h1>Reports</h1>
        <div class="flex gap-1 mb-2" style="flex-wrap:wrap;">
            <a href="?status=" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-light' ?>">All</a>
            <a href="?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-light' ?>">Pending</a>
            <a href="?status=reviewed" class="btn btn-sm <?= $statusFilter === 'reviewed' ? 'btn-primary' : 'btn-light' ?>">Reviewed</a>
            <a href="?status=resolved" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-light' ?>">Resolved</a>
            <a href="?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-light' ?>">Rejected</a>
        </div>

        <?php if ($reports): ?>
            <?php foreach ($reports as $r): ?>
                <div class="card mb-1">
                    <div class="panel-header" style="margin-bottom:8px;">
                        <div>
                            <span class="badge badge-muted"><?= e($reasonLabels[$r['reason']] ?? $r['reason']) ?></span>
                            <strong style="margin-left:6px;">Reported by <?= e($r['reporter_name']) ?></strong>
                        </div>
                        <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                    </div>
                    <?php if ($r['reported_name']): ?><p style="margin:0 0 4px;">Reported user: <strong><?= e($r['reported_name']) ?></strong></p><?php endif; ?>
                    <?php if ($r['request_code']): ?><p style="margin:0 0 4px;">Reported request: <strong><?= e($r['request_code']) ?></strong></p><?php endif; ?>
                    <?php if ($r['details']): ?><p class="text-muted" style="margin:0 0 8px;"><?= e($r['details']) ?></p><?php endif; ?>
                    <p class="text-muted" style="font-size:0.8rem;">Submitted <?= e(time_ago($r['created_at'])) ?></p>

                    <form method="post" class="flex gap-1" style="align-items:flex-end;flex-wrap:wrap;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= e($r['id']) ?>">
                        <div class="form-group" style="flex:1;min-width:160px;">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach (['pending','reviewed','resolved','rejected'] as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:2;min-width:220px;">
                            <label>Admin Notes</label>
                            <input type="text" name="admin_notes" value="<?= e($r['admin_notes'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-flag"></i><p>No reports found.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
