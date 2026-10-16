<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/auth.php';
}
require_once __DIR__ . '/notifications.php';
$user = current_user();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
function nav_active($page, $current) { return $page === $current ? 'active' : ''; }
?>
<header class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="<?= e(SITE_URL) ?>/index.php">
            <i class="fa-solid fa-droplet"></i>
            <span><?= e(SITE_NAME) ?></span>
        </a>

        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="nav-links" id="navLinks">
            <a href="<?= e(SITE_URL) ?>/index.php" class="<?= nav_active('index.php', $currentPage) ?>">Home</a>
            <a href="<?= e(SITE_URL) ?>/find-donor.php" class="<?= nav_active('find-donor.php', $currentPage) ?>">Find Donor</a>
            <a href="<?= e(SITE_URL) ?>/emergency-requests.php" class="<?= nav_active('emergency-requests.php', $currentPage) ?>">Emergency</a>
            <a href="<?= e(SITE_URL) ?>/blood-banks.php" class="<?= nav_active('blood-banks.php', $currentPage) ?>">Blood Banks</a>
            <a href="<?= e(SITE_URL) ?>/donation-camps.php" class="<?= nav_active('donation-camps.php', $currentPage) ?>">Donation Camps</a>
            <a href="<?= e(SITE_URL) ?>/compatibility.php" class="<?= nav_active('compatibility.php', $currentPage) ?>">Compatibility</a>
            <a href="<?= e(SITE_URL) ?>/about.php" class="<?= nav_active('about.php', $currentPage) ?>">About</a>

            <?php if ($user): ?>
                <div class="nav-mobile-only">
                    <a href="<?= e(SITE_URL) ?>/<?= $user['role'] === 'admin' ? 'admin' : ($user['role'] === 'seeker' ? 'seeker' : 'donor') ?>/dashboard.php">Dashboard</a>
                    <a href="<?= e(SITE_URL) ?>/logout.php">Logout</a>
                </div>
            <?php else: ?>
                <div class="nav-mobile-only">
                    <a href="<?= e(SITE_URL) ?>/login.php">Login</a>
                    <a href="<?= e(SITE_URL) ?>/register.php" class="btn-nav-primary">Register</a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="nav-right">
            <?php if ($user): ?>
                <?php render_notification_bell($user['id']); ?>
                <div class="user-menu">
                    <button class="user-menu-btn" id="userMenuBtn" type="button">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="<?= e(UPLOAD_URL . $user['profile_photo']) ?>" alt="" class="avatar-sm">
                        <?php else: ?>
                            <span class="avatar-sm avatar-fallback"><?= e(mb_substr($user['full_name'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <span class="user-menu-name"><?= e(explode(' ', $user['full_name'])[0]) ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <?php
                        $dashPrefix = $user['role'] === 'admin' ? 'admin' : ($user['role'] === 'seeker' ? 'seeker' : 'donor');
                        ?>
                        <a href="<?= e(SITE_URL) ?>/<?= $dashPrefix ?>/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                        <?php if ($user['role'] !== 'admin'): ?>
                        <a href="<?= e(SITE_URL) ?>/<?= $dashPrefix ?>/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                        <?php endif; ?>
                        <a href="<?= e(SITE_URL) ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(SITE_URL) ?>/login.php" class="btn-nav">Login</a>
                <a href="<?= e(SITE_URL) ?>/register.php" class="btn-nav-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
