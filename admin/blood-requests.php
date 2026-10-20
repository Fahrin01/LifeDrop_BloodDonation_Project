<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $pdo->prepare('DELETE FROM blood_requests WHERE id = :id')->execute([':id' => $requestId]);
        flash_set('success', 'Request removed.');
    } elseif (in_array($action, ['pending', 'searching', 'donor_found', 'fulfilled', 'cancelled', 'expired'], true)) {
        $pdo->prepare('UPDATE blood_requests SET status = :s WHERE id = :id')->execute([':s' => $action, ':id' => $requestId]);
        flash_set('success', 'Request status updated.');
    }
    redirect('admin/blood-requests.php');
}

$bloodGroup = clean($_GET['blood_group'] ?? '');
$district = clean($_GET['district'] ?? '');
$emergencyOnly = isset($_GET['emergency']);
$statusFilter = clean($_GET['status'] ?? '');

$sql = "SELECT br.*, u.full_name AS seeker_name FROM blood_requests br JOIN users u ON u.id = br.seeker_id WHERE 1=1";
$params = [];
if ($bloodGroup !== '' && in_array($bloodGroup, blood_groups(), true)) { $sql .= ' AND br.blood_group = :bg'; $params[':bg'] = $bloodGroup; }
if ($district !== '') { $sql .= ' AND br.district LIKE :district'; $params[':district'] = "%$district%"; }
if ($emergencyOnly) { $sql .= " AND br.request_type = 'emergency'"; }
if (in_array($statusFilter, ['pending','searching','donor_found','fulfilled','cancelled','expired'], true)) {
    $sql .= ' AND br.status = :status'; $params[':status'] = $statusFilter;
}
$sql .= ' ORDER BY br.created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Manage Blood Requests — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('blood-requests'); ?>
    <div class="dash-main">
        <h1>Manage Blood Requests</h1>
        <p class="dash-welcome"><?= count($requests) ?> request(s) shown</p>

        <form class="filters-bar" method="get" action="<?= e(SITE_URL) ?>/admin/blood-requests.php">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group">
                        <option value="">Any</option>
                        <?php foreach (blood_groups() as $bg): ?>
                            <option value="<?= e($bg) ?>" <?= $bloodGroup === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="district" value="<?= e($district) ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Any</option>
                        <?php foreach (['pending','searching','donor_found','fulfilled','cancelled','expired'] as $s): ?>
                            <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$s))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="emergency" value="1" <?= $emergencyOnly ? 'checked' : '' ?>> Emergency only
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Seeker</th><th>Blood Group</th><th>Hospital</th><th>District</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= e($r['request_code']) ?></td>
                        <td><?= e($r['seeker_name']) ?></td>
                        <td><span class="badge badge-muted"><?= e($r['blood_group']) ?></span></td>
                        <td><?= e($r['hospital_name']) ?></td>
                        <td><?= e($r['district']) ?></td>
                        <td><?= $r['request_type'] === 'emergency' ? '<span class="badge badge-emergency">Emergency</span>' : '<span class="badge badge-info">Normal</span>' ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?><input type="hidden" name="request_id" value="<?= e($r['id']) ?>">
                                <select name="action" onchange="this.form.submit()" class="badge <?= status_badge_class($r['status']) ?>" style="border:none;padding:4px 8px;">
                                    <?php foreach (['pending','searching','donor_found','fulfilled','cancelled','expired'] as $s): ?>
                                        <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$s))) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="post" style="display:inline;" data-confirm="Remove this request permanently?">
                                <?= csrf_field() ?><input type="hidden" name="request_id" value="<?= e($r['id']) ?>"><input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$requests): ?>
                    <tr><td colspan="8" class="text-center text-muted">No requests found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
