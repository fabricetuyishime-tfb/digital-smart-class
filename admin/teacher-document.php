<?php
require_once __DIR__ . '/../includes/admin-auth.php';

$documentColumns = [
    'cv' => 'cv_file',
    'certificate' => 'certificate_file'
];
$applicationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$documentType = $_GET['document'] ?? '';

if (!$db_connected || !$pdo || !$applicationId || !isset($documentColumns[$documentType])) {
    http_response_code(404);
    exit('Document not found.');
}

try {
    $stmt = $pdo->prepare("SELECT {$documentColumns[$documentType]} FROM teacher_applications WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $applicationId]);
    $storedPath = $stmt->fetchColumn();
} catch (PDOException $e) {
    log_database_error('Load teacher application document', $e);
    http_response_code(500);
    exit('The document could not be loaded.');
}

$documentRoot = realpath(__DIR__ . '/../uploads/teacher-documents');
$documentPath = $storedPath ? realpath(__DIR__ . '/../' . ltrim($storedPath, '/\\')) : false;
$allowedPrefix = $documentRoot ? $documentRoot . DIRECTORY_SEPARATOR : '';
if (!$documentRoot || !$documentPath || !str_starts_with($documentPath, $allowedPrefix) || !is_file($documentPath)) {
    http_response_code(404);
    exit('Document not found.');
}

$extension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
if (!in_array($extension, $allowedExtensions, true)) {
    http_response_code(404);
    exit('Document not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="teacher-application-' . $documentType . '.' . $extension . '"');
header('Content-Length: ' . filesize($documentPath));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($documentPath);
