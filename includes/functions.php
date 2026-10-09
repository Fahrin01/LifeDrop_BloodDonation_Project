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
