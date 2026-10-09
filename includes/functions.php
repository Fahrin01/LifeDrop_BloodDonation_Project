<?php
/**
 * includes/functions.php
 * Shared helper functions used across the whole project.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/* -----------------------------------------------------------
 * General / security helpers
 * ----------------------------------------------------------- */

/** Escape output for safe HTML display (XSS protection). */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Trim + strip tags on user input (basic sanitisation). */
function clean($value) {
    return trim(strip_tags($value ?? ''));
}

/** Generate/verify CSRF token for forms. */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Security check failed. Please refresh the page and try again.');
    }
}

/** Flash messages stored in session, shown once. */
function flash_set($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get_all() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Redirect helper. */
function redirect($path) {
    header('Location: ' . SITE_URL . '/' . ltrim($path, '/'));
    exit;
}

/* -----------------------------------------------------------
 * Domain constants
 * ----------------------------------------------------------- */

function blood_groups() {
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

function divisions() {
    return ['Dhaka', 'Chattogram', 'Rajshahi', 'Khulna', 'Barishal', 'Sylhet', 'Rangpur', 'Mymensingh'];
}

/** Districts for a division, pulled from the locations table. */
function districts_for_division($division) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT DISTINCT district FROM locations WHERE division = :d ORDER BY district');
    $stmt->execute([':d' => $division]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Areas for a district, pulled from the locations table. */
function areas_for_district($district) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT DISTINCT area FROM locations WHERE district = :d ORDER BY area');
    $stmt->execute([':d' => $district]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


/* -----------------------------------------------------------
 * Blood compatibility (educational only)
 * ----------------------------------------------------------- */

/** Who a given blood group CAN DONATE TO. */
function compatible_recipients($bloodGroup) {
    $map = [
        'O-'  => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        'O+'  => ['A+', 'B+', 'AB+', 'O+'],
        'A-'  => ['A+', 'A-', 'AB+', 'AB-'],
        'A+'  => ['A+', 'AB+'],
        'B-'  => ['B+', 'B-', 'AB+', 'AB-'],
        'B+'  => ['B+', 'AB+'],
        'AB-' => ['AB+', 'AB-'],
        'AB+' => ['AB+'],
    ];
    return $map[$bloodGroup] ?? [];
}

/** Who a given blood group CAN RECEIVE FROM. */
function compatible_donors($bloodGroup) {
    $map = [
        'A+'  => ['A+', 'A-', 'O+', 'O-'],
        'A-'  => ['A-', 'O-'],
        'B+'  => ['B+', 'B-', 'O+', 'O-'],
        'B-'  => ['B-', 'O-'],
        'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        'AB-' => ['A-', 'B-', 'AB-', 'O-'],
        'O+'  => ['O+', 'O-'],
        'O-'  => ['O-'],
    ];
    return $map[$bloodGroup] ?? [];
}

/* -----------------------------------------------------------
 * Request code generator
 * ----------------------------------------------------------- */

function generate_request_code() {
    global $pdo;
    $year = date('Y');
    do {
        $number = random_int(100000, 999999);
        $code = "BC-{$year}-{$number}";
        $stmt = $pdo->prepare('SELECT id FROM blood_requests WHERE request_code = :c');
        $stmt->execute([':c' => $code]);
    } while ($stmt->fetch());
    return $code;
}

/* -----------------------------------------------------------
 * Simple rule-based donor matching score (NOT medical advice)
 * ----------------------------------------------------------- */

function calculate_match_score(array $donor, array $request) {
    $score = 0;

    // Blood group match (50 points) - exact match or compatible donor
    if ($donor['blood_group'] === $request['blood_group']) {
        $score += 50;
    } elseif (in_array($donor['blood_group'], compatible_donors($request['blood_group']), true)) {
        $score += 35;
    }

    // Location match (25 points)
    if (!empty($donor['district']) && $donor['district'] === $request['district']) {
        $score += 25;
    } elseif (!empty($donor['division']) && $donor['division'] === $request['division']) {
        $score += 12;
    }

    // Availability (15 points)
    if (($donor['availability'] ?? '') === 'available') {
        $score += 15;
    } elseif (($donor['availability'] ?? '') === 'maybe') {
        $score += 7;
    }

    // Verified (10 points)
    if (!empty($donor['is_verified'])) {
        $score += 10;
    }

    return min(100, $score);
}