<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$course = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = :id AND teacher_id = :tid LIMIT 1");
        $stmt->execute(['id' => $courseId, 'tid' => $user['id']]);
        $course = $stmt->fetch();
    } catch (PDOException $e) {
        $course = null;
    }
}
if (!$course) {
    $fallbackList = get_fallback_courses();
    $course = $fallbackList[0];
}

$page_title = "Manage Course: " . e($course['title']) . " — DIGITAL SMART CLASS";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 30000);
    $status = $_POST['status'] ?? 'published';

    if ($db_connected && $pdo) {
        try {
            $up = $pdo->prepare("
                UPDATE courses 
                SET title = :title, subtitle = :sub, description = :desc, price = :price, status = :st
                WHERE id = :id AND teacher_id = :tid
            ");
            $up->execute([
                'title' => $title,
                'sub' => $subtitle,
                'desc' => $desc,
                'price' => $price,
                'st' => $status,
                'id' => $courseId,
                'tid' => $user['id']
            ]);
            set_flash('success', 'Course details updated successfully!');
            header("Location: " . site_url('teacher/courses.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('error', 'Error updating course: ' . $e->getMessage());
        }
    } else {
        set_flash('success', 'Course updated (Preview Mode)!');
        header("Location: " . site_url('teacher/courses.php'));
        exit;
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
                <h2>Manage Course: <?= e($course['title']); ?></h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Update tuition pricing, course summary, or lesson content.</p>
            </div>
            <a href="<?= site_url('teacher/modules.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-list"></i> Curriculum Modules
            </a>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; max-width: 800px;">
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Course Title</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($course['title']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Course Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" required value="<?= e($course['subtitle']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5" required><?= e($course['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Tuition Price (RWF)</label>
                        <input type="number" name="price" class="form-control" required value="<?= (int)$course['price']; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="published" <?= $course['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="archived" <?= $course['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
