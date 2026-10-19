<?php
require_once __DIR__ . '/includes/auth.php';

$stmt = $pdo->query("SELECT * FROM donation_camps WHERE camp_date >= CURDATE() ORDER BY camp_date ASC");
$upcomingCamps = $stmt->fetchAll();

$stmt2 = $pdo->query("SELECT * FROM donation_camps WHERE camp_date < CURDATE() ORDER BY camp_date DESC LIMIT 6");
$pastCamps = $stmt2->fetchAll();

$pageTitle = 'Donation Camps — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

function render_camp_card($camp) {
    ?>
    <div class="camp-card">
        <div class="camp-date-badge">
            <div class="d"><?= e(date('d', strtotime($camp['camp_date']))) ?></div>
            <div class="m"><?= e(date('M Y', strtotime($camp['camp_date']))) ?></div>
        </div>
        <div class="camp-card-body">
            <h4><?= e($camp['camp_name']) ?></h4>
            <?php if ($camp['organization']): ?><p class="text-muted" style="font-size:0.85rem;">by <?= e($camp['organization']) ?></p><?php endif; ?>
            <p style="font-size:0.85rem;"><i class="fa-solid fa-location-dot"></i> <?= e($camp['location']) ?></p>
            <?php if ($camp['camp_time']): ?><p style="font-size:0.85rem;"><i class="fa-regular fa-clock"></i> <?= e($camp['camp_time']) ?></p><?php endif; ?>
            <?php if ($camp['description']): ?><p class="text-muted" style="font-size:0.85rem;"><?= e($camp['description']) ?></p><?php endif; ?>
            <?php if ($camp['contact']): ?><p style="font-size:0.85rem;"><i class="fa-solid fa-phone"></i> <?= e($camp['contact']) ?></p><?php endif; ?>
            <?php if ($camp['registration_info']): ?><p class="text-muted" style="font-size:0.8rem;"><i class="fa-solid fa-circle-info"></i> <?= e($camp['registration_info']) ?></p><?php endif; ?>
        </div>
    </div>
    <?php
}
?>
<div class="container section-tight">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Get Involved</span>
        <h2>Blood Donation Camps</h2>
        <p>Browse upcoming voluntary blood donation camps and drives near you.</p>
    </div>

    <h3 class="mb-1">Upcoming Camps</h3>
    <?php if ($upcomingCamps): ?>
        <div class="grid grid-3 mb-2">
            <?php foreach ($upcomingCamps as $camp) render_camp_card($camp); ?>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="fa-regular fa-calendar"></i><p>No upcoming camps scheduled right now. Check back soon!</p></div>
    <?php endif; ?>

    <?php if ($pastCamps): ?>
        <h3 class="mb-1 mt-2">Past Camps</h3>
        <div class="grid grid-3">
            <?php foreach ($pastCamps as $camp) render_camp_card($camp); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
