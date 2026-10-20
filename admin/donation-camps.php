<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$admin = current_user();
$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM donation_camps WHERE id = :id')->execute([':id' => (int) $_POST['camp_id']]);
        flash_set('success', 'Camp removed.');
        redirect('admin/donation-camps.php');
    }

    $id = (int) ($_POST['camp_id'] ?? 0);
    $campName = clean($_POST['camp_name'] ?? '');
    $organization = clean($_POST['organization'] ?? '');
    $campDate = $_POST['camp_date'] ?? '';
    $campTime = clean($_POST['camp_time'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $division = clean($_POST['division'] ?? '');
    $district = clean($_POST['district'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $contact = clean($_POST['contact'] ?? '');
    $registrationInfo = clean($_POST['registration_info'] ?? '');

    if ($campName === '') $errors[] = 'Please enter a camp name.';
    if (!$campDate) $errors[] = 'Please select a date.';
    if ($location === '') $errors[] = 'Please enter a location.';

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE donation_camps SET camp_name=:name, organization=:org, camp_date=:date, camp_time=:time,
                 location=:loc, division=:division, district=:district, description=:desc, contact=:contact,
                 registration_info=:reg WHERE id=:id'
            );
            $stmt->execute([':name'=>$campName, ':org'=>$organization ?: null, ':date'=>$campDate, ':time'=>$campTime ?: null,
                ':loc'=>$location, ':division'=>$division ?: null, ':district'=>$district ?: null, ':desc'=>$description ?: null,
                ':contact'=>$contact ?: null, ':reg'=>$registrationInfo ?: null, ':id'=>$id]);
            flash_set('success', 'Camp updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO donation_camps (camp_name, organization, camp_date, camp_time, location, division, district, description, contact, registration_info, created_by)
                 VALUES (:name, :org, :date, :time, :loc, :division, :district, :desc, :contact, :reg, :admin)'
            );
            $stmt->execute([':name'=>$campName, ':org'=>$organization ?: null, ':date'=>$campDate, ':time'=>$campTime ?: null,
                ':loc'=>$location, ':division'=>$division ?: null, ':district'=>$district ?: null, ':desc'=>$description ?: null,
                ':contact'=>$contact ?: null, ':reg'=>$registrationInfo ?: null, ':admin'=>$admin['id']]);
            flash_set('success', 'Camp created.');
        }
        redirect('admin/donation-camps.php');
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM donation_camps WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$camps = $pdo->query('SELECT * FROM donation_camps ORDER BY camp_date DESC')->fetchAll();

$pageTitle = 'Manage Donation Camps — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('donation-camps'); ?>
    <div class="dash-main">
        <h1>Donation Camps</h1>

        <div class="card mb-2">
            <div class="panel-header"><h3><?= $editing ? 'Edit Camp' : 'Create New Camp' ?></h3></div>
            <?php if ($errors): ?><div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="<?= e(SITE_URL) ?>/admin/donation-camps.php">
                <?= csrf_field() ?>
                <input type="hidden" name="camp_id" value="<?= e($editing['id'] ?? '') ?>">
                <div class="form-grid">
                    <div class="form-group form-full"><label>Camp Name</label><input type="text" name="camp_name" value="<?= e($editing['camp_name'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Organization</label><input type="text" name="organization" value="<?= e($editing['organization'] ?? '') ?>"></div>
                    <div class="form-group"><label>Date</label><input type="date" name="camp_date" value="<?= e($editing['camp_date'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Time</label><input type="text" name="camp_time" value="<?= e($editing['camp_time'] ?? '') ?>" placeholder="9:00 AM - 4:00 PM"></div>
                    <div class="form-group form-full"><label>Location</label><input type="text" name="location" value="<?= e($editing['location'] ?? '') ?>" required></div>
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division">
                            <option value="">Select</option>
                            <?php foreach (divisions() as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($editing['division'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>District</label><input type="text" name="district" value="<?= e($editing['district'] ?? '') ?>"></div>
                    <div class="form-group"><label>Contact</label><input type="text" name="contact" value="<?= e($editing['contact'] ?? '') ?>"></div>
                    <div class="form-group"><label>Registration Info</label><input type="text" name="registration_info" value="<?= e($editing['registration_info'] ?? '') ?>"></div>
                    <div class="form-group form-full"><label>Description</label><textarea name="description"><?= e($editing['description'] ?? '') ?></textarea></div>
                </div>
                <div class="form-actions flex gap-1">
                    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?></button>
                    <?php if ($editing): ?><a href="<?= e(SITE_URL) ?>/admin/donation-camps.php" class="btn btn-light">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Camp</th><th>Date</th><th>Location</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($camps as $c): ?>
                    <tr>
                        <td><?= e($c['camp_name']) ?></td>
                        <td><?= e(date('d M Y', strtotime($c['camp_date']))) ?></td>
                        <td><?= e($c['location']) ?></td>
                        <td class="table-actions">
                            <a href="?edit=<?= e($c['id']) ?>" class="btn btn-light btn-sm">Edit</a>
                            <form method="post" style="display:inline;" data-confirm="Delete this camp?">
                                <?= csrf_field() ?><input type="hidden" name="camp_id" value="<?= e($c['id']) ?>"><input type="hidden" name="action" value="delete">
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
