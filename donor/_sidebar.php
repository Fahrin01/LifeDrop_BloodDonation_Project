<?php
/** Renders the donor dashboard sidebar. Call render_donor_sidebar($active). */
function render_donor_sidebar($active) {
    $links = [
        'dashboard'        => ['dashboard.php', 'fa-gauge', 'Dashboard'],
        'profile'          => ['profile.php', 'fa-user', 'Profile'],
        'availability'     => ['availability.php', 'fa-toggle-on', 'Availability'],
        'requests'         => ['requests.php', 'fa-inbox', 'Requests'],
        'donation-history' => ['donation-history.php', 'fa-clock-rotate-left', 'Donation History'],
        'notifications'    => ['notifications.php', 'fa-bell', 'Notifications'],
        'messages'         => ['messages.php', 'fa-comments', 'Messages'],
    ];
    ?>
    <div class="dash-sidebar">
        <?php foreach ($links as $key => [$href, $icon, $label]): ?>
            <a href="<?= e(SITE_URL) ?>/donor/<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= e($icon) ?>"></i> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
