<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['donor']);

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $donationDate = $_POST['donation_date'] ?? '';
    $hospitalName = clean($_POST['hospital_name'] ?? '');
    $bloodGroup = $_POST['blood_group'] ?? '';
    $units = (int) ($_POST['units'] ?? 1);
    $notes = clean($_POST['notes'] ?? '');

    if (!$donationDate) $errors[] = 'Please select a donation date.';
    if ($hospitalName === '') $errors[] = 'Please enter the hospital/blood bank name.';
    if (!in_array($bloodGroup, blood_groups(), true)) $errors[] = 'Please select a valid blood group.';
    if ($units < 1 || $units > 10) $errors[] = 'Units must be between 1 and 10.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO donation_history (donor_id, donation_date, hospital_name, blood_group, units, notes, status)
             VALUES (:donor_id, :date, :hospital, :bg, :units, :notes, "completed")'
        );
        $stmt->execute([
            ':donor_id' => $user['id'], ':date' => $donationDate, ':hospital' => $hospitalName,
            ':bg' => $bloodGroup, ':units' => $units, ':notes' => $notes ?: null,
        ]);

        // Update donor's totals and last donation date
        $pdo->prepare(
            'UPDATE users SET total_donations = total_donations + 1,
             last_donation_date = GREATEST(COALESCE(last_donation_date, :d1), :d2) WHERE id = :id'
        )->execute([':d1' => $donationDate, ':d2' => $donationDate, ':id' => $user['id']]);

        flash_set('success', 'Donation record added. Thank you for donating!');
        redirect('donor/donation-history.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM donation_history WHERE donor_id = :id ORDER BY donation_date DESC');
$stmt->execute([':id' => $user['id']]);
$history = $stmt->fetchAll();

$pageTitle = 'Donation History — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_donor_sidebar('donation-history'); ?>
    <div class="dash-main">
        <h1>Donation History</h1>
        <p class="dash-welcome">Keep a record of your past donations.</p>

        <div class="card mb-2">
            <div class="panel-header"><h3>Add Donation Record</h3></div>
            <?php if ($errors): ?>
                <div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" action="<?= e(SITE_URL) ?>/donor/donation-history.php">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Donation Date</label>
                        <input type="date" name="donation_date" max="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood_group" required>
                            <?php foreach (blood_groups() as $bg): ?>
                                <option value="<?= e($bg) ?>" <?= $user['blood_group'] === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hospital / Blood Bank</label>
                        <input type="text" name="hospital_name" required>
                    </div>
                    <div class="form-group">
                        <label>Units</label>
                        <input type="number" name="units" value="1" min="1" max="10" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Notes (optional)</label>
                        <textarea name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="panel-header"><h3>My Records</h3></div>
            <?php if ($history): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Hospital</th><th>Blood Group</th><th>Units</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= e(date('d M Y', strtotime($h['donation_date']))) ?></td>
                            <td><?= e($h['hospital_name']) ?></td>
                            <td><span class="badge badge-muted"><?= e($h['blood_group']) ?></span></td>
                            <td><?= e($h['units']) ?></td>
                            <td><span class="badge <?= status_badge_class($h['status']) ?>"><?= e(ucfirst($h['status'])) ?></span></td>
                            <td><?= e($h['notes'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">No donation records yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
