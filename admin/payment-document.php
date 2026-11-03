<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'accountant']);

$paymentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$paymentId || !$db_connected || !$pdo) {
    http_response_code(404);
    exit('Payment proof not found.');
}

try {
    $stmt = $pdo->prepare("SELECT proof_file FROM payments WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $paymentId]);
    $relativePath = $stmt->fetchColumn();
} catch (PDOException $e) {
    log_database_error('Load payment proof', $e);
    http_response_code(500);
    exit('Payment proof could not be loaded.');
}

if (
    !is_string($relativePath)
    || !preg_match('#^uploads/payments/[a-f0-9]{32}\.(pdf|jpg|jpeg|png|webp)$#', $relativePath)
) {
    http_response_code(404);
    exit('Payment proof not found.');
}

$uploadRoot = realpath(__DIR__ . '/../uploads/payments');
$filePath = realpath(__DIR__ . '/../' . $relativePath);
if (
    !$uploadRoot
    || !$filePath
    || strpos($filePath, $uploadRoot . DIRECTORY_SEPARATOR) !== 0
    || !is_file($filePath)
) {
    http_response_code(404);
    exit('Payment proof not found.');
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
$allowedMimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp'
];
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!isset($allowedMimeTypes[$extension]) || $mimeType !== $allowedMimeTypes[$extension]) {
    http_response_code(404);
    exit('Payment proof not found.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="payment-proof-' . $paymentId . '.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($filePath);
exit;
