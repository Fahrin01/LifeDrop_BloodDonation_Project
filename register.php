<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old = $_POST;
$presetType = in_array($_GET['type'] ?? '', ['donor', 'seeker', 'both'], true) ? $_GET['type'] : 'seeker';
$old['account_type'] = $old['account_type'] ?? $presetType;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$success, $errors, $newUserId] = register_user($_POST);

    if ($success) {
        flash_set('success', 'Account created successfully! Please log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Register — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap" style="max-width:640px;">
    <div class="auth-card">
        <h2>Create Your Account</h2>
        <p class="sub">Join LifeDrop as a donor, a seeker, or both.</p>

        <?php if ($errors): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(SITE_URL) ?>/register.php" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group form-full mb-1">
                <label>I want to register as</label>
                <div class="role-toggle">
                    <label><input type="radio" name="account_type" value="seeker" <?= ($old['account_type'] ?? '') === 'seeker' ? 'checked' : '' ?>><span><i class="fa-solid fa-hand-holding-medical"></i> Blood Seeker</span></label>
                    <label><input type="radio" name="account_type" value="donor" <?= ($old['account_type'] ?? '') === 'donor' ? 'checked' : '' ?>><span><i class="fa-solid fa-droplet"></i> Donor</span></label>
                    <label><input type="radio" name="account_type" value="both" <?= ($old['account_type'] ?? '') === 'both' ? 'checked' : '' ?>><span><i class="fa-solid fa-people-arrows"></i> Both</span></label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group form-full">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required value="<?= e($old['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="01712345678" required value="<?= e($old['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">Select</option>
                        <?php foreach (blood_groups() as $bg): ?>
                            <option value="<?= e($bg) ?>" <?= ($old['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gender">Gender (optional)</label>
                    <select id="gender" name="gender">
                        <option value="">Prefer not to say</option>
                        <option value="Male" <?= ($old['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($old['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="division">Division</label>
                    <select id="division" name="division" data-division data-target-district="district">
                        <option value="">Select</option>
                        <?php foreach (divisions() as $d): ?>
                            <option value="<?= e($d) ?>" <?= ($old['division'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="district">District</label>
                    <select id="district" name="district" data-district data-target-area="area_list" data-selected="<?= e($old['district'] ?? '') ?>">
                        <option value="">Select Division First</option>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label for="area">Upazila / Area</label>
                    <input type="text" id="area" name="area" list="area_list" value="<?= e($old['area'] ?? '') ?>" placeholder="Type your area" autocomplete="off">
                    <datalist id="area_list"></datalist>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth (optional)</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($old['date_of_birth'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="profile_photo">Profile Photo (optional)</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="form-group" id="lastDonationGroup">
                    <label for="last_donation_date">Last Donation Date (donors)</label>
                    <input type="date" id="last_donation_date" name="last_donation_date" value="<?= e($old['last_donation_date'] ?? '') ?>">
                </div>
                <div class="form-group" id="availabilityGroup">
                    <label for="availability">Availability (donors)</label>
                    <select id="availability" name="availability">
                        <option value="available" <?= ($old['availability'] ?? 'available') === 'available' ? 'selected' : '' ?>>🟢 Available</option>
                        <option value="maybe" <?= ($old['availability'] ?? '') === 'maybe' ? 'selected' : '' ?>>🟡 Maybe Available</option>
                        <option value="unavailable" <?= ($old['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>🔴 Not Available</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </div>
        </form>
        <div class="auth-switch">Already have an account? <a href="<?= e(SITE_URL) ?>/login.php">Log in</a></div>
    </div>
</div>

<script>
function toggleDonorFields() {
    const type = document.querySelector('input[name="account_type"]:checked')?.value;
    const show = (type === 'donor' || type === 'both');
    document.getElementById('lastDonationGroup').style.display = show ? '' : 'none';
    document.getElementById('availabilityGroup').style.display = show ? '' : 'none';
}
document.querySelectorAll('input[name="account_type"]').forEach(function (el) {
    el.addEventListener('change', toggleDonorFields);
});
toggleDonorFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
