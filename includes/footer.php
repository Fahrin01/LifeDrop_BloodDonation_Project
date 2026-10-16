</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col footer-brand">
            <div class="brand">
                <i class="fa-solid fa-droplet"></i>
                <span><?= e(SITE_NAME) ?></span>
            </div>
            <p>Connecting blood donors with people who need blood.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="<?= e(SITE_URL) ?>/find-donor.php">Find Donor</a>
            <a href="<?= e(SITE_URL) ?>/emergency-requests.php">Emergency Requests</a>
            <a href="<?= e(SITE_URL) ?>/blood-banks.php">Blood Banks</a>
            <a href="<?= e(SITE_URL) ?>/compatibility.php">Compatibility</a>
        </div>
        <div class="footer-col">
            <h4>Platform</h4>
            <a href="<?= e(SITE_URL) ?>/about.php">About Us</a>
            <a href="<?= e(SITE_URL) ?>/donation-camps.php">Donation Camps</a>
            <a href="<?= e(SITE_URL) ?>/health-safety.php">Health &amp; Safety</a>
            <a href="<?= e(SITE_URL) ?>/contact.php">Contact</a>
            <?php if (is_logged_in()): ?><a href="<?= e(SITE_URL) ?>/report.php">Report an Issue</a><?php endif; ?>
        </div>
        <div class="footer-col footer-disclaimer-col">
            <h4>Important</h4>
            <p class="footer-disclaimer">
                <?= e(SITE_NAME) ?> is a connection platform and does not replace medical
                professionals, hospitals, blood banks, or emergency services. Always consult a
                qualified healthcare professional for medical decisions.
            </p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.
    </div>
</footer>
<script src="<?= e(SITE_URL) ?>/assets/js/script.js"></script>
</body>
</html>
