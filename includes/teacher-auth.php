<?php
/**
 * Teacher Authorization Guard
 * Ensures user is authenticated, has Teacher role, AND is Approved by Administrator
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    set_flash('error', 'Please log in to access the instructor portal.');
    header("Location: " . site_url('login.php'));
    exit;
}

$currentUser = current_user();
if ($currentUser['role'] !== 'teacher' && $currentUser['role'] !== 'admin') {
    set_flash('error', 'Access denied: Teacher privileges required.');
    header("Location: " . site_url('index.php'));
    exit;
}

// Check approval status in database
if ($currentUser['role'] === 'teacher' && $db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $currentUser['id']]);
        $status = $stmt->fetchColumn();

        if ($status === 'pending') {
            // Check if current page is already application.php
            $currentScript = basename($_SERVER['SCRIPT_NAME']);
            if ($currentScript !== 'application.php') {
                set_flash('info', 'Your teacher application is currently PENDING approval from the Administrator. You will receive teaching privileges once approved.');
                header("Location: " . site_url('teacher/application.php'));
                exit;
            }
        } elseif ($status === 'suspended' || $status === 'inactive') {
            set_flash('error', 'Your teacher account is currently suspended. Please contact the administrator.');
            header("Location: " . site_url('index.php'));
            exit;
        }
    } catch (PDOException $e) {
        // Continue if DB check fails
    }
}
