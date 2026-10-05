<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Manage Teachers — DIGITAL SMART CLASS";

$teachers = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM courses c WHERE c.teacher_id = u.id) as course_count
            FROM users u
            WHERE u.role_id = 2
            ORDER BY u.created_at DESC
        ");
        $teachers = $stmt->fetchAll();
    } catch (PDOException $e) {
        $teachers = [];
    }
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
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Manage Instructors</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Approved teachers with teaching privileges and course assignments.</p>
            </div>
            <a href="<?= site_url('admin/teacher-applications.php'); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-id-card"></i> Review Applications
            </a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Qualification & Experience</th>
                            <th>Courses Created</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td>
                                    <strong>David Mugisha</strong><br>
                                    <span style="font-size: 0.8rem; color: #64748b;">teacher@digitalsmart.rw</span>
                                </td>
                                <td>Certified YouTube Strategist (6 Years Experience)</td>
                                <td>3 Masterclasses</td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($t['full_name']); ?></strong><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= e($t['email']); ?></span>
                                    </td>
                                    <td><?= e($t['qualification'] ?? 'Instructor'); ?> (<?= e($t['experience'] ?? '3+ Years'); ?>)</td>
                                    <td><?= $t['course_count']; ?> Courses</td>
                                    <td><span class="badge badge-<?= $t['status']; ?>"><?= e(ucfirst($t['status'])); ?></span></td>
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
