<?php
/**
 * Student Authorization Guard
 * Ensures user is authenticated and possesses Student role
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    set_flash('error', 'Please log in to access the student portal.');
    header("Location: " . site_url('login.php'));
    exit;
}

$currentUser = current_user();
if ($currentUser['role'] !== 'student' && $currentUser['role'] !== 'admin') {
    set_flash('error', 'Access denied: This section is reserved for students.');
    header("Location: " . site_url('index.php'));
    exit;
}
