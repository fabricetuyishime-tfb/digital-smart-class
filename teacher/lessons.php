<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();

$moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 1;
$error = null;

if (!$db_connected || !$pdo) {
    http_response_code(503);
    exit('Lesson management is unavailable while the database is disconnected. Start MySQL and try again.');
}

if ($db_connected && $pdo) {
    try {
        $accessStmt = $pdo->prepare("SELECT m.id, m.course_id FROM modules m JOIN courses c ON c.id = m.course_id WHERE m.id = :mid AND (c.teacher_id = :tid OR :is_admin = 1) LIMIT 1");
        $accessStmt->execute([
            'mid' => $moduleId,
            'tid' => $user['id'],
            'is_admin' => $user['role'] === 'admin' ? 1 : 0,
        ]);
        $accessRow = $accessStmt->fetch();
        if (!$accessRow) {
            http_response_code(404);
            exit('Module not found.');
        }
        $courseId = (int)$accessRow['course_id'];
    } catch (PDOException $e) {
        log_database_error('Verify module ownership', $e);
        http_response_code(500);
        exit('Unable to load this module right now. Please try again later.');
    }
}

/** Keep courses.total_lessons in sync with the real number of lessons. */
function sync_course_lesson_count(PDO $pdo, $courseId) {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = :cid");
    $cnt->execute(['cid' => $courseId]);
    $total = (int)$cnt->fetchColumn();
    $up = $pdo->prepare("UPDATE courses SET total_lessons = :t WHERE id = :cid");
    $up->execute(['t' => $total, 'cid' => $courseId]);
}

// Handle Delete Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_lesson' && $db_connected && $pdo) {
    $lessonToDelete = (int)($_POST['lesson_id'] ?? 0);
    try {
        $find = $pdo->prepare("
            SELECT l.id, l.video_url
            FROM lessons l
            JOIN modules m ON m.id = l.module_id
            JOIN courses c ON c.id = m.course_id
            WHERE l.id = :lid AND l.module_id = :mid AND (c.teacher_id = :tid OR :is_admin = 1)
            LIMIT 1
        ");
        $find->execute([
            'lid' => $lessonToDelete,
            'mid' => $moduleId,
            'tid' => $user['id'],
            'is_admin' => $user['role'] === 'admin' ? 1 : 0,
        ]);
        $lessonRow = $find->fetch();

        if ($lessonRow) {
            $del = $pdo->prepare("DELETE FROM lessons WHERE id = :lid");
            $del->execute(['lid' => $lessonToDelete]);

            // Remove an uploaded video file if this lesson owned one.
            $videoUrl = (string)($lessonRow['video_url'] ?? '');
            if (preg_match('/\.(mp4|webm|ogg)$/i', $videoUrl)) {
                $abs = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', $videoUrl), '/'));
                $uploadsRoot = realpath(__DIR__ . '/../uploads');
                if ($abs && $uploadsRoot && strpos($abs, $uploadsRoot) === 0 && is_file($abs)) {
                    @unlink($abs);
                }
            }

            sync_course_lesson_count($pdo, $courseId);
            set_flash('success', 'Lesson deleted.');
        } else {
            set_flash('error', 'Lesson not found.');
        }
        header("Location: " . site_url('teacher/lessons.php?module_id=' . $moduleId));
        exit;
    } catch (PDOException $e) {
        log_database_error('Delete lesson', $e);
        $error = 'The lesson could not be deleted. Please try again.';
    }
}

// Handle Add Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_title'])) {
    $title = trim($_POST['lesson_title']);
    $videoUrl = trim($_POST['video_url'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 20);
    $content = trim($_POST['content'] ?? '');
    $isPreview = isset($_POST['is_free_preview']) ? 1 : 0;

    if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['mp4', 'webm', 'ogg'];
        $extension = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $error = 'Video files must be MP4, WebM, or OGG format.';
        } else {
            $targetDir = __DIR__ . '/../uploads/videos/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $videoName = 'lesson_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $targetDir . $videoName)) {
                $videoUrl = 'uploads/videos/' . $videoName;
            } else {
                $error = 'The video upload could not be saved.';
            }
        }
    }

    if (!$error && !empty($title) && $db_connected && $pdo) {
        try {
            $ordStmt = $pdo->prepare("SELECT COALESCE(MAX(order_num), 0) + 1 FROM lessons WHERE module_id = :mid");
            $ordStmt->execute(['mid' => $moduleId]);
            $nextOrder = (int)$ordStmt->fetchColumn();

            $stmt = $pdo->prepare("
            INSERT INTO lessons (module_id, title, content, video_url, duration_minutes, order_num, is_free_preview)
            VALUES (:mid, :title, :content, :url, :dur, :ord, :prev)
        ");
            $stmt->execute([
                'mid' => $moduleId,
                'title' => $title,
                'content' => $content,
                'url' => $videoUrl,
                'dur' => $duration,
                'ord' => $nextOrder,
                'prev' => $isPreview
            ]);
            sync_course_lesson_count($pdo, $courseId);
            set_flash('success', 'Lesson added to module successfully!');
            header("Location: " . site_url('teacher/lessons.php?module_id=' . $moduleId));
            exit;
        } catch (PDOException $e) {
            log_database_error('Create lesson', $e);
            $error = 'The lesson could not be saved. Please try again.';
        }
    }
}

