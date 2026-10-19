<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);

$user = current_user();
$userId = $user['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM blood_requests WHERE seeker_id = :id');
$stmt->execute([':id' => $userId]);
$totalRequests = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = :id AND status IN ('pending','searching','donor_found')");
$stmt->execute([':id' => $userId]);
$activeRequests = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = :id AND status = 'fulfilled'");
$stmt->execute([':id' => $userId]);
$fulfilledRequests = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = :id AND request_type = 'emergency'");
$stmt->execute([':id' => $userId]);
$emergencyCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM blood_requests WHERE seeker_id = :id ORDER BY created_at DESC LIMIT 5');
$stmt->execute([':id' => $userId]);
$recentRequests = $stmt->fetchAll();

$pageTitle = 'Seeker Dashboard — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('dashboard'); ?>
    <div class="dash-main">
        <h1>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
        <p class="dash-welcome">Manage your blood requests and find donors quickly.</p>

        <div class="dash-stats">
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-list"></i></div><div><div class="num"><?= e($totalRequests) ?></div><div class="label">Total Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="num"><?= e($activeRequests) ?></div><div class="label">Active Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-check-double"></i></div><div><div class="num"><?= e($fulfilledRequests) ?></div><div class="label">Fulfilled</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="num"><?= e($emergencyCount) ?></div><div class="label">Emergency Requests</div></div></div>
        </div>

        <div class="card mb-2">
            <div class="panel-header">
                <h3>Recent Requests</h3>
                <a href="<?= e(SITE_URL) ?>/seeker/my-requests.php" class="link-btn">View All</a>
            </div>
            <?php if ($recentRequests): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Request</th><th>Blood Group</th><th>Hospital</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($recentRequests as $r): ?>
                        <tr>
                            <td><?= e($r['request_code']) ?></td>
                            <td><span class="badge badge-muted"><?= e($r['blood_group']) ?></span></td>
                            <td><?= e($r['hospital_name']) ?></td>
                            <td><?= $r['request_type'] === 'emergency' ? '<span class="badge badge-emergency">Emergency</span>' : '<span class="badge badge-info">Normal</span>' ?></td>
                            <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucwords(str_replace('_',' ',$r['status']))) ?></span></td>
                            <td><a href="<?= e(SITE_URL) ?>/seeker/matched-donors.php?request_id=<?= e($r['id']) ?>" class="link-btn">Matches</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">You haven't created any requests yet.</p>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="<?= e(SITE_URL) ?>/seeker/create-request.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Create New Request</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
