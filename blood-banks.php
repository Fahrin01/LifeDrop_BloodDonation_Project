<?php
require_once __DIR__ . '/includes/auth.php';

$division = clean($_GET['division'] ?? '');
$type = clean($_GET['type'] ?? '');

$sql = 'SELECT * FROM hospitals WHERE 1=1';
$params = [];
if ($division !== '' && in_array($division, divisions(), true)) {
    $sql .= ' AND division = :division';
    $params[':division'] = $division;
}
if (in_array($type, ['hospital', 'blood_bank', 'collection_center'], true)) {
    $sql .= ' AND type = :type';
    $params[':type'] = $type;
}
$sql .= ' ORDER BY name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hospitals = $stmt->fetchAll();

$typeLabels = ['hospital' => 'Hospital', 'blood_bank' => 'Blood Bank', 'collection_center' => 'Collection Center'];

$pageTitle = 'Blood Banks & Hospitals — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Directory</span>
        <h2>Hospitals &amp; Blood Banks</h2>
        <p>Find hospitals, blood banks, and blood collection centers across Bangladesh.</p>
    </div>

    <form class="filters-bar" method="get" action="<?= e(SITE_URL) ?>/blood-banks.php">
        <div class="filters-grid" style="grid-template-columns: 1fr 1fr auto;">
            <div class="form-group">
                <label>Division</label>
                <select name="division">
                    <option value="">Any</option>
                    <?php foreach (divisions() as $d): ?>
                        <option value="<?= e($d) ?>" <?= $division === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="">Any</option>
                    <option value="hospital" <?= $type === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                    <option value="blood_bank" <?= $type === 'blood_bank' ? 'selected' : '' ?>>Blood Bank</option>
                    <option value="collection_center" <?= $type === 'collection_center' ? 'selected' : '' ?>>Collection Center</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <?php if ($hospitals): ?>
    <div class="grid grid-3">
        <?php foreach ($hospitals as $h): ?>
            <div class="hospital-card">
                <span class="hospital-type"><?= e($typeLabels[$h['type']] ?? $h['type']) ?></span>
                <h4><?= e($h['name']) ?></h4>
                <p class="text-muted" style="font-size:0.85rem;margin:0;"><i class="fa-solid fa-location-dot"></i> <?= e($h['address']) ?>, <?= e($h['district']) ?></p>
                <?php if ($h['phone']): ?><p style="font-size:0.85rem;margin:0;"><i class="fa-solid fa-phone"></i> <?= e($h['phone']) ?></p><?php endif; ?>
                <?php if ($h['opening_hours']): ?><p style="font-size:0.85rem;margin:0;"><i class="fa-regular fa-clock"></i> <?= e($h['opening_hours']) ?></p><?php endif; ?>
                <?php if ($h['available_blood_groups']): ?>
                    <div class="donor-meta" style="justify-content:flex-start;margin-top:6px;">
                        <?php foreach (explode(',', $h['available_blood_groups']) as $bg): ?>
                            <span class="badge badge-muted"><?= e(trim($bg)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($h['latitude'] && $h['longitude']): ?>
                    <a class="btn btn-light btn-sm mt-1" target="_blank" rel="noopener"
                       href="https://www.openstreetmap.org/?mlat=<?= e($h['latitude']) ?>&mlon=<?= e($h['longitude']) ?>#map=16/<?= e($h['latitude']) ?>/<?= e($h['longitude']) ?>">
                       <i class="fa-solid fa-map"></i> View on Map
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-hospital"></i>
            <p>No hospitals or blood banks match your filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
