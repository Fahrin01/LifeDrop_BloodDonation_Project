<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Blood Compatibility — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-tight" style="max-width:900px;">
    <div class="section-header" style="margin-bottom:26px;">
        <span class="eyebrow">Learn</span>
        <h2>Blood Compatibility Guide</h2>
        <p>A simplified overview of which blood groups can donate to or receive from each other.</p>
    </div>

    <div class="disclaimer-box">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>This information is for educational purposes only. Actual transfusion decisions must be made by qualified healthcare professionals and authorized blood banks.</span>
    </div>

    <div class="table-wrap">
        <table class="compat-table">
            <thead>
                <tr>
                    <th>Blood Group</th>
                    <th>Can Donate To</th>
                    <th>Can Receive From</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (blood_groups() as $bg): ?>
                    <tr>
                        <td><?= e($bg) ?></td>
                        <td><?= e(implode(', ', compatible_recipients($bg))) ?></td>
                        <td><?= e(implode(', ', compatible_donors($bg))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid grid-2 mt-2">
        <div class="card">
            <h4><i class="fa-solid fa-star"></i> Universal Donor</h4>
            <p class="text-muted">O- is often called the universal donor because it can donate red blood cells to any blood group in emergencies.</p>
        </div>
        <div class="card">
            <h4><i class="fa-solid fa-star"></i> Universal Recipient</h4>
            <p class="text-muted">AB+ is often called the universal recipient because it can receive red blood cells from any blood group.</p>
        </div>
    </div>

    <div class="card mt-2">
        <h4>Rh Factor</h4>
        <p class="text-muted">The "+" or "−" after a blood group refers to the Rh factor. Generally, Rh-negative blood can be given to both Rh-positive and Rh-negative recipients, while Rh-positive blood should only be given to Rh-positive recipients — but final decisions always rest with medical professionals.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
