<?php
/**
 * Digital Smart Class - Database Connection
 * Uses PDO for secure, prepared SQL execution.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_name = 'digital_smart_class';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

/**
 * Helper to get DB connection safely
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Base site URL configuration
 */
function site_url($path = '') {
    // Detect folder dynamically
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine relative directory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Strip subdirectories like /student, /admin, /teacher, /accountant
    $baseDir = preg_replace('/(\/(student|teacher|admin|accountant|config|includes|assets))$/', '', $scriptDir);
    $baseDir = rtrim($baseDir, '/');

    return $protocol . $host . $baseDir . '/' . ltrim($path, '/');
}
