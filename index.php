<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = SITE_NAME . ' — Give Blood. Save a Life.';
require_once __DIR__ . '/includes/header.php';

// ---- Platform statistics (real data from MySQL) ----
$totalDonors = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_donor_profile = 1")->fetchColumn();
$totalDonations = (int) $pdo->query("SELECT COUNT(*) FROM donation_history WHERE status = 'completed'")->fetchColumn();
$activeRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status IN ('pending','searching','donor_found')")->fetchColumn();
$emergencyRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE request_type = 'emergency' AND status IN ('pending','searching','donor_found')")->fetchColumn();
$fulfilledRequests = (int) $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'fulfilled'")->fetchColumn();

// ---- Recent emergency requests ----
$stmt = $pdo->query(
    "SELECT * FROM blood_requests
     WHERE request_type = 'emergency' AND status IN ('pending','searching','donor_found')
     ORDER BY created_at DESC LIMIT 3"
);
$recentEmergencies = $stmt->fetchAll();
?>

<section class="hero-illustrated">
    <div class="hero-card">
        <div class="hero-illustration-wrap">
            <div class="blob-1"></div>
            <div class="blob-2"></div>
            <img src="<?= e(SITE_URL) ?>/assets/images/hero-illustration.png" alt="Blood donation illustration">
        </div>
        <div class="hero-copy">
            <span class="eyebrow-pill"><i class="fa-solid fa-heart-pulse"></i> Bangladesh's blood donor network</span>
            <h1>Donate Your <span class="accent">Blood</span>, Save a Life</h1>
            <p class="subtitle">Connect with verified blood donors near you and get help exactly when it matters most — for routine needs and emergencies alike.</p>
            <div class="hero-actions">
                <a href="<?= e(SITE_URL) ?>/find-donor.php" class="btn btn-yellow"><i class="fa-solid fa-magnifying-glass"></i> Find Blood</a>
                <a href="<?= e(SITE_URL) ?>/register.php?type=donor" class="btn btn-primary"><i class="fa-solid fa-hand-holding-heart"></i> Become a Donor</a>
                <a href="<?= e(SITE_URL) ?>/seeker/create-request.php" class="btn btn-secondary"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Request</a>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="quick-search">
        <h3>Quick Blood Search</h3>
        <form action="<?= e(SITE_URL) ?>/find-donor.php" method="get" class="quick-search-grid">
            <div class="form-group">
                <label for="qs_bg">Blood Group</label>
                <select name="blood_group" id="qs_bg">
                    <option value="">Any</option>
                    <?php foreach (blood_groups() as $bg): ?>
                        <option value="<?= e($bg) ?>"><?= e($bg) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="qs_div">Division</label>
                <select name="division" id="qs_div" data-division data-target-district="qs_dist">
                    <option value="">Any</option>
                    <?php foreach (divisions() as $d): ?>
                        <option value="<?= e($d) ?>"><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="qs_dist">District</label>
                <select name="district" id="qs_dist" data-district data-target-area="qs_area_list">
                    <option value="">Any</option>
                </select>
            </div>
            <div class="form-group">
                <label for="qs_area">Area</label>
                <input type="text" name="area" id="qs_area" list="qs_area_list" placeholder="Any" autocomplete="off">
                <datalist id="qs_area_list"></datalist>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Find Donor</button>
        </form>
    </div>
</div>

<section class="section section-tight">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow">Live Need</span>
            <h2>Emergency Blood Requests</h2>
            <p>These patients need blood urgently right now. A few minutes of your time could save a life.</p>
        </div>
        <?php if ($recentEmergencies): ?>
        <div class="grid grid-3">
            <?php foreach ($recentEmergencies as $req): ?>
                <div class="emergency-card">
                    <div class="top-row">
                        <span class="badge badge-emergency"><i class="fa-solid fa-triangle-exclamation"></i> URGENT</span>
                        <span class="blood-chip" style="position:static;width:34px;height:34px;font-size:0.75rem;"><?= e($req['blood_group']) ?></span>
                    </div>
                    <h3><?= e($req['blood_group']) ?> Blood Required · <?= e($req['units_needed']) ?> Bag<?= $req['units_needed'] > 1 ? 's' : '' ?></h3>
                    <div class="meta-row">
                        <span><i class="fa-solid fa-hospital"></i> <?= e($req['hospital_name']) ?></span>
                        <span><i class="fa-solid fa-location-dot"></i> <?= e($req['area'] ? $req['area'] . ', ' : '') . e($req['district']) ?></span>
                    </div>
                    <div class="meta-row">
                        <span><i class="fa-regular fa-clock"></i> Needed by <?= e(date('d M, h:i A', strtotime($req['required_date'] . ' ' . ($req['required_time'] ?? '00:00')))) ?></span>
                    </div>
                    <a href="<?= e(SITE_URL) ?>/emergency-requests.php#req-<?= e($req['id']) ?>" class="btn btn-secondary btn-sm">View Request</a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-2">
            <a href="<?= e(SITE_URL) ?>/emergency-requests.php" class="btn btn-light">View All Emergency Requests</a>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-circle-check"></i>
                <p>No active emergency requests right now. Great news!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow">Our Impact</span>
            <h2>Platform Statistics</h2>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><div class="num"><?= e($totalDonors) ?></div><div class="label">Registered Donors</div></div>
            <div class="stat-card"><div class="num"><?= e($totalDonations) ?></div><div class="label">Successful Donations</div></div>
            <div class="stat-card"><div class="num"><?= e($activeRequests) ?></div><div class="label">Active Requests</div></div>
            <div class="stat-card"><div class="num"><?= e($emergencyRequests) ?></div><div class="label">Emergency Requests</div></div>
            <div class="stat-card"><div class="num"><?= e($fulfilledRequests) ?></div><div class="label">Requests Fulfilled</div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow">Simple Process</span>
            <h2>How It Works</h2>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num"><i class="fa-solid fa-magnifying-glass"></i></div>
                <h4>1. Search Blood</h4>
                <p>Filter by blood group and location to find compatible donors near you.</p>
            </div>
            <div class="step-card">
                <div class="step-num"><i class="fa-solid fa-user-check"></i></div>
                <h4>2. Find a Donor</h4>
                <p>Browse verified donor profiles and their availability status.</p>
            </div>
            <div class="step-card">
                <div class="step-num"><i class="fa-solid fa-paper-plane"></i></div>
                <h4>3. Send Request</h4>
                <p>Create a request and notify matching donors instantly.</p>
            </div>
            <div class="step-card">
                <div class="step-num"><i class="fa-solid fa-heart"></i></div>
                <h4>4. Save a Life</h4>
                <p>Connect, coordinate, and donate — a simple act that means everything.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
