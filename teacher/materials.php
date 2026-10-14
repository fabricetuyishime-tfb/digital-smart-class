<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();
$page_title = "Course Materials — Digital Smart Class";

if (!$db_connected || !$pdo) {
    http_response_code(503);
    exit('Course materials are unavailable while the database is disconnected. Start MySQL and try again.');
}

$isAdmin = ($user['role'] === 'admin');

// Load the courses this teacher can manage.
$myCourses = [];
try {
    if ($isAdmin) {
        $myCourses = $pdo->query("SELECT id, title FROM courses ORDER BY created_at DESC")->fetchAll();
    } else {
        $cStmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY created_at DESC");
        $cStmt->execute(['tid' => $user['id']]);
        $myCourses = $cStmt->fetchAll();
    }
} catch (PDOException $e) {
    log_database_error('Load teacher courses for materials', $e);
    $myCourses = [];
}

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (int)($myCourses[0]['id'] ?? 0);
$error = null;

// Verify the selected course belongs to this teacher (admins may manage any course).
$courseOwned = false;
foreach ($myCourses as $mc) {
    if ((int)$mc['id'] === $selectedCourseId) {
        $courseOwned = true;
        break;
    }
}
if (!$courseOwned) {
    $selectedCourseId = (int)($myCourses[0]['id'] ?? 0);
}

/** Human readable file size. */
function human_file_size($bytes) {
    $bytes = (int)$bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    $i = min($i, count($units) - 1);
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}

/** Map a file extension to the materials.file_type enum. */
function material_type_from_ext($ext) {
    $map = [
        'pdf' => 'pdf',
        'ppt' => 'presentation',
        'pptx' => 'presentation',
        'key' => 'presentation',
        'doc' => 'document',
        'docx' => 'document',
        'odt' => 'document',
        'zip' => 'zip',
        'rar' => 'zip',
        'txt' => 'notes',
        'md' => 'notes',
    ];
    return $map[$ext] ?? 'document';
}

// Handle upload / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'upload' && $selectedCourseId > 0) {
        $title = trim($_POST['title'] ?? '');
        $lessonId = isset($_POST['lesson_id']) && $_POST['lesson_id'] !== '' ? (int)$_POST['lesson_id'] : null;

        if ($title === '') {
            $error = 'Please give the material a title.';
        } elseif (empty($_FILES['material_file']['name']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please choose a file to upload (PDF, presentation, document, ZIP, or notes).';
        } else {
            $originalName = $_FILES['material_file']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'ppt', 'pptx', 'key', 'doc', 'docx', 'odt', 'zip', 'rar', 'txt', 'md'];

            if (!in_array($ext, $allowed, true)) {
                $error = 'Unsupported file type. Allowed: PDF, PPT/PPTX, DOC/DOCX, ZIP, TXT, MD.';
            } else {
                $fileType = material_type_from_ext($ext);
                $subDir = ($fileType === 'presentation') ? 'presentations' : 'documents';
                $targetDir = __DIR__ . '/../uploads/' . $subDir . '/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $storedName = 'mat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $targetDir . $storedName;

                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                    $relativePath = 'uploads/' . $subDir . '/' . $storedName;
                    $fileSize = human_file_size(filesize($targetPath));

                    try {
                        $ins = $pdo->prepare("
                            INSERT INTO materials (course_id, lesson_id, title, file_path, file_type, file_size)
                            VALUES (:cid, :lid, :title, :path, :type, :size)
                        ");
                        $ins->execute([
                            'cid' => $selectedCourseId,
                            'lid' => $lessonId ?: null,
                            'title' => $title,
                            'path' => $relativePath,
                            'type' => $fileType,
                            'size' => $fileSize,
                        ]);
                        set_flash('success', 'Material "' . $title . '" uploaded successfully!');
                        header('Location: ' . site_url('teacher/materials.php?course_id=' . $selectedCourseId));
                        exit;
                    } catch (PDOException $e) {
                        @unlink($targetPath);
                        log_database_error('Save course material', $e);
                        $error = 'The material could not be saved. Please try again.';
                    }
                } else {
                    $error = 'The file could not be uploaded. Check the file size and folder permissions.';
                }
            }
        }
    } elseif ($action === 'delete' && $selectedCourseId > 0) {
        $materialId = (int)($_POST['material_id'] ?? 0);
        try {
            $del = $pdo->prepare("SELECT file_path FROM materials WHERE id = :id AND course_id = :cid LIMIT 1");
            $del->execute(['id' => $materialId, 'cid' => $selectedCourseId]);
            $filePath = $del->fetchColumn();

            if ($filePath) {
                $d = $pdo->prepare("DELETE FROM materials WHERE id = :id AND course_id = :cid");
                $d->execute(['id' => $materialId, 'cid' => $selectedCourseId]);

                $abs = realpath(__DIR__ . '/../' . $filePath);
                $uploadsRoot = realpath(__DIR__ . '/../uploads');
                if ($abs && $uploadsRoot && strpos($abs, $uploadsRoot) === 0 && is_file($abs)) {
                    @unlink($abs);
                }
                set_flash('success', 'Material deleted.');
            } else {
                set_flash('error', 'Material not found.');
            }
            header('Location: ' . site_url('teacher/materials.php?course_id=' . $selectedCourseId));
            exit;
        } catch (PDOException $e) {
            log_database_error('Delete course material', $e);
            $error = 'The material could not be deleted. Please try again.';
        }
    }
}

