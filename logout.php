<?php
require_once __DIR__ . '/includes/auth.php';
logout_user();
session_start();
flash_set('success', 'You have been logged out successfully.');
redirect('index.php');
