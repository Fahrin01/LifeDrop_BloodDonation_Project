<?php
/** Renders the seeker dashboard sidebar. Call render_seeker_sidebar($active). */
function render_seeker_sidebar($active) {
    $links = [
        'dashboard'       => ['dashboard.php', 'fa-gauge', 'Dashboard'],
        'create-request'  => ['create-request.php', 'fa-circle-plus', 'Create Request'],
        'my-requests'     => ['my-requests.php', 'fa-list-check', 'My Requests'],
        'matched-donors'  => ['matched-donors.php', 'fa-users', 'Matched Donors'],
        'notifications'   => ['notifications.php', 'fa-bell', 'Notifications'],
        'messages'        => ['messages.php', 'fa-comments', 'Messages'],
    ];
    ?>
    <div class="dash-sidebar">
        <?php foreach ($links as $key => [$href, $icon, $label]): ?>
            <a href="<?= e(SITE_URL) ?>/seeker/<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= e($icon) ?>"></i> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
