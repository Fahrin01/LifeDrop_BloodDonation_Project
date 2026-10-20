<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);

$user = current_user();
$requestId = (int) ($_GET['request_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM blood_requests WHERE id = :id AND seeker_id = :seeker LIMIT 1');
$stmt->execute([':id' => $requestId, ':seeker' => $user['id']]);
$request = $stmt->fetch();

if (!$request) {
    // No specific request selected — show a picker of the seeker's requests instead.
    $stmt = $pdo->prepare('SELECT * FROM blood_requests WHERE seeker_id = :id ORDER BY created_at DESC');
    $stmt->execute([':id' => $user['id']]);
    $allRequests = $stmt->fetchAll();
}

$matches = [];
if ($request) {
    $stmt = $pdo->prepare(
        "SELECT dr.*, u.full_name, u.blood_group, u.division, u.district, u.area, u.availability,
                u.is_verified, u.profile_photo, u.last_donation_date
         FROM donor_requests dr JOIN users u ON u.id = dr.donor_id
         WHERE dr.blood_request_id = :rid ORDER BY dr.match_score DESC"
    );
    $stmt->execute([':rid' => $requestId]);
    $matches = $stmt->fetchAll();
}

$pageTitle = 'Matched Donors — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('matched-donors'); ?>
    <div class="dash-main">
        <h1>Matched Donors</h1>

        <?php if (!$request): ?>
            <p class="dash-welcome">Select one of your requests to see recommended donors.</p>
            <?php if (!empty($allRequests)): ?>
            <div class="grid grid-3">
                <?php foreach ($allRequests as $r): ?>
                    <a href="?request_id=<?= e($r['id']) ?>" class="card">
                        <span class="badge badge-muted"><?= e($r['blood_group']) ?></span>
                        <h4 class="mt-1"><?= e($r['request_code']) ?></h4>
                        <p class="text-muted" style="font-size:0.85rem;"><?= e($r['hospital_name']) ?></p>
                        <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucwords(str_replace('_',' ',$r['status']))) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="empty-state"><i class="fa-solid fa-list"></i><p>You have no requests yet.</p></div>
            <?php endif; ?>
        <?php else: ?>
            <p class="dash-welcome">
                Showing recommended donors for <strong><?= e($request['request_code']) ?></strong>
                (<?= e($request['blood_group']) ?>, <?= e($request['hospital_name']) ?>).
                <a href="<?= e(SITE_URL) ?>/seeker/matched-donors.php" class="link-btn">Change request</a>
            </p>

            <?php if ($matches): ?>
            <div class="grid grid-3">
                <?php foreach ($matches as $m): ?>
                    <div class="donor-card">
                        <span class="blood-chip"><?= e($m['blood_group']) ?></span>
                        <?php if (!empty($m['profile_photo'])): ?>
                            <img class="donor-avatar" src="<?= e(UPLOAD_URL . $m['profile_photo']) ?>" alt="">
                        <?php else: ?>
                            <div class="donor-avatar-fallback"><?= e(mb_substr($m['full_name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div class="donor-name"><?= e($m['full_name']) ?></div>
                        <div class="match-score mb-1"><?= e($m['match_score']) ?>% Match</div>
                        <div class="donor-meta">
                            <?php if ($m['is_verified']): ?><span class="badge badge-success">Verified</span><?php endif; ?>
                            <span class="badge <?= status_badge_class($m['status']) ?>"><?= e(ucfirst($m['status'])) ?></span>
                        </div>
                        <div class="donor-location"><i class="fa-solid fa-location-dot"></i> <?= e($m['area'] ? $m['area'] . ', ' : '') . e($m['district']) ?></div>
                        <?php if ($m['status'] === 'accepted'): ?>
                            <a href="<?= e(SITE_URL) ?>/seeker/messages.php?with=<?= e($m['donor_id']) ?>" class="btn btn-primary btn-sm mt-1">Message Donor</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No matching donors were found for this request yet.</p></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
