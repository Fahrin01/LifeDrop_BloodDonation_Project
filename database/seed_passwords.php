<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Demo email => intended plain-text password
$demoAccounts = [
    'admin@bloodconnect.test'  => 'Admin@12345',
    'rahim@example.com'        => 'Donor@12345',
    'karim@example.com'        => 'Donor@12345',
    'farida@example.com'       => 'Donor@12345',
    'nayeem@example.com'       => 'Donor@12345',
    'sadia@example.com'        => 'Donor@12345',
    'tanvir@example.com'       => 'Donor@12345',
    'mitu@example.com'         => 'Donor@12345',
    'habib@example.com'        => 'Donor@12345',
    'runa@example.com'         => 'Donor@12345',
    'jahangir@example.com'     => 'Donor@12345',
    'anika@example.com'        => 'Seeker@12345',
    'shakil@example.com'       => 'Seeker@12345',
];

$updated = 0;
$errors  = [];

foreach ($demoAccounts as $email => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE email = :email');
        $stmt->execute([':hash' => $hash, ':email' => $email]);
        if ($stmt->rowCount() > 0) {
            $updated++;
        }
    } catch (PDOException $e) {
        $errors[] = $email . ': ' . $e->getMessage();
    }
}

$isCli = (php_sapi_name() === 'cli');
$selfDeleted = false;
if (empty($errors)) {
    // Remove this file so it can't be re-run accidentally.
    $selfDeleted = @unlink(__FILE__);
}

$output = "LifeDrop — Demo Password Seeding\n";
$output .= "----------------------------------------\n";
$output .= "Accounts updated: {$updated} / " . count($demoAccounts) . "\n";
if (!empty($errors)) {
    $output .= "Errors:\n - " . implode("\n - ", $errors) . "\n";
} else {
    $output .= "All demo accounts now use their documented passwords (see README.md).\n";
    $output .= $selfDeleted
        ? "This script has deleted itself for safety.\n"
        : "Please delete this file manually for safety.\n";
}

if ($isCli) {
    echo $output;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $output;
}
