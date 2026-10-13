<?php
/**
 * includes/auth.php
 * Session-based authentication and role-based access control.
 */

require_once __DIR__ . '/functions.php';

/** Is anyone logged in? */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/** Current logged-in user's full row, or null. */
function current_user() {
    static $user = null;
    static $fetched = false;
    if ($fetched) {
        return $user;
    }
    $fetched = true;
    if (!is_logged_in()) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user && $user['status'] === 'suspended') {
        logout_user();
        return null;
    }
    return $user;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/** Force login; redirect to login page and remember intended destination. */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        flash_set('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

/**
 * Require one of the given roles. 'both' role users satisfy either
 * 'donor' or 'seeker' requirements.
 */
function require_role($roles) {
    require_login();
    $roles = (array) $roles;
    $userRole = current_user_role();
    $ok = in_array($userRole, $roles, true)
        || ($userRole === 'both' && (in_array('donor', $roles, true) || in_array('seeker', $roles, true)));
    if (!$ok) {
        http_response_code(403);
        flash_set('danger', 'You do not have permission to access that page.');
        redirect('index.php');
    }
}

function require_admin() {
    require_role('admin');
}