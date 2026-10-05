<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');
$user = current_user();
$page_title = "Teacher Courses — Digital Smart Class";

$courses = [];
if ($db_connected && $pdo) {
    try {
        $cStmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = :tid ORDER BY created_at DESC");
        $cStmt->execute(['tid' => $user['id']]);
        $courses = $cStmt->fetchAll();
    } catch (PDOException $e) {
        $courses = [];
    }
}
if (empty($courses)) {
    $courses = get_fallback_courses();
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
            <li class="sidebar-item"><a href="<?= site_url('teacher/add-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Course Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Enrolled Students</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Manage Courses</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Review, edit, and organize lessons in your courses.</p>
            </div>
            <a href="<?= site_url('teacher/add-course.php'); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Course
            </a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Category</th>
                            <th>Level</th>
                            <th>Price (RWF)</th>
                            <th>Lessons</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--gray-900);">
                                    <?= e($c['title']); ?>
                                </td>
                                <td><?= e($c['category_name'] ?? 'Video Production'); ?></td>
                                <td><span class="badge" style="background: var(--gray-100);"><?= e($c['level']); ?></span></td>
                                <td><?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?></td>
                                <td><?= e($c['total_lessons']); ?> Lessons</td>
                                <td>
                                    <a href="<?= site_url('student/course-details.php?id=' . $c['id']); ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
