<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$user = current_user();
$page_title = "Manage Courses — Digital Smart Class";

// Handle course deletion if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    if ($db_connected && $pdo) {
        try {
            $delStmt = $pdo->prepare("DELETE FROM courses WHERE id = :id");
            $delStmt->execute(['id' => $delId]);
            set_flash('success', "Course #{$delId} removed successfully.");
            header("Location: " . site_url('admin/courses.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('error', "Could not delete course: " . $e->getMessage());
        }
    }
}

$courses = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'active') as active_students
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            ORDER BY c.id ASC
        ");
        $courses = $stmt->fetchAll();
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
            <div class="sidebar-user-avatar" style="background: var(--dark-surface);"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Super Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Admin Overview</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-graduation-cap"></i> Manage Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments Verification</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Financial Reports</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Course Catalog Administration</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Add, edit pricing, update curricula, or publish courses.</p>
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
                            <th>ID</th>
                            <th>Course Title</th>
                            <th>Category</th>
                            <th>Level</th>
                            <th>Price (RWF)</th>
                            <th>Active Learners</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td>#<?= $c['id']; ?></td>
                                <td style="font-weight: 700; color: var(--gray-900);">
                                    <?= e($c['title']); ?>
                                </td>
                                <td><?= e($c['category_name'] ?? 'Production'); ?></td>
                                <td><span class="badge" style="background: var(--gray-100);"><?= e($c['level']); ?></span></td>
                                <td style="font-weight: 700;"><?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?></td>
                                <td><?= $c['active_students'] ?? 18; ?> Students</td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <a href="<?= site_url('student/course-details.php?id=' . $c['id']); ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="" method="POST" onsubmit="return confirm('Delete this course?')">
                                            <input type="hidden" name="delete_id" value="<?= $c['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
