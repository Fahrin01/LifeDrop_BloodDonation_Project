<?php
require_once __DIR__ . '/includes/auth.php';

$bloodGroup = clean($_GET['blood_group'] ?? '');
$division = clean($_GET['division'] ?? '');
$district = clean($_GET['district'] ?? '');
$area = clean($_GET['area'] ?? '');
$availability = clean($_GET['availability'] ?? '');
$verifiedOnly = isset($_GET['verified']) ? 1 : 0;

$sql = "SELECT id, full_name, blood_group, division, district, area, availability, is_verified,
               profile_photo, last_donation_date, total_donations
        FROM users WHERE is_donor_profile = 1 AND status = 'active'";
$params = [];

if ($bloodGroup !== '' && in_array($bloodGroup, blood_groups(), true)) {
    $sql .= ' AND blood_group = :bg';
    $params[':bg'] = $bloodGroup;
}
if ($division !== '' && in_array($division, divisions(), true)) {
    $sql .= ' AND division = :division';
    $params[':division'] = $division;
}
if ($district !== '') {
    $sql .= ' AND district = :district';
    $params[':district'] = $district;
}
if ($area !== '') {
    $sql .= ' AND area = :area';
    $params[':area'] = $area;
}
if ($availability !== '' && in_array($availability, ['available', 'maybe', 'unavailable'], true)) {
    $sql .= ' AND availability = :availability';
    $params[':availability'] = $availability;
} else {
    // By default, hide fully unavailable donors from the general search
    $sql .= " AND availability != 'unavailable'";
}
if ($verifiedOnly) {
    $sql .= ' AND is_verified = 1';
}

$sql .= ' ORDER BY is_verified DESC, FIELD(availability,"available","maybe","unavailable"), total_donations DESC LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

$districtOptions = $division ? districts_for_division($division) : [];
$areaOptions = $district ? areas_for_district($district) : [];

$pageTitle = 'Find a Donor — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Search</span>
        <h2>Find a Blood Donor</h2>
        <p>Filter by blood group, location, and availability to find the right donor near you.</p>
    </div>

    <form class="filters-bar" method="get" action="<?= e(SITE_URL) ?>/find-donor.php">
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
                <label>Division</label>
                <select name="division" data-division data-target-district="f_district">
                    <option value="">Any</option>
                    <?php foreach (divisions() as $d): ?>
                        <option value="<?= e($d) ?>" <?= $division === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>District</label>
                <select name="district" id="f_district" data-district data-target-area="f_area_list" data-selected="<?= e($district) ?>">
                    <option value="">Any</option>
                    <?php foreach ($districtOptions as $d): ?>
                        <option value="<?= e($d) ?>" <?= $district === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Area</label>
                <input type="text" name="area" id="f_area" list="f_area_list" value="<?= e($area) ?>" placeholder="Any" autocomplete="off">
                <datalist id="f_area_list">
                    <?php foreach ($areaOptions as $a): ?>
                        <option value="<?= e($a) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Availability</label>
                <select name="availability">
                    <option value="">Any</option>
                    <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>🟢 Available</option>
                    <option value="maybe" <?= $availability === 'maybe' ? 'selected' : '' ?>>🟡 Maybe</option>
                    <option value="unavailable" <?= $availability === 'unavailable' ? 'selected' : '' ?>>🔴 Not Available</option>
                </select>
            </div>
        </div>
        <div class="flex items-center gap-1 mt-1">
            <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;">
                <input type="checkbox" name="verified" value="1" <?= $verifiedOnly ? 'checked' : '' ?>> Verified donors only
            </label>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;"><i class="fa-solid fa-filter"></i> Apply Filters</button>
            <a href="<?= e(SITE_URL) ?>/find-donor.php" class="btn btn-light btn-sm">Reset</a>
        </div>
    </form>

    <p class="text-muted mb-2"><?= count($donors) ?> donor(s) found</p>

    <?php if ($donors): ?>
    <div class="grid grid-4">
        <?php foreach ($donors as $donor): ?>
            <div class="donor-card">
                <span class="blood-chip"><?= e($donor['blood_group']) ?></span>
                <?php if (!empty($donor['profile_photo'])): ?>
                    <img class="donor-avatar" src="<?= e(UPLOAD_URL . $donor['profile_photo']) ?>" alt="">
                <?php else: ?>
                    <div class="donor-avatar-fallback"><?= e(mb_substr($donor['full_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <div class="donor-name"><?= e($donor['full_name']) ?></div>
                <div class="donor-meta">
                    <?php if ($donor['is_verified']): ?><span class="badge badge-success"><i class="fa-solid fa-check"></i> Verified</span><?php endif; ?>
                    <span class="badge <?= status_badge_class($donor['availability']) ?>">
                        <?= $donor['availability'] === 'available' ? '🟢 Available' : ($donor['availability'] === 'maybe' ? '🟡 Maybe' : '🔴 Unavailable') ?>
                    </span>
                </div>
                <div class="donor-location"><i class="fa-solid fa-location-dot"></i> <?= e($donor['area'] ? $donor['area'] . ', ' : '') . e($donor['district']) ?></div>
                <div class="donor-last-donation">
                    Last Donation: <?= $donor['last_donation_date'] ? e(date('d M Y', strtotime($donor['last_donation_date']))) : 'No record yet' ?>
                </div>
                <div class="donor-card-actions">
                    <a href="<?= e(SITE_URL) ?>/donor-profile.php?id=<?= e($donor['id']) ?>" class="btn btn-light btn-sm" style="flex:1;">View Profile</a>
                    <a href="<?= e(SITE_URL) ?>/seeker/create-request.php?donor_id=<?= e($donor['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;">Request Blood</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-user-slash"></i>
            <p>No donors match your filters right now. Try widening your search.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
