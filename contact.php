<?php
require_once __DIR__ . '/includes/auth.php';

$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    // This is a demo project: the message is simply acknowledged.
    // In production, you would store this in a table or send an email.
    $submitted = true;
    flash_set('success', 'Thank you! Your message has been received. We will get back to you soon.');
    redirect('contact.php');
}

$pageTitle = 'Contact Us — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:640px;">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Get in Touch</span>
        <h2>Contact Us</h2>
        <p>Questions, feedback, or partnership ideas? Send us a message.</p>
    </div>

    <div class="card">
        <form method="post" action="<?= e(SITE_URL) ?>/contact.php">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group form-full">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group form-full">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Send Message</button>
            </div>
        </form>
    </div>

    <div class="grid grid-3 mt-2">
        <div class="card text-center"><i class="fa-solid fa-envelope" style="color:var(--red-600);"></i><p class="mt-1" style="font-size:0.85rem;">support@bloodconnect.test</p></div>
        <div class="card text-center"><i class="fa-solid fa-phone" style="color:var(--red-600);"></i><p class="mt-1" style="font-size:0.85rem;">+880 1700-000000</p></div>
        <div class="card text-center"><i class="fa-solid fa-location-dot" style="color:var(--red-600);"></i><p class="mt-1" style="font-size:0.85rem;">Dhaka, Bangladesh</p></div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
