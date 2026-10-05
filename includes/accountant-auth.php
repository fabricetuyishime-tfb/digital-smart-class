<?php
/**
 * Accountant Authorization Guard
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    set_flash('error', 'Please log in to access the financial portal.');
    header("Location: " . site_url('login.php'));
    exit;
}

$currentUser = current_user();
if ($currentUser['role'] !== 'accountant' && $currentUser['role'] !== 'admin') {
    set_flash('error', 'Unauthorized: Financial Accounting credentials required.');
    header("Location: " . site_url('index.php'));
    exit;
}
