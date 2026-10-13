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

/** Attempt login by email or phone + password. Returns true/false. */
function attempt_login($identifier, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE (email = :email OR phone = :phone) LIMIT 1');
    $stmt->execute([':email' => $identifier, ':phone' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    if ($user['status'] === 'suspended') {
        flash_set('danger', 'Your account has been suspended. Please contact support.');
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    return true;
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path']);
    }
    session_destroy();
}

/**
 * Register a new user. Returns [success(bool), errors(array), userId|null].
 */
function register_user(array $data) {
    global $pdo;
    $errors = [];

    $fullName   = clean($data['full_name'] ?? '');
    $email      = strtolower(trim($data['email'] ?? ''));
    $phone      = clean($data['phone'] ?? '');
    $password   = $data['password'] ?? '';
    $confirm    = $data['confirm_password'] ?? '';
    $accountType= $data['account_type'] ?? 'seeker';
    $bloodGroup = $data['blood_group'] ?? null;
    $division   = clean($data['division'] ?? '');
    $district   = clean($data['district'] ?? '');
    $area       = clean($data['area'] ?? '');
    $gender     = $data['gender'] ?? null;
    $dob        = $data['date_of_birth'] ?? null;
    $lastDonation = $data['last_donation_date'] ?? null;
    $availability = $data['availability'] ?? 'available';

    if ($fullName === '' || mb_strlen($fullName) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
        $errors[] = 'Please enter a valid Bangladeshi phone number (e.g. 01712345678).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($accountType, ['donor', 'seeker', 'both'], true)) {
        $errors[] = 'Invalid account type.';
    }
    if ($bloodGroup !== null && $bloodGroup !== '' && !in_array($bloodGroup, blood_groups(), true)) {
        $errors[] = 'Invalid blood group.';
    }
    if ($division !== '' && !in_array($division, divisions(), true)) {
        $errors[] = 'Invalid division.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e OR phone = :p');
        $stmt->execute([':e' => $email, ':p' => $phone]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email or phone number already exists.';
        }
    }

    if ($errors) {
        return [false, $errors, null];
    }

    $isDonorProfile = in_array($accountType, ['donor', 'both'], true) ? 1 : 0;

    $stmt = $pdo->prepare(
        'INSERT INTO users
        (full_name, email, phone, password_hash, role, blood_group, division, district, area,
         gender, date_of_birth, last_donation_date, availability, is_donor_profile)
        VALUES
        (:full_name, :email, :phone, :password_hash, :role, :blood_group, :division, :district, :area,
         :gender, :dob, :last_donation, :availability, :is_donor_profile)'
    );

    $stmt->execute([
        ':full_name'      => $fullName,
        ':email'          => $email,
        ':phone'          => $phone,
        ':password_hash'  => password_hash($password, PASSWORD_DEFAULT),
        ':role'           => $accountType,
        ':blood_group'    => $bloodGroup ?: null,
        ':division'       => $division ?: null,
        ':district'       => $district ?: null,
        ':area'           => $area ?: null,
        ':gender'         => $gender ?: null,
        ':dob'            => $dob ?: null,
        ':last_donation'  => ($isDonorProfile && $lastDonation) ? $lastDonation : null,
        ':availability'   => $isDonorProfile ? $availability : 'available',
        ':is_donor_profile'=> $isDonorProfile,
    ]);

    return [true, [], (int) $pdo->lastInsertId()];
}
