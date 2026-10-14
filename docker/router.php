<?php
/**
 * Front controller / router for PHP's built-in server.
 *
 * Replaces the Apache .htaccess rules:
 *  - blocks direct access to config/, includes/, database/, docker/, uploads/
 *  - blocks source/backup/documentation files
 *  - serves static assets and executes application PHP pages
 */

$root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: __DIR__;
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = ltrim(urldecode($uri), '/');
$lower = strtolower($path);

// Directories that must never be served over HTTP.
$blockedPrefixes = [
    'config/', 'includes/', 'database/', 'docker/', 'uploads/',
    '.git/', '.github/', 'docs/',
];
foreach ($blockedPrefixes as $prefix) {
    if ($path === rtrim($prefix, '/') || strpos($path, $prefix) === 0) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return true;
    }
}

// File types that must never be served.
foreach (['.sql', '.md', '.env', '.log', '.lock', '.sh', '.bat', '.yml', '.yaml', '.ini'] as $ext) {
    if (substr($lower, -strlen($ext)) === $ext) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return true;
    }
}

// Home page.
if ($path === '' || $path === '/') {
    require $root . '/index.php';
    return true;
}

// Existing file: let the built-in server execute PHP or stream static content.
$file = realpath($root . '/' . $path);
if ($file && strpos($file, $root) === 0 && is_file($file)) {
    return false;
}

// Clean URL support: /courses -> /courses.php
if (is_file($root . '/' . $path . '.php')) {
    require $root . '/' . $path . '.php';
    return true;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><title>404</title><h1>404 — Page not found</h1>';
return true;
