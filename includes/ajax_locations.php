<?php
/**
 * includes/ajax_locations.php
 * Returns JSON list of districts (for a division) or areas (for a district).
 * Used by assets/js/script.js to power dependent dropdowns.
 */
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';

if ($type === 'districts') {
    $division = clean($_GET['division'] ?? '');
    echo json_encode($division ? districts_for_division($division) : []);
    exit;
}

if ($type === 'areas') {
    $district = clean($_GET['district'] ?? '');
    echo json_encode($district ? areas_for_district($district) : []);
    exit;
}

echo json_encode([]);
