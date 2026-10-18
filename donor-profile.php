<?php
require_once __DIR__ . '/includes/auth.php';

$donorId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT id, full_name, blood_group, division, district, area, availability,
                               is_verified, profile_photo, last_donation_date, total_donations, created_at
                        FROM users WHERE id = :id AND is_donor_profile = 1 AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $donorId]);
$donor = $stmt->fetch();

if (!$donor) {
    flash_set('danger', 'Donor profile not found.');
    redirect('find-donor.php');
}

$pageTitle = e($donor['full_name']) . ' — Donor Profile — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:720px;">
    <div class="card text-center">
        <span class="blood-chip" style="position:static;display:inline-flex;margin-bottom:14px;width:52px;height:52px;font-size:1.1rem;"><?= e($donor['blood_group']) ?></span>
        <?php if (!empty($donor['profile_photo'])): ?>
            <img class="donor-avatar" style="width:110px;height:110px;" src="<?= e(UPLOAD_URL . $donor['profile_photo']) ?>" alt="">
        <?php else: ?>
            <div class="donor-avatar-fallback" style="width:110px;height:110px;font-size:2.4rem;"><?= e(mb_substr($donor['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <h2 class="mt-1"><?= e($donor['full_name']) ?></h2>
        <div class="donor-meta" style="justify-content:center;margin-bottom:14px;">
            <?php if ($donor['is_verified']): ?><span class="badge badge-success"><i class="fa-solid fa-check"></i> Verified Donor</span><?php endif; ?>
            <span class="badge <?= status_badge_class($donor['availability']) ?>">
                <?= $donor['availability'] === 'available' ? '🟢 Available' : ($donor['availability'] === 'maybe' ? '🟡 Maybe Available' : '🔴 Not Available') ?>
            </span>
        </div>

        <div class="grid grid-3 mt-2" style="text-align:left;">
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-location-dot"></i></div><div><div class="num" style="font-size:0.95rem;"><?= e($donor['area'] ? $donor['area'] . ', ' : '') . e($donor['district']) ?></div><div class="label">Location</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-solid fa-droplet"></i></div><div><div class="num"><?= e($donor['total_donations']) ?></div><div class="label">Total Donations</div></div></div>
            <div class="dash-stat"><div class="icon"><i class="fa-regular fa-calendar"></i></div><div><div class="num" style="font-size:0.95rem;"><?= $donor['last_donation_date'] ? e(date('d M Y', strtotime($donor['last_donation_date']))) : 'No record' ?></div><div class="label">Last Donation</div></div></div>
        </div>

        <p class="text-muted mt-2" style="font-size:0.85rem;">
            For privacy, exact contact information and home address are shared only after a
            blood request is created and accepted.
        </p>

        <div class="flex gap-1 justify-between mt-2">
            <a href="<?= e(SITE_URL) ?>/find-donor.php" class="btn btn-light">Back to Search</a>
            <a href="<?= e(SITE_URL) ?>/seeker/create-request.php?donor_id=<?= e($donor['id']) ?>" class="btn btn-primary">Request Blood</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
