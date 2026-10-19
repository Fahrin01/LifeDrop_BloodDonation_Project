<?php
require_once __DIR__ . '/includes/auth.php';

$bloodGroup = clean($_GET['blood_group'] ?? '');
$district = clean($_GET['district'] ?? '');

$sql = "SELECT br.*, u.full_name AS seeker_name FROM blood_requests br
        JOIN users u ON u.id = br.seeker_id
        WHERE br.request_type = 'emergency' AND br.status IN ('pending','searching','donor_found')";
$params = [];
if ($bloodGroup !== '' && in_array($bloodGroup, blood_groups(), true)) {
    $sql .= ' AND br.blood_group = :bg';
    $params[':bg'] = $bloodGroup;
}
if ($district !== '') {
    $sql .= ' AND br.district = :district';
    $params[':district'] = $district;
}
$sql .= ' ORDER BY br.required_date ASC, br.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Emergency Blood Requests — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">🔴 Live</span>
        <h2>Emergency Blood Requests</h2>
        <p>These requests need urgent attention. If you match the blood group and can help, please respond.</p>
    </div>

    <form class="filters-bar" method="get" action="<?= e(SITE_URL) ?>/emergency-requests.php">
        <div class="filters-grid" style="grid-template-columns: 1fr 1fr auto;">
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
                <input type="text" name="district" value="<?= e($district) ?>" placeholder="e.g. Dhaka">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <?php if ($requests): ?>
    <div class="grid grid-3">
        <?php foreach ($requests as $req): ?>
            <div class="emergency-card" id="req-<?= e($req['id']) ?>">
                <div class="top-row">
                    <span class="badge badge-emergency"><i class="fa-solid fa-triangle-exclamation"></i> URGENT</span>
                    <span class="badge <?= status_badge_class($req['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $req['status']))) ?></span>
                </div>
                <h3><?= e($req['blood_group']) ?> Blood Required · <?= e($req['units_needed']) ?> Bag<?= $req['units_needed'] > 1 ? 's' : '' ?></h3>
                <p class="text-muted" style="font-size:0.85rem;margin:0;">Patient: <?= e($req['patient_name']) ?></p>
                <div class="meta-row">
                    <span><i class="fa-solid fa-hospital"></i> <?= e($req['hospital_name']) ?></span>
                </div>
                <div class="meta-row">
                    <span><i class="fa-solid fa-location-dot"></i> <?= e($req['area'] ? $req['area'] . ', ' : '') . e($req['district']) . ', ' . e($req['division']) ?></span>
                </div>
                <div class="meta-row">
                    <span><i class="fa-regular fa-clock"></i> Needed by <?= e(date('d M Y', strtotime($req['required_date']))) ?><?= $req['required_time'] ? ' at ' . e(date('h:i A', strtotime($req['required_time']))) : '' ?></span>
                </div>
                <div class="meta-row">
                    <span><i class="fa-regular fa-hourglass"></i> Posted <?= e(time_ago($req['created_at'])) ?></span>
                </div>
                <?php if (is_logged_in() && current_user_role() !== 'seeker'): ?>
                    <a href="<?= e(SITE_URL) ?>/donor/requests.php" class="btn btn-primary btn-sm">Respond to Request</a>
                <?php elseif (!is_logged_in()): ?>
                    <a href="<?= e(SITE_URL) ?>/login.php" class="btn btn-primary btn-sm">Log In to Respond</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <p>No active emergency requests match your filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
