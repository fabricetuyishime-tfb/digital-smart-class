<?php
/**
 * Lightweight health check for hosting platforms.
 * Returns 200 when the application can boot; includes DB status.
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$status = [
    'app'  => 'digital-smart-class',
    'php'  => PHP_VERSION,
    'time' => gmdate('c'),
    'database' => $db_connected ? 'connected' : 'unavailable',
];

http_response_code($db_connected ? 200 : 503);
echo json_encode($status, JSON_PRETTY_PRINT);
