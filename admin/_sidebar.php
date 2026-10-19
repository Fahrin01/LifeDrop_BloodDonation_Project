<?php
/** Renders the admin dashboard sidebar. Call render_admin_sidebar($active). */
function render_admin_sidebar($active) {
    $links = [
        'dashboard'      => ['dashboard.php', 'fa-gauge', 'Dashboard'],
        'users'          => ['users.php', 'fa-users', 'Users'],
        'donors'         => ['donors.php', 'fa-user-check', 'Verify Donors'],
        'blood-requests' => ['blood-requests.php', 'fa-droplet', 'Blood Requests'],
        'hospitals'      => ['hospitals.php', 'fa-hospital', 'Hospitals'],
        'donation-camps' => ['donation-camps.php', 'fa-campground', 'Donation Camps'],
        'reports'        => ['reports.php', 'fa-flag', 'Reports'],
        'settings'       => ['settings.php', 'fa-gear', 'Settings'],
    ];
    ?>
    <div class="dash-sidebar">
        <?php foreach ($links as $key => [$href, $icon, $label]): ?>
            <a href="<?= e(SITE_URL) ?>/admin/<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= e($icon) ?>"></i> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
