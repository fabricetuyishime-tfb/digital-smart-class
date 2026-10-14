<?php
/**
 * Lightweight health check for hosting platforms.
 * Always returns HTTP 200 while the PHP runtime boots, and reports DB status.
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$status = [
    'app'   => 'digital-smart-class',
    'php'   => PHP_VERSION,
    'time'  => gmdate('c'),
    'status' => $db_connected ? 'ok' : 'degraded',
    'database' => $db_connected ? 'connected' : 'unavailable',
];

http_response_code(200);
echo json_encode($status, JSON_PRETTY_PRINT);