// Fetch lessons
$lessons = [];
if ($db_connected && $pdo) {
    try {
        $lStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = :mid ORDER BY order_num ASC");
        $lStmt->execute(['mid' => $moduleId]);
        $lessons = $lStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load module lessons', $e);
        $lessons = [];
    }
}

$page_title = "Manage Lessons — DIGITAL SMART CLASS";
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
            <li class="sidebar-item active"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/create-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Manage Module Lessons</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Upload video links, lesson notes, and set free preview permissions.</p>
            </div>
            <a href="<?= site_url('teacher/courses.php'); ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>

        <!-- Add Lesson Form -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; max-width: 750px;">
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 1.25rem;">Add New Video Lesson</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Lesson Title *</label>
                    <input type="text" name="lesson_title" class="form-control" required placeholder="e.g. Lesson 5: Recording Shorts with Smartphone">
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Video URL (optional)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube-nocookie.com/embed/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="20">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Or Upload Video (optional)</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg">
                    <small style="color: #64748b;">Supported formats: MP4, WebM, OGG. Uploading a file replaces the URL above.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Lesson Notes & Text Content</label>
                    <textarea name="content" class="form-control" rows="3" placeholder="Key takeaways, formulas, code, or action items..."></textarea>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
                    <input type="checkbox" name="is_free_preview" id="is_free_preview" value="1">
                    <label for="is_free_preview" style="font-size: 0.9rem; color: #334155; font-weight: 600;">Allow Free Preview (Unregistered visitors can watch)</label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Save Lesson
                </button>
            </form>
        </div>

        <!-- Lessons Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Lesson Title</th>
                            <th>Duration</th>
                            <th>Video</th>
                            <th>Preview Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lessons)): ?>
                            <tr><td colspan="5">No lessons yet. Add a video lesson above.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $l): ?>
                                <?php
                                    $videoUrl = (string)($l['video_url'] ?? '');
                                    $isUploaded = (bool)preg_match('/\.(mp4|webm|ogg)$/i', $videoUrl);
                                ?>
                                <tr>
                                    <td style="font-weight: 700; color: #0f172a;"><?= e($l['title']); ?></td>
                                    <td><?= e($l['duration_minutes']); ?> mins</td>
                                    <td>
                                        <?php if ($videoUrl === ''): ?>
                                            <span style="font-size: 0.8rem; color: var(--gray-400);"><i class="fas fa-video-slash"></i> None</span>
                                        <?php elseif ($isUploaded): ?>
                                            <a href="<?= e(site_url('student/file.php?lesson_id=' . (int)$l['id'])); ?>" target="_blank" class="badge" style="background: #e0f2fe; color: #0369a1; text-decoration: none;">
                                                <i class="fas fa-file-video"></i> Uploaded
                                            </a>
                                        <?php else: ?>
                                            <span class="badge" style="background: #ede9fe; color: #6d28d9;"><i class="fas fa-link"></i> External</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($l['is_free_preview'])): ?>
                                            <span class="badge" style="background: #e0f2fe; color: #0369a1;">Free Preview</span>
                                        <?php else: ?>
                                            <span class="badge badge-active">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="<?= site_url('teacher/lessons.php?module_id=' . $moduleId); ?>" method="POST" onsubmit="return confirm('Delete this lesson?');">
                                            <input type="hidden" name="action" value="delete_lesson">
                                            <input type="hidden" name="lesson_id" value="<?= (int)$l['id']; ?>">
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
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
