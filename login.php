<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = clean($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter your email/phone and password.';
    } elseif (attempt_login($identifier, $password)) {
        $role = current_user_role();
        $target = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);
        flash_set('success', 'Welcome back!');
        if ($target) {
            header('Location: ' . $target);
            exit;
        }
        $dashPrefix = $role === 'admin' ? 'admin' : ($role === 'seeker' ? 'seeker' : 'donor');
        redirect($dashPrefix . '/dashboard.php');
    } else {
        $error = $error ?: 'Invalid email/phone or password.';
        if (!empty($_SESSION['flash'])) {
            // attempt_login may have set a suspended-account flash message
            $flashes = flash_get_all();
            $error = end($flashes)['message'] ?? $error;
        }
    }
}

$pageTitle = 'Login — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p class="sub">Log in to manage requests, donations, and your profile.</p>

        <?php if ($error): ?>
            <div class="error-list"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(SITE_URL) ?>/login.php">
            <?= csrf_field() ?>
            <div class="form-group form-full mb-1">
                <label for="identifier">Email or Phone</label>
                <input type="text" id="identifier" name="identifier" required autofocus>
            </div>
            <div class="form-group form-full mb-1">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </div>
        </form>
        <div class="auth-switch">Don't have an account? <a href="<?= e(SITE_URL) ?>/register.php">Register now</a></div>
        <div class="auth-switch" style="margin-top:6px;font-size:0.8rem;">
            Demo: <code>admin@bloodconnect.test</code> / <code>Admin@12345</code>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
