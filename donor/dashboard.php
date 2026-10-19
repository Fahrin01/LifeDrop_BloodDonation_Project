<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['donor']);

$user = current_user();
$userId = $user['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM donor_requests WHERE donor_id = :id');
$stmt->execute([':id' => $userId]);
$requestsReceived = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM donor_requests WHERE donor_id = :id AND status = 'accepted'");
$stmt->execute([':id' => $userId]);
$requestsAccepted = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM donor_requests WHERE donor_id = :id AND status = 'pending'");
$stmt->execute([':id' => $userId]);
$requestsPending = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM donation_history WHERE donor_id = :id AND status = "completed"');
$stmt->execute([':id' => $userId]);
$totalDonations = (int) $stmt->fetchColumn();

// Recent requests sent to this donor
$stmt = $pdo->prepare(
    "SELECT dr.*, br.blood_group, br.hospital_name, br.district, br.request_type, br.required_date, br.request_code
     FROM donor_requests dr JOIN blood_requests br ON br.id = dr.blood_request_id
     WHERE dr.donor_id = :id ORDER BY dr.created_at DESC LIMIT 5"
);
$stmt->execute([':id' => $userId]);
$recentRequests = $stmt->fetchAll();

// Donations over time (for chart) - monthly counts, last 6 months
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(donation_date, '%Y-%m') ym, COUNT(*) c FROM donation_history
     WHERE donor_id = :id AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym"
);
$stmt->execute([':id' => $userId]);
$chartRows = $stmt->fetchAll();

$pageTitle = 'Donor Dashboard — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_donor_sidebar('dashboard'); ?>
    <div class="dash-main">
        <h1>Welcome, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
        <p class="dash-welcome">
            Blood Group: <strong><?= e($user['blood_group'] ?? 'Not set') ?></strong> ·
            Status: <span class="badge <?= status_badge_class($user['availability']) ?>"><?= e(ucfirst($user['availability'])) ?></span>
        </p>

        <div class="dash-stats">
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-droplet"></i></div><div><div class="num"><?= e($totalDonations) ?></div><div class="label">Donations</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-inbox"></i></div><div><div class="num"><?= e($requestsReceived) ?></div><div class="label">Requests Received</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="num"><?= e($requestsPending) ?></div><div class="label">Pending Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-check-double"></i></div><div><div class="num"><?= e($requestsAccepted) ?></div><div class="label">Requests Accepted</div></div></div>
        </div>

        <div class="card mb-2">
            <div class="panel-header">
                <h3>Recent Blood Requests</h3>
                <a href="<?= e(SITE_URL) ?>/donor/requests.php" class="link-btn">View All</a>
            </div>
            <?php if ($recentRequests): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Request</th><th>Blood Group</th><th>Hospital</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentRequests as $r): ?>
                        <tr>
                            <td><?= e($r['request_code']) ?></td>
                            <td><span class="badge badge-muted"><?= e($r['blood_group']) ?></span></td>
                            <td><?= e($r['hospital_name']) ?></td>
                            <td><?= $r['request_type'] === 'emergency' ? '<span class="badge badge-emergency">Emergency</span>' : '<span class="badge badge-info">Normal</span>' ?></td>
                            <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">No blood requests received yet.</p>
            <?php endif; ?>
        </div>

        <?php if ($user['last_donation_date']): ?>
        <div class="card mb-2" style="border-left:4px solid var(--amber-600);">
            <h3><i class="fa-solid fa-bell"></i> Donation Reminder</h3>
            <p>Your last donation was on <strong><?= e(date('d F Y', strtotime($user['last_donation_date']))) ?></strong>.</p>
            <p class="text-muted" style="font-size:0.85rem;">Please consult an appropriate healthcare professional or blood donation center regarding your eligibility before donating again.</p>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3 class="mb-1">Donations Over Time</h3>
            <canvas id="donationsChart" height="90"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
const chartLabels = <?= json_encode(array_map(fn($r) => $r['ym'], $chartRows)) ?>;
const chartData = <?= json_encode(array_map(fn($r) => (int) $r['c'], $chartRows)) ?>;
new Chart(document.getElementById('donationsChart'), {
    type: 'line',
    data: {
        labels: chartLabels.length ? chartLabels : ['No data yet'],
        datasets: [{
            label: 'Donations',
            data: chartData.length ? chartData : [0],
            borderColor: '#d61f36',
            backgroundColor: 'rgba(214,31,54,0.1)',
            tension: 0.35,
            fill: true,
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
