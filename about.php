<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About Us — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:820px;">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Our Mission</span>
        <h2>About <?= e(SITE_NAME) ?></h2>
    </div>

    <div class="card mb-2">
        <p><?= e(SITE_NAME) ?> is a Bangladesh-focused platform built to make it easier for people who
        need blood to connect with willing, verified donors — during both routine and emergency
        situations.</p>
        <p>We believe that no patient should struggle to find compatible blood because of a lack
        of coordination. By combining a simple donor directory, smart matching, and real-time
        notifications, we aim to shorten the distance between a request and a life saved.</p>
    </div>

    <div class="grid grid-3 mb-2">
        <div class="card text-center">
            <i class="fa-solid fa-droplet" style="font-size:1.8rem;color:var(--red-600);"></i>
            <h4 class="mt-1">Simple Matching</h4>
            <p class="text-muted" style="font-size:0.88rem;">A transparent, rule-based algorithm connects seekers with the closest compatible donors.</p>
        </div>
        <div class="card text-center">
            <i class="fa-solid fa-shield-heart" style="font-size:1.8rem;color:var(--red-600);"></i>
            <h4 class="mt-1">Privacy First</h4>
            <p class="text-muted" style="font-size:0.88rem;">Personal details stay protected until a genuine connection is made.</p>
        </div>
        <div class="card text-center">
            <i class="fa-solid fa-people-group" style="font-size:1.8rem;color:var(--red-600);"></i>
            <h4 class="mt-1">Community Driven</h4>
            <p class="text-muted" style="font-size:0.88rem;">Built for donors, seekers, hospitals, and volunteers across the country.</p>
        </div>
    </div>

    <div class="disclaimer-box">
        <i class="fa-solid fa-circle-info"></i>
        <span><?= e(SITE_NAME) ?> is a connection platform and does not replace medical professionals, hospitals, blood banks, or emergency services.</span>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
