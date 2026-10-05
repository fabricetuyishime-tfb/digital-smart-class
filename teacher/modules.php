<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 1;

// Handle Add Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_title'])) {
    $title = trim($_POST['module_title']);
    if (!empty($title) && $db_connected && $pdo) {
        $stmt = $pdo->prepare("INSERT INTO modules (course_id, title, order_num) VALUES (:cid, :title, 1)");
        $stmt->execute(['cid' => $courseId, 'title' => $title]);
        set_flash('success', 'New module added successfully!');
        header("Location: " . site_url('teacher/modules.php?course_id=' . $courseId));
        exit;
    }
}

// Fetch modules
$modules = [];
if ($db_connected && $pdo) {
    try {
        $mStmt = $pdo->prepare("
            SELECT m.*, (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.id) as lesson_count
            FROM modules m
            WHERE m.course_id = :cid
            ORDER BY m.order_num ASC
        ");
        $mStmt->execute(['cid' => $courseId]);
        $modules = $mStmt->fetchAll();
    } catch (PDOException $e) {
        $modules = [];
    }
}

$page_title = "Course Modules — DIGITAL SMART CLASS";
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
                <h2>Manage Course Modules</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Group your lessons into progressive learning chapters.</p>
            </div>
            <a href="<?= site_url('teacher/courses.php'); ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>

        <!-- Add Module Form -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; max-width: 600px;">
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 1rem;">Add New Module</h4>
            <form action="" method="POST" style="display: flex; gap: 0.75rem;">
                <input type="text" name="module_title" class="form-control" required placeholder="e.g. Module 4: Monetization & Channel Scaling">
                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                    <i class="fas fa-plus"></i> Add Module
                </button>
            </form>
        </div>

        <!-- Modules List -->
        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Module Title</th>
                            <th>Total Lessons</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($modules)): ?>
                            <tr>
                                <td style="font-weight: 600;">Module 1: YouTube Channel Architecture & Algorithms</td>
                                <td>4 Lessons</td>
                                <td>
                                    <a href="<?= site_url('teacher/lessons.php?module_id=1'); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-video"></i> Manage Lessons
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Module 2: Video Production & Thumbnail Packaging</td>
                                <td>4 Lessons</td>
                                <td>
                                    <a href="<?= site_url('teacher/lessons.php?module_id=2'); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-video"></i> Manage Lessons
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($modules as $m): ?>
                                <tr>
                                    <td style="font-weight: 700; color: #0f172a;"><?= e($m['title']); ?></td>
                                    <td><?= $m['lesson_count']; ?> Lessons</td>
                                    <td>
                                        <a href="<?= site_url('teacher/lessons.php?module_id=' . $m['id']); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-video"></i> Manage Lessons
                                        </a>
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
