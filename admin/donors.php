<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $donorId = (int) ($_POST['donor_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'verify') {
        $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = :id AND is_donor_profile = 1')->execute([':id' => $donorId]);
        create_notification($donorId, 'admin_verification', 'Account Verified', 'Your donor profile has been verified by an admin.', '/donor/profile.php');
        flash_set('success', 'Donor verified.');
    } elseif ($action === 'reject') {
        $pdo->prepare('UPDATE users SET is_verified = 0 WHERE id = :id AND is_donor_profile = 1')->execute([':id' => $donorId]);
        flash_set('info', 'Donor verification rejected/reset.');
    }
    redirect('admin/donors.php');
}

$filter = clean($_GET['filter'] ?? 'pending');
$sql = "SELECT * FROM users WHERE is_donor_profile = 1";
if ($filter === 'pending') {
    $sql .= ' AND is_verified = 0';
} elseif ($filter === 'verified') {
    $sql .= ' AND is_verified = 1';
}
$sql .= ' ORDER BY created_at DESC';
$donors = $pdo->query($sql)->fetchAll();

$pageTitle = 'Verify Donors — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('donors'); ?>
    <div class="dash-main">
        <h1>Donor Verification</h1>
        <p class="dash-welcome">Review donor profiles and mark them as verified. Verification does not imply medical fitness to donate.</p>

        <div class="flex gap-1 mb-2">
            <a href="?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-light' ?>">Pending Verification</a>
            <a href="?filter=verified" class="btn btn-sm <?= $filter === 'verified' ? 'btn-primary' : 'btn-light' ?>">Verified</a>
            <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-light' ?>">All Donors</a>
        </div>

        <?php if ($donors): ?>
        <div class="grid grid-3">
            <?php foreach ($donors as $d): ?>
                <div class="donor-card" style="text-align:left;">
                    <div class="flex items-center gap-1 mb-1">
                        <?php if (!empty($d['profile_photo'])): ?>
                            <img class="avatar-sm" style="width:48px;height:48px;" src="<?= e(UPLOAD_URL . $d['profile_photo']) ?>" alt="">
                        <?php else: ?>
                            <span class="avatar-sm avatar-fallback" style="width:48px;height:48px;"><?= e(mb_substr($d['full_name'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:700;"><?= e($d['full_name']) ?></div>
                            <span class="badge badge-muted"><?= e($d['blood_group']) ?></span>
                        </div>
                    </div>
                    <p class="text-muted" style="font-size:0.85rem;margin:0;"><i class="fa-solid fa-envelope"></i> <?= e($d['email']) ?></p>
                    <p class="text-muted" style="font-size:0.85rem;margin:0 0 8px;"><i class="fa-solid fa-phone"></i> <?= e($d['phone']) ?></p>
                    <p style="font-size:0.85rem;margin:0 0 10px;"><i class="fa-solid fa-location-dot"></i> <?= e($d['area'] ? $d['area'] . ', ' : '') . e($d['district']) ?></p>
                    <?php if ($d['is_verified']): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="donor_id" value="<?= e($d['id']) ?>"><input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-light btn-sm btn-block">Remove Verification</button>
                        </form>
                    <?php else: ?>
                        <div class="flex gap-1">
                            <form method="post" style="flex:1;"><?= csrf_field() ?><input type="hidden" name="donor_id" value="<?= e($d['id']) ?>"><input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn btn-success btn-sm btn-block">Verify</button>
                            </form>
                            <form method="post" style="flex:1;"><?= csrf_field() ?><input type="hidden" name="donor_id" value="<?= e($d['id']) ?>"><input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-sm btn-block">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-user-check"></i><p>No donors match this filter.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
