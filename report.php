<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$reportedUserId = (int) ($_GET['user_id'] ?? 0) ?: null;
$reportedRequestId = (int) ($_GET['request_id'] ?? 0) ?: null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reason = $_POST['reason'] ?? '';
    $details = clean($_POST['details'] ?? '');
    $reportedUserId = (int) ($_POST['reported_user_id'] ?? 0) ?: null;
    $reportedRequestId = (int) ($_POST['reported_request_id'] ?? 0) ?: null;

    $validReasons = ['fake_request', 'fake_donor', 'spam', 'harassment', 'incorrect_info', 'suspicious_activity', 'other'];
    if (!in_array($reason, $validReasons, true)) {
        $errors[] = 'Please select a valid reason.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, reported_request_id, reason, details)
             VALUES (:reporter, :ruser, :rreq, :reason, :details)'
        );
        $stmt->execute([
            ':reporter' => $user['id'], ':ruser' => $reportedUserId, ':rreq' => $reportedRequestId,
            ':reason' => $reason, ':details' => $details ?: null,
        ]);
        flash_set('success', 'Thank you. Your report has been submitted and will be reviewed by our team.');
        redirect('index.php');
    }
}

$pageTitle = 'Report an Issue — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:600px;">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Safety</span>
        <h2>Report an Issue</h2>
        <p>Help us keep LifeDrop safe by reporting fake requests, fake donors, spam, or suspicious activity.</p>
    </div>

    <div class="card">
        <?php if ($errors): ?><div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= e(SITE_URL) ?>/report.php">
            <?= csrf_field() ?>
            <input type="hidden" name="reported_user_id" value="<?= e($reportedUserId) ?>">
            <input type="hidden" name="reported_request_id" value="<?= e($reportedRequestId) ?>">
            <div class="form-group mb-1">
                <label>Reason</label>
                <select name="reason" required>
                    <option value="">Select a reason</option>
                    <option value="fake_request">Fake blood request</option>
                    <option value="fake_donor">Fake donor</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harassment</option>
                    <option value="incorrect_info">Incorrect information</option>
                    <option value="suspicious_activity">Suspicious activity</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group mb-2">
                <label>Details (optional)</label>
                <textarea name="details" placeholder="Please share any details that would help us review this."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit Report</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
