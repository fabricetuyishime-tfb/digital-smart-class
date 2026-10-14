<?php
/**
 * Secure File Gateway — Digital Smart Class
 * Serves uploaded lesson videos and course materials only after
 * authentication, enrollment, and payment verification.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = strtolower($_SESSION['user_role'] ?? '');
$isAdmin = ($userRole === 'admin');

$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$materialId = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;

$fileName = null;   // relative path under project root, e.g. uploads/videos/x.mp4
$courseId = null;
$isFreePreview = false;

if ($db_connected && $pdo) {
    try {
        if ($lessonId > 0) {
            $stmt = $pdo->prepare("
                SELECT l.video_url, l.is_free_preview, m.course_id
                FROM lessons l
                JOIN modules m ON m.id = l.module_id
                WHERE l.id = :lid
                LIMIT 1
            ");
            $stmt->execute(['lid' => $lessonId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['video_url'])) {
                $fileName = $row['video_url'];
                $courseId = (int)$row['course_id'];
                $isFreePreview = !empty($row['is_free_preview']);
            }
        } elseif ($materialId > 0) {
            $stmt = $pdo->prepare("
                SELECT file_path, course_id, lesson_id
                FROM materials
                WHERE id = :mid
                LIMIT 1
            ");
            $stmt->execute(['mid' => $materialId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['file_path'])) {
                $fileName = $row['file_path'];
                $courseId = (int)$row['course_id'];

                // Material inherits preview access from its lesson, if any.
                if (!empty($row['lesson_id'])) {
                    $pStmt = $pdo->prepare("SELECT is_free_preview FROM lessons WHERE id = :lid LIMIT 1");
                    $pStmt->execute(['lid' => (int)$row['lesson_id']]);
                    $isFreePreview = !empty($pStmt->fetchColumn());
                }
            }
        }
    } catch (PDOException $e) {
        log_database_error('Secure file access lookup', $e);
    }
} else {
    // Preview mode fallback: only hardcoded demo course 2 / lesson video is allowed.
    if ($lessonId > 0) {
        $fileName = 'uploads/videos/demo-lesson.mp4';
        $courseId = 2;
        $isFreePreview = true;
    }
}

if (!$fileName) {
    http_response_code(404);
    exit('File not found.');
}

// Normalize and protect against path traversal
$relative = str_replace('\\', '/', $fileName);
$relative = ltrim($relative, '/');
$fullPath = realpath(__DIR__ . '/../' . $relative);
$uploadsRoot = realpath(__DIR__ . '/../uploads');

if ($fullPath === false || $uploadsRoot === false
    || strpos($fullPath, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0
    || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

// Access decision
$allowed = false;
if ($isFreePreview) {
    $allowed = true;
} elseif ($isAdmin) {
    $allowed = true;
} elseif ($userId > 0) {
    // Verify student/admin session role
    if ($userRole === 'student' || $userRole === 'admin') {
        if ($courseId && $db_connected && $pdo) {
            try {
                $eStmt = $pdo->prepare("
                    SELECT status FROM enrollments
                    WHERE user_id = :uid AND course_id = :cid
                    LIMIT 1
                ");
                $eStmt->execute(['uid' => $userId, 'cid' => $courseId]);
                $status = $eStmt->fetchColumn();
                if (in_array($status, ['active', 'completed'], true)) {
                    $allowed = true;
                }
            } catch (PDOException $e) {
                $allowed = false;
            }
        }
    }
}

if (!$allowed) {
    if ($userId === 0) {
        header('Location: ' . site_url('login.php'));
        exit;
    }
    http_response_code(403);
    exit('Access denied: this content requires an active enrollment.');
}

// Determine MIME type
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'md' => 'text/markdown',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

$fileSize = filesize($fullPath);
$start = 0;
$end = $fileSize - 1;
$length = $fileSize;

if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=(\d*)-(\d*)/i', $_SERVER['HTTP_RANGE'], $matches)) {
        if ($matches[1] !== '') {
            $start = (int)$matches[1];
        }
        if ($matches[2] !== '') {
            $end = (int)$matches[2];
        }
        if ($start > $end || $start >= $fileSize) {
            http_response_code(416);
            header("Content-Range: bytes */{$fileSize}");
            exit;
        }
        $end = min($end, $fileSize - 1);
        $length = $end - $start + 1;
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
    }
} else {
    http_response_code(200);
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $length);
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Cache-Control: private, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

$fp = fopen($fullPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit('Unable to read file.');
}
fseek($fp, $start);
$chunkSize = 1024 * 1024;
$remaining = $length;
while ($remaining > 0 && !feof($fp)) {
    $read = min($chunkSize, $remaining);
    echo fread($fp, $read);
    $remaining -= $read;
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}
fclose($fp);
exit;
