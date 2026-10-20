<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$admin = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $fullName = clean($_POST['full_name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        if ($fullName === '') $errors[] = 'Please enter your name.';
        if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) $errors[] = 'Please enter a valid phone number.';

        if (!$errors) {
            $pdo->prepare('UPDATE users SET full_name = :n, phone = :p WHERE id = :id')
                ->execute([':n' => $fullName, ':p' => $phone, ':id' => $admin['id']]);
            flash_set('success', 'Profile updated.');
            redirect('admin/settings.php');
        }
    } elseif ($formType === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $admin['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                ->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => $admin['id']]);
            flash_set('success', 'Password changed successfully.');
            redirect('admin/settings.php');
        }
    }
}

$pageTitle = 'Settings — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_admin_sidebar('settings'); ?>
    <div class="dash-main">
        <h1>Admin Settings</h1>

        <?php if ($errors): ?><div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <div class="card mb-2" style="max-width:520px;">
            <h3 class="mb-1">Profile</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_type" value="profile">
                <div class="form-group mb-1">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= e($admin['full_name']) ?>" required>
                </div>
                <div class="form-group mb-1">
                    <label>Email</label>
                    <input type="email" value="<?= e($admin['email']) ?>" disabled>
                </div>
                <div class="form-group mb-1">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?= e($admin['phone']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>

        <div class="card" style="max-width:520px;">
            <h3 class="mb-1">Change Password</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_type" value="password">
                <div class="form-group mb-1">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group mb-1">
                    <label>New Password</label>
                    <input type="password" name="new_password" minlength="8" required>
                </div>
                <div class="form-group mb-1">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
