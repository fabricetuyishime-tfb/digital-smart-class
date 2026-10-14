<?php
/**
 * Digital Smart Class - Database Connection
 * Uses PDO for secure, prepared SQL execution.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if ($secureCookie) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Environment-aware configuration. Local XAMPP works with the defaults below;
// hosted platforms (Vercel, Railway, etc.) can inject DB_* variables.
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'digital_smart_class';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS');
if ($db_pass === false) {
    $db_pass = ''; // Default XAMPP password is empty
}
$db_port = getenv('DB_PORT') ?: '3306';

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
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
    // Detect folder dynamically, including reverse proxies/load balancers
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine relative directory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Strip subdirectories like /student, /admin, /teacher, /accountant
    $baseDir = preg_replace('/(\/(student|teacher|admin|accountant|config|includes|assets))$/', '', $scriptDir);
    $baseDir = rtrim($baseDir, '/');

    return $protocol . $host . $baseDir . '/' . ltrim($path, '/');
}
