<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM hospitals WHERE id = :id')->execute([':id' => (int) $_POST['hospital_id']]);
        flash_set('success', 'Entry removed.');
        redirect('admin/hospitals.php');
    }

    $id = (int) ($_POST['hospital_id'] ?? 0);
    $name = clean($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'hospital';
    $address = clean($_POST['address'] ?? '');
    $division = clean($_POST['division'] ?? '');
    $district = clean($_POST['district'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $openingHours = clean($_POST['opening_hours'] ?? '');
    $bloodGroups = clean($_POST['available_blood_groups'] ?? '');
    $lat = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $lng = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if ($name === '') $errors[] = 'Please enter a name.';
    if ($address === '') $errors[] = 'Please enter an address.';
    if (!in_array($division, divisions(), true)) $errors[] = 'Please select a valid division.';
    if ($district === '') $errors[] = 'Please enter a district.';

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE hospitals SET name=:name, type=:type, address=:address, division=:division, district=:district,
                 phone=:phone, opening_hours=:hours, available_blood_groups=:bg, latitude=:lat, longitude=:lng WHERE id=:id'
            );
            $stmt->execute([':name'=>$name, ':type'=>$type, ':address'=>$address, ':division'=>$division, ':district'=>$district,
                ':phone'=>$phone ?: null, ':hours'=>$openingHours ?: null, ':bg'=>$bloodGroups ?: null, ':lat'=>$lat, ':lng'=>$lng, ':id'=>$id]);
            flash_set('success', 'Entry updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO hospitals (name, type, address, division, district, phone, opening_hours, available_blood_groups, latitude, longitude)
                 VALUES (:name, :type, :address, :division, :district, :phone, :hours, :bg, :lat, :lng)'
            );
            $stmt->execute([':name'=>$name, ':type'=>$type, ':address'=>$address, ':division'=>$division, ':district'=>$district,
                ':phone'=>$phone ?: null, ':hours'=>$openingHours ?: null, ':bg'=>$bloodGroups ?: null, ':lat'=>$lat, ':lng'=>$lng]);
            flash_set('success', 'Entry added.');
        }
        redirect('admin/hospitals.php');
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM hospitals WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$hospitals = $pdo->query('SELECT * FROM hospitals ORDER BY name')->fetchAll();
$typeLabels = ['hospital' => 'Hospital', 'blood_bank' => 'Blood Bank', 'collection_center' => 'Collection Center'];

$pageTitle = 'Manage Hospitals — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('hospitals'); ?>
    <div class="dash-main">
        <h1>Hospitals &amp; Blood Banks</h1>

        <div class="card mb-2">
            <div class="panel-header"><h3><?= $editing ? 'Edit Entry' : 'Add New Hospital / Blood Bank' ?></h3></div>
            <?php if ($errors): ?><div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="<?= e(SITE_URL) ?>/admin/hospitals.php">
                <?= csrf_field() ?>
                <input type="hidden" name="hospital_id" value="<?= e($editing['id'] ?? '') ?>">
                <div class="form-grid">
                    <div class="form-group form-full"><label>Name</label><input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required></div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <?php foreach ($typeLabels as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($editing['type'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($editing['phone'] ?? '') ?>"></div>
                    <div class="form-group form-full"><label>Address</label><input type="text" name="address" value="<?= e($editing['address'] ?? '') ?>" required></div>
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division" required>
                            <option value="">Select</option>
                            <?php foreach (divisions() as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($editing['division'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>District</label><input type="text" name="district" value="<?= e($editing['district'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Opening Hours</label><input type="text" name="opening_hours" value="<?= e($editing['opening_hours'] ?? '') ?>" placeholder="e.g. 24 Hours"></div>
                    <div class="form-group"><label>Available Blood Groups</label><input type="text" name="available_blood_groups" value="<?= e($editing['available_blood_groups'] ?? '') ?>" placeholder="A+,O+,B-"></div>
                    <div class="form-group"><label>Latitude (optional)</label><input type="text" name="latitude" value="<?= e($editing['latitude'] ?? '') ?>"></div>
                    <div class="form-group"><label>Longitude (optional)</label><input type="text" name="longitude" value="<?= e($editing['longitude'] ?? '') ?>"></div>
                </div>
                <div class="form-actions flex gap-1">
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?></button>
                    <?php if ($editing): ?><a href="<?= e(SITE_URL) ?>/admin/hospitals.php" class="btn btn-light">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Type</th><th>District</th><th>Phone</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($hospitals as $h): ?>
                    <tr>
                        <td><?= e($h['name']) ?></td>
                        <td><span class="badge badge-muted"><?= e($typeLabels[$h['type']] ?? $h['type']) ?></span></td>
                        <td><?= e($h['district']) ?></td>
                        <td><?= e($h['phone'] ?? '—') ?></td>
                        <td class="table-actions">
                            <a href="?edit=<?= e($h['id']) ?>" class="btn btn-light btn-sm">Edit</a>
                            <form method="post" style="display:inline;" data-confirm="Delete this entry?">
                                <?= csrf_field() ?><input type="hidden" name="hospital_id" value="<?= e($h['id']) ?>"><input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
