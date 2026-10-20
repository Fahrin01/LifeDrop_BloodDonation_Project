<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Health & Safety — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:820px;">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Guidelines</span>
        <h2>Health &amp; Safety Information</h2>
        <p>General, non-medical guidance for donors and seekers using LifeDrop.</p>
    </div>

    <div class="disclaimer-box">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?= e(SITE_NAME) ?> does not provide medical advice or determine medical eligibility to donate. Always consult a qualified healthcare professional or authorized blood bank before donating or receiving blood.</span>
    </div>

    <div class="card mb-2">
        <h4><i class="fa-solid fa-clipboard-check"></i> Before You Donate</h4>
        <ul style="list-style:disc;padding-left:20px;">
            <li>Consult the blood bank or hospital staff about your eligibility to donate.</li>
            <li>Eat a healthy meal and stay hydrated before donating.</li>
            <li>Avoid donating if you feel unwell, have a fever, or have recently taken certain medications — ask a professional.</li>
            <li>Bring a valid ID and, if available, your donor card.</li>
        </ul>
    </div>

    <div class="card mb-2">
        <h4><i class="fa-solid fa-heart-pulse"></i> After You Donate</h4>
        <ul style="list-style:disc;padding-left:20px;">
            <li>Rest for a few minutes and have a light snack/drink as advised by the staff.</li>
            <li>Avoid heavy lifting or strenuous exercise for the rest of the day.</li>
            <li>Keep the bandage on for several hours and watch for any unusual symptoms.</li>
            <li>Follow the recommended waiting period before your next donation, as advised by a medical professional.</li>
        </ul>
    </div>

    <div class="card">
        <h4><i class="fa-solid fa-user-shield"></i> Platform Safety</h4>
        <ul style="list-style:disc;padding-left:20px;">
            <li>Never share your password or OTP with anyone.</li>
            <li>Donor and seeker contact details are only shared after a request is created and accepted.</li>
            <li>Report fake requests, suspicious accounts, or harassment using the report option.</li>
            <li>Always meet in safe, public settings such as hospitals or blood banks.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
