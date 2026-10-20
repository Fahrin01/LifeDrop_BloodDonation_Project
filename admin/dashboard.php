<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalDonors = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_donor_profile = 1')->fetchColumn();
$availableDonors = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_donor_profile = 1 AND availability = 'available'")->fetchColumn();
$activeRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status IN ('pending','searching','donor_found')")->fetchColumn();
$emergencyRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE request_type = 'emergency' AND status IN ('pending','searching','donor_found')")->fetchColumn();
$fulfilledRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'fulfilled'")->fetchColumn();
$totalHospitals = (int) $pdo->query('SELECT COUNT(*) FROM hospitals')->fetchColumn();
$pendingReports = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$pendingVerifications = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_donor_profile = 1 AND is_verified = 0")->fetchColumn();

// Chart 1: Blood group distribution among donors
$stmt = $pdo->query("SELECT blood_group, COUNT(*) c FROM users WHERE is_donor_profile = 1 AND blood_group IS NOT NULL GROUP BY blood_group");
$bgDist = $stmt->fetchAll();

// Chart 2: Monthly requests (last 6 months)
$stmt = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) c FROM blood_requests
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym"
);
$monthlyRequests = $stmt->fetchAll();

// Chart 3: Emergency vs Normal
$stmt = $pdo->query("SELECT request_type, COUNT(*) c FROM blood_requests GROUP BY request_type");
$typeDist = $stmt->fetchAll();

// Chart 4: Requests by district (top 8)
$stmt = $pdo->query("SELECT district, COUNT(*) c FROM blood_requests GROUP BY district ORDER BY c DESC LIMIT 8");
$districtDist = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('dashboard'); ?>
    <div class="dash-main">
        <h1>Admin Dashboard</h1>
        <p class="dash-welcome">Platform-wide overview and analytics.</p>

        <div class="dash-stats" style="grid-template-columns:repeat(4,1fr);">
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-users"></i></div><div><div class="num"><?= e($totalUsers) ?></div><div class="label">Total Users</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-droplet"></i></div><div><div class="num"><?= e($totalDonors) ?></div><div class="label">Total Donors</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-circle-check"></i></div><div><div class="num"><?= e($availableDonors) ?></div><div class="label">Available Donors</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-list"></i></div><div><div class="num"><?= e($activeRequests) ?></div><div class="label">Active Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="num"><?= e($emergencyRequests) ?></div><div class="label">Emergency Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-check-double"></i></div><div><div class="num"><?= e($fulfilledRequests) ?></div><div class="label">Fulfilled Requests</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-hospital"></i></div><div><div class="num"><?= e($totalHospitals) ?></div><div class="label">Registered Hospitals</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-flag"></i></div><div><div class="num"><?= e($pendingReports) ?></div><div class="label">Pending Reports</div></div></div>
        </div>

        <?php if ($pendingVerifications > 0): ?>
        <div class="card mb-2" style="border-left:4px solid var(--amber-600);">
            <div class="flex items-center justify-between">
                <div><i class="fa-solid fa-user-clock"></i> <strong><?= e($pendingVerifications) ?></strong> donor(s) awaiting verification.</div>
                <a href="<?= e(SITE_URL) ?>/admin/donors.php?filter=pending" class="btn btn-light btn-sm">Review Now</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-2">
            <div class="card">
                <h3 class="mb-1">Blood Group Distribution</h3>
                <canvas id="bgChart" height="220"></canvas>
            </div>
            <div class="card">
                <h3 class="mb-1">Requests Per Month</h3>
                <canvas id="monthlyChart" height="220"></canvas>
            </div>
            <div class="card">
                <h3 class="mb-1">Emergency vs Normal</h3>
                <canvas id="typeChart" height="220"></canvas>
            </div>
            <div class="card">
                <h3 class="mb-1">Requests by District</h3>
                <canvas id="districtChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
const bgLabels = <?= json_encode(array_map(fn($r) => $r['blood_group'], $bgDist)) ?>;
const bgData = <?= json_encode(array_map(fn($r) => (int) $r['c'], $bgDist)) ?>;
new Chart(document.getElementById('bgChart'), {
    type: 'doughnut',
    data: { labels: bgLabels, datasets: [{ data: bgData, backgroundColor: ['#d61f36','#e63950','#f2798c','#f7a8b5','#b5152a','#7a0e1c','#ffc2cb','#8a1f2d'] }] },
});

const monthLabels = <?= json_encode(array_map(fn($r) => $r['ym'], $monthlyRequests)) ?>;
const monthData = <?= json_encode(array_map(fn($r) => (int) $r['c'], $monthlyRequests)) ?>;
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: monthLabels.length ? monthLabels : ['No data'], datasets: [{ label: 'Requests', data: monthData.length ? monthData : [0], backgroundColor: '#d61f36' }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

const typeLabels = <?= json_encode(array_map(fn($r) => ucfirst($r['request_type']), $typeDist)) ?>;
const typeData = <?= json_encode(array_map(fn($r) => (int) $r['c'], $typeDist)) ?>;
new Chart(document.getElementById('typeChart'), {
    type: 'pie',
    data: { labels: typeLabels.length ? typeLabels : ['No data'], datasets: [{ data: typeData.length ? typeData : [1], backgroundColor: ['#d61f36', '#2563eb'] }] },
});

const distLabels = <?= json_encode(array_map(fn($r) => $r['district'], $districtDist)) ?>;
const distData = <?= json_encode(array_map(fn($r) => (int) $r['c'], $districtDist)) ?>;
new Chart(document.getElementById('districtChart'), {
    type: 'bar',
    data: { labels: distLabels.length ? distLabels : ['No data'], datasets: [{ label: 'Requests', data: distData.length ? distData : [0], backgroundColor: '#2563eb' }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