// Lessons for the selected course (so materials can be attached to a lesson).
$courseLessons = [];
$materials = [];
if ($selectedCourseId > 0) {
    try {
        $lStmt = $pdo->prepare("
            SELECT l.id, l.title
            FROM lessons l
            JOIN modules m ON m.id = l.module_id
            WHERE m.course_id = :cid
            ORDER BY m.order_num ASC, l.order_num ASC
        ");
        $lStmt->execute(['cid' => $selectedCourseId]);
        $courseLessons = $lStmt->fetchAll();

        $matStmt = $pdo->prepare("
            SELECT mat.*, l.title AS lesson_title
            FROM materials mat
            LEFT JOIN lessons l ON l.id = mat.lesson_id
            WHERE mat.course_id = :cid
            ORDER BY mat.created_at DESC
        ");
        $matStmt->execute(['cid' => $selectedCourseId]);
        $materials = $matStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load course materials', $e);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #0284c7;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Instructor</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('teacher/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/create-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Course Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Course Materials</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Upload notes, PDFs, slides, and project files. Students unlock them after enrollment.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <?php if (empty($myCourses)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Create a course first, then come back to upload its materials.
                <a href="<?= site_url('teacher/add-course.php'); ?>" style="font-weight: 700;">Create a course</a>.
            </div>
        <?php else: ?>
            <div style="margin-bottom: 1.5rem;">
                <form action="<?= site_url('teacher/materials.php'); ?>" method="GET" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0; min-width: 320px;">
                        <label class="form-label">Select Course</label>
                        <select name="course_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($myCourses as $mc): ?>
                                <option value="<?= (int)$mc['id']; ?>" <?= (int)$mc['id'] === $selectedCourseId ? 'selected' : ''; ?>>
                                    <?= e($mc['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <noscript><button type="submit" class="btn btn-outline">Load</button></noscript>
                </form>
            </div>

            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; max-width: 780px;">
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 1.25rem;">Upload New Material</h4>
                <form action="<?= site_url('teacher/materials.php?course_id=' . $selectedCourseId); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label class="form-label">Material Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Module 1 Workbook & Exercises.pdf">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Attach to Lesson (optional)</label>
                            <select name="lesson_id" class="form-control">
                                <option value="">Whole course</option>
                                <?php foreach ($courseLessons as $cl): ?>
                                    <option value="<?= (int)$cl['id']; ?>"><?= e($cl['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File *</label>
                            <input type="file" name="material_file" class="form-control" required
                                   accept=".pdf,.ppt,.pptx,.key,.doc,.docx,.odt,.zip,.rar,.txt,.md">
                        </div>
                    </div>

                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">
                        Allowed: PDF, PPT/PPTX, DOC/DOCX, ZIP, TXT, MD. Students access files through the secure gateway.
                    </p>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Material
                    </button>
                </form>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Type</th>
                                <th>Attached To</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materials)): ?>
                                <tr><td colspan="5">No materials uploaded for this course yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($materials as $mat): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0f172a;"><?= e($mat['title']); ?></td>
                                        <td><span class="badge" style="background: var(--gray-100);"><?= e(ucfirst($mat['file_type'])); ?></span></td>
                                        <td><?= e($mat['lesson_title'] ?? 'Whole course'); ?></td>
                                        <td><?= e($mat['file_size']); ?></td>
                                        <td style="display: flex; gap: 0.35rem;">
                                            <a href="<?= e(site_url('student/file.php?material_id=' . (int)$mat['id'])); ?>" target="_blank" class="btn btn-sm btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form action="<?= site_url('teacher/materials.php?course_id=' . $selectedCourseId); ?>" method="POST" onsubmit="return confirm('Delete this material?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="material_id" value="<?= (int)$mat['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
