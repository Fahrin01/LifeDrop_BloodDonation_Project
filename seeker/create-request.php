<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['seeker']);

$user = current_user();
$errors = [];
$presetDonorId = (int) ($_GET['donor_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $bloodGroup = $_POST['blood_group'] ?? '';
    $units = (int) ($_POST['units_needed'] ?? 1);
    $patientName = clean($_POST['patient_name'] ?? '');
    $hospitalName = clean($_POST['hospital_name'] ?? '');
    $division = clean($_POST['division'] ?? '');
    $district = clean($_POST['district'] ?? '');
    $area = clean($_POST['area'] ?? '');
    $requiredDate = $_POST['required_date'] ?? '';
    $requiredTime = $_POST['required_time'] ?? null;
    $contactNumber = clean($_POST['contact_number'] ?? '');
    $additionalInfo = clean($_POST['additional_info'] ?? '');
    $requestType = ($_POST['request_type'] ?? 'normal') === 'emergency' ? 'emergency' : 'normal';
    $targetDonorId = (int) ($_POST['target_donor_id'] ?? 0);

    if (!in_array($bloodGroup, blood_groups(), true)) $errors[] = 'Please select a valid blood group.';
    if ($units < 1 || $units > 20) $errors[] = 'Units must be between 1 and 20.';
    if ($patientName === '') $errors[] = 'Please enter the patient name.';
    if ($hospitalName === '') $errors[] = 'Please enter the hospital name.';
    if (!in_array($division, divisions(), true)) $errors[] = 'Please select a valid division.';
    if ($district === '') $errors[] = 'Please select a district.';
    if (!$requiredDate || strtotime($requiredDate) < strtotime(date('Y-m-d'))) $errors[] = 'Please choose a valid required date (today or later).';
    if (!preg_match('/^01[3-9][0-9]{8}$/', $contactNumber)) $errors[] = 'Please enter a valid contact number.';

    if (!$errors) {
        $requestCode = generate_request_code();
        $stmt = $pdo->prepare(
            'INSERT INTO blood_requests
            (request_code, seeker_id, blood_group, units_needed, patient_name, hospital_name, division,
             district, area, required_date, required_time, contact_number, additional_info, request_type, status)
            VALUES (:code, :seeker, :bg, :units, :patient, :hospital, :division, :district, :area,
             :req_date, :req_time, :contact, :info, :type, "pending")'
        );
        $stmt->execute([
            ':code' => $requestCode, ':seeker' => $user['id'], ':bg' => $bloodGroup, ':units' => $units,
            ':patient' => $patientName, ':hospital' => $hospitalName, ':division' => $division,
            ':district' => $district, ':area' => $area ?: null, ':req_date' => $requiredDate,
            ':req_time' => $requiredTime ?: null, ':contact' => $contactNumber, ':info' => $additionalInfo ?: null,
            ':type' => $requestType,
        ]);
        $requestId = (int) $pdo->lastInsertId();
        $requestRow = [
            'blood_group' => $bloodGroup, 'district' => $district, 'division' => $division,
        ];

        // ---- Rule-based donor matching ----
        $donorStmt = $pdo->prepare(
            "SELECT id, blood_group, division, district, availability, is_verified
             FROM users WHERE is_donor_profile = 1 AND status = 'active' AND availability != 'unavailable' AND id != :seeker_id"
        );
        $donorStmt->execute([':seeker_id' => $user['id']]);
        $candidateDonors = $donorStmt->fetchAll();

        $scored = [];
        foreach ($candidateDonors as $donor) {
            $score = calculate_match_score($donor, $requestRow);
            if ($score >= 35 || (int) $donor['id'] === $targetDonorId) {
                $scored[] = ['donor' => $donor, 'score' => max($score, (int) $donor['id'] === $targetDonorId ? 40 : $score)];
            }
        }
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $topMatches = array_slice($scored, 0, 10);

        // Ensure a directly-targeted donor is always included even if not in top 10
        if ($targetDonorId && !in_array($targetDonorId, array_column(array_column($topMatches, 'donor'), 'id'), true)) {
            $tStmt = $pdo->prepare("SELECT id, blood_group, division, district, availability, is_verified FROM users WHERE id = :id AND is_donor_profile = 1 LIMIT 1");
            $tStmt->execute([':id' => $targetDonorId]);
            if ($targetDonor = $tStmt->fetch()) {
                $topMatches[] = ['donor' => $targetDonor, 'score' => calculate_match_score($targetDonor, $requestRow)];
            }
        }

        $insertDr = $pdo->prepare(
            'INSERT INTO donor_requests (blood_request_id, donor_id, match_score, status) VALUES (:br, :donor, :score, "pending")'
        );
        foreach ($topMatches as $match) {
            $insertDr->execute([':br' => $requestId, ':donor' => $match['donor']['id'], ':score' => $match['score']]);
            $title = $requestType === 'emergency' ? '🚨 Emergency Blood Request' : 'New Blood Request';
            create_notification(
                $match['donor']['id'], 'blood_request', $title,
                "{$bloodGroup} blood is needed at {$hospitalName}. {$units} bag(s) required.",
                '/donor/requests.php'
            );
        }

        if ($topMatches) {
            $pdo->prepare("UPDATE blood_requests SET status = 'searching' WHERE id = :id")->execute([':id' => $requestId]);
        }

        flash_set('success', "Request Created Successfully! Your request ID is {$requestCode}. " . count($topMatches) . ' matching donor(s) notified.');
        redirect('seeker/my-requests.php');
    }
}

$districtOptions = !empty($_POST['division']) ? districts_for_division($_POST['division']) : [];
$areaOptions = !empty($_POST['district']) ? areas_for_district($_POST['district']) : [];

$targetDonor = null;
if ($presetDonorId) {
    $stmt = $pdo->prepare("SELECT id, full_name, blood_group FROM users WHERE id = :id AND is_donor_profile = 1 LIMIT 1");
    $stmt->execute([':id' => $presetDonorId]);
    $targetDonor = $stmt->fetch();
}

$pageTitle = 'Create Blood Request — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_sidebar.php';
?>
<div class="dash-wrap">
    <?php render_seeker_sidebar('create-request'); ?>
    <div class="dash-main">
        <h1>Create Blood Request</h1>
        <p class="dash-welcome">Fill in the details below. We'll notify matching donors automatically.</p>

        <?php if ($targetDonor): ?>
            <div class="disclaimer-box" style="background:var(--blue-50);color:#1846a3;">
                <i class="fa-solid fa-circle-info"></i>
                <span>This request will be sent directly to <strong><?= e($targetDonor['full_name']) ?></strong> (<?= e($targetDonor['blood_group']) ?>), in addition to other matching donors.</span>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:800px;">
            <?php if ($errors): ?>
                <div class="error-list"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" action="<?= e(SITE_URL) ?>/seeker/create-request.php">
                <?= csrf_field() ?>
                <?php if ($targetDonor): ?><input type="hidden" name="target_donor_id" value="<?= e($targetDonor['id']) ?>"><?php endif; ?>

                <div class="form-group form-full mb-2">
                    <label>Request Type</label>
                    <div class="role-toggle">
                        <label><input type="radio" name="request_type" value="normal" checked><span>Normal</span></label>
                        <label><input type="radio" name="request_type" value="emergency"><span>🔴 Emergency</span></label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Blood Group Needed</label>
                        <select name="blood_group" required>
                            <option value="">Select</option>
                            <?php foreach (blood_groups() as $bg): ?>
                                <option value="<?= e($bg) ?>" <?= ($targetDonor['blood_group'] ?? ($_POST['blood_group'] ?? '')) === $bg ? 'selected' : '' ?>><?= e($bg) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Units / Bags Needed</label>
                        <input type="number" name="units_needed" min="1" max="20" value="<?= e($_POST['units_needed'] ?? 1) ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Patient Name</label>
                        <input type="text" name="patient_name" value="<?= e($_POST['patient_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Hospital Name</label>
                        <input type="text" name="hospital_name" value="<?= e($_POST['hospital_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Division</label>
                        <select name="division" data-division data-target-district="r_district" required>
                            <option value="">Select</option>
                            <?php foreach (divisions() as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($_POST['division'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <select name="district" id="r_district" data-district data-target-area="r_area_list" data-selected="<?= e($_POST['district'] ?? '') ?>" required>
                            <option value="">Select Division First</option>
                            <?php foreach ($districtOptions as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($_POST['district'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label>Hospital Area (optional)</label>
                        <input type="text" name="area" id="r_area" list="r_area_list" value="<?= e($_POST['area'] ?? '') ?>" placeholder="Type the area" autocomplete="off">
                        <datalist id="r_area_list">
                            <?php foreach ($areaOptions as $a): ?>
                                <option value="<?= e($a) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Required Date</label>
                        <input type="date" name="required_date" min="<?= e(date('Y-m-d')) ?>" value="<?= e($_POST['required_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Required Time (optional)</label>
                        <input type="time" name="required_time" value="<?= e($_POST['required_time'] ?? '') ?>">
                    </div>
                    <div class="form-group form-full">
                        <label>Contact Number</label>
                        <input type="tel" name="contact_number" placeholder="01712345678" value="<?= e($_POST['contact_number'] ?? $user['phone']) ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Additional Information (optional)</label>
                        <textarea name="additional_info"><?= e($_POST['additional_info'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
