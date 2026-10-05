<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();

$moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 1;

// Handle Add Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_title'])) {
    $title = trim($_POST['lesson_title']);
    $videoUrl = trim($_POST['video_url'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 20);
    $content = trim($_POST['content'] ?? '');
    $isPreview = isset($_POST['is_free_preview']) ? 1 : 0;

    if (!empty($title) && $db_connected && $pdo) {
        $stmt = $pdo->prepare("
            INSERT INTO lessons (module_id, title, content, video_url, duration_minutes, is_free_preview)
            VALUES (:mid, :title, :content, :url, :dur, :prev)
        ");
        $stmt->execute([
            'mid' => $moduleId,
            'title' => $title,
            'content' => $content,
            'url' => $videoUrl,
            'dur' => $duration,
            'prev' => $isPreview
        ]);
        set_flash('success', 'Lesson added to module successfully!');
        header("Location: " . site_url('teacher/lessons.php?module_id=' . $moduleId));
        exit;
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
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Lesson Title *</label>
                    <input type="text" name="lesson_title" class="form-control" required placeholder="e.g. Lesson 5: Recording Shorts with Smartphone">
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Video Embed URL (YouTube/Vimeo/MP4)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube-nocookie.com/embed/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" value="20">
                    </div>
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
                            <th>Preview Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lessons)): ?>
                            <tr>
                                <td>Lesson 1: Understanding Shorts Ecosystem</td>
                                <td>18 mins</td>
                                <td><span class="badge" style="background: #e0f2fe; color: #0369a1;">Free Preview</span></td>
                            </tr>
                            <tr>
                                <td>Lesson 2: 3-Second Retention Hooks</td>
                                <td>22 mins</td>
                                <td><span class="badge badge-active">Protected</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $l): ?>
                                <tr>
                                    <td style="font-weight: 700; color: #0f172a;"><?= e($l['title']); ?></td>
                                    <td><?= e($l['duration_minutes']); ?> mins</td>
                                    <td>
                                        <?php if (!empty($l['is_free_preview'])): ?>
                                            <span class="badge" style="background: #e0f2fe; color: #0369a1;">Free Preview</span>
                                        <?php else: ?>
                                            <span class="badge badge-active">Protected</span>
                                        <?php endif; ?>
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
