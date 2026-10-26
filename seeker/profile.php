<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = clean($_POST['full_name'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $bloodGroup = $_POST['blood_group'] ?? '';
    $division = clean($_POST['division'] ?? '');
    $district = clean($_POST['district'] ?? '');
    $area = clean($_POST['area'] ?? '');
    $gender = $_POST['gender'] ?? null;

    if ($fullName === '' || mb_strlen($fullName) < 2) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) $errors[] = 'Please enter a valid phone number.';
    if ($bloodGroup !== '' && !in_array($bloodGroup, blood_groups(), true)) $errors[] = 'Please select a valid blood group.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone AND id != :id');
        $stmt->execute([':phone' => $phone, ':id' => $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'That phone number is already used by another account.';
        }
    }

    $photoFilename = $user['profile_photo'];
    if (!$errors) {
        [$uploaded, $uploadError] = handle_image_upload('profile_photo', 'seeker');
        if ($uploadError) {
            $errors[] = $uploadError;
        } elseif ($uploaded) {
            $photoFilename = $uploaded;
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE users SET full_name=:full_name, phone=:phone, blood_group=:bg, division=:division,
             district=:district, area=:area, gender=:gender, profile_photo=:photo WHERE id=:id'
        );
        $stmt->execute([
            ':full_name' => $fullName, ':phone' => $phone, ':bg' => $bloodGroup ?: null,
            ':division' => $division ?: null, ':district' => $district ?: null, ':area' => $area ?: null,
            ':gender' => $gender ?: null, ':photo' => $photoFilename, ':id' => $user['id'],
        ]);
        flash_set('success', 'Profile updated successfully.');
        redirect('seeker/profile.php');
    }
    $user = array_merge($user, $_POST);
}

$districtOptions = !empty($user['division']) ? districts_for_division($user['division']) : [];
$areaOptions = !empty($user['district']) ? areas_for_district($user['district']) : [];

$pageTitle = 'My Profile — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('profile'); ?>
    <div class="dash-main">
        <h1>My Profile</h1>
        <p class="dash-welcome">Update your contact details and default location.</p>

        <div class="card" style="max-width:720px;">
            <?php if ($errors): ?>
                <div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" action="<?= e(SITE_URL) ?>/seeker/profile.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="text-center mb-2">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img class="donor-avatar" style="width:90px;height:90px;" src="<?= e(UPLOAD_URL . $user['profile_photo']) ?>" alt="">
                    <?php else: ?>
                        <div class="donor-avatar-fallback" style="width:90px;height:90px;font-size:2rem;"><?= e(mb_substr($user['full_name'], 0, 1)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email (cannot be changed)</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?= e($user['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Blood Group (optional)</label>
                        <select name="blood_group">
                            <option value="">Not set</option>
                            <?php foreach (blood_groups() as $bg): ?>
                                <option value="<?= e($bg) ?>" <?= ($user['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Prefer not to say</option>
                            <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division" data-division data-target-district="p_district">
                            <option value="">Select</option>
                            <?php foreach (divisions() as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($user['division'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <select name="district" id="p_district" data-district data-target-area="p_area_list" data-selected="<?= e($user['district'] ?? '') ?>">
                            <option value="">Select</option>
                            <?php foreach ($districtOptions as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($user['district'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" id="p_area" list="p_area_list" value="<?= e($user['area'] ?? '') ?>" placeholder="Type your area" autocomplete="off">
                        <datalist id="p_area_list">
                            <?php foreach ($areaOptions as $a): ?>
                                <option value="<?= e($a) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group form-full">
                        <label>Update Profile Photo</label>
                        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
