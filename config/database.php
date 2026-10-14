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

// Environment-aware configuration. Local XAMPP works with the defaults below.
// Hosted platforms can inject explicit DB_* variables, or provide Railway's
// MySQL plugin variables (MYSQLHOST/MYSQLPORT/MYSQLUSER/MYSQLPASSWORD/
// MYSQLDATABASE) or a connection string (MYSQL_URL / DATABASE_URL).
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT');

if ($db_host === false || $db_host === '') {
    $db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
    $parts = ($db_url && preg_match('#^mysql#i', $db_url)) ? parse_url($db_url) : false;

    if ($parts) {
        $db_host = $parts['host'] ?? null;
        $db_port = isset($parts['port']) ? (string) $parts['port'] : $db_port;
        $db_user = isset($parts['user']) ? urldecode($parts['user']) : $db_user;
        $db_pass = isset($parts['pass']) ? urldecode($parts['pass']) : $db_pass;
        if (!empty($parts['path'])) {
            $db_name = ltrim($parts['path'], '/');
        }
    } else {
        $db_host = getenv('MYSQLHOST');
        $db_port = getenv('MYSQLPORT') ?: $db_port;
        $db_user = getenv('MYSQLUSER');
        $db_pass = getenv('MYSQLPASSWORD');
        $db_name = getenv('MYSQLDATABASE') ?: $db_name;
    }
}

// Local development defaults (XAMPP).
$db_host = ($db_host === false || $db_host === '') ? 'localhost' : $db_host;
$db_name = ($db_name === false || $db_name === '') ? 'digital_smart_class' : $db_name;
$db_user = ($db_user === false || $db_user === '') ? 'root' : $db_user;
if ($db_pass === false) {
    $db_pass = ''; // Default XAMPP password is empty
}
$db_port = ($db_port === false || $db_port === '') ? '3306' : $db_port;

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
