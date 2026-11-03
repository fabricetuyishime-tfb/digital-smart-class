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

if ($currentUser['role'] === 'teacher' && (!$db_connected || !$pdo)) {
    if (empty($_SESSION['demo_mode'])) {
        set_flash('error', 'Teacher access is temporarily unavailable because the database connection could not be verified.');
        header("Location: " . site_url('login.php'));
        exit;
    }
}

if ($currentUser['role'] === 'teacher' && $db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.status, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $currentUser['id']]);
        $account = $stmt->fetch();
        $currentScript = basename($_SERVER['SCRIPT_NAME']);

        if (!$account || $account['role_name'] !== 'teacher') {
            set_flash('error', 'This account does not have teacher access. Please sign in with an approved teacher account.');
            header("Location: " . site_url('index.php'));
            exit;
        } elseif ($account['status'] === 'suspended') {
            set_flash('error', 'Your teacher account is suspended. Please contact the administrator.');
            header("Location: " . site_url('index.php'));
            exit;
        } elseif ($account['status'] !== 'active' && $currentScript !== 'application.php') {
            set_flash('info', 'Your teacher application is awaiting approval. Instructor tools will be available after administrator review.');
            header("Location: " . site_url('teacher/application.php'));
            exit;
        }
    } catch (PDOException $e) {
        log_database_error('Verify teacher access', $e);
        if (basename($_SERVER['SCRIPT_NAME']) !== 'application.php') {
            set_flash('error', 'Teacher access could not be verified. Please try again later.');
            header("Location: " . site_url('teacher/application.php'));
            exit;
        }
    }
}
