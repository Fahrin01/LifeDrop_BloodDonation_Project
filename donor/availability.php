<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['donor']);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $availability = $_POST['availability'] ?? 'available';
    $lastDonation = $_POST['last_donation_date'] ?? null;

    if (!in_array($availability, ['available', 'maybe', 'unavailable'], true)) {
        $availability = 'available';
    }

    $stmt = $pdo->prepare('UPDATE users SET availability = :a, last_donation_date = :ld WHERE id = :id');
    $stmt->execute([':a' => $availability, ':ld' => $lastDonation ?: null, ':id' => $user['id']]);

    flash_set('success', 'Availability updated.');
    redirect('donor/availability.php');
}

$pageTitle = 'Availability — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_donor_sidebar('availability'); ?>
    <div class="dash-main">
        <h1>Donation Availability</h1>
        <p class="dash-welcome">Let seekers know whether you're currently available to donate.</p>

        <div class="card" style="max-width:520px;">
            <form method="post" action="<?= e(SITE_URL) ?>/donor/availability.php">
                <?= csrf_field() ?>
                <div class="form-group form-full mb-2">
                    <label>Current Status</label>
                    <div class="role-toggle" style="flex-direction:column;gap:10px;">
                        <label><input type="radio" name="availability" value="available" <?= $user['availability'] === 'available' ? 'checked' : '' ?>><span>🟢 Available — I can donate now</span></label>
                        <label><input type="radio" name="availability" value="maybe" <?= $user['availability'] === 'maybe' ? 'checked' : '' ?>><span>🟡 Maybe Available — depends on the situation</span></label>
                        <label><input type="radio" name="availability" value="unavailable" <?= $user['availability'] === 'unavailable' ? 'checked' : '' ?>><span>🔴 Not Available — please don't send requests</span></label>
                    </div>
                </div>
                <div class="form-group form-full mb-2">
                    <label>Last Donation Date</label>
                    <input type="date" name="last_donation_date" value="<?= e($user['last_donation_date'] ?? '') ?>">
                    <p class="text-muted" style="font-size:0.8rem;">We use this only to show you a friendly reminder — please consult a professional about your actual eligibility.</p>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Availability</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
