<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Manage Enrollments — DIGITAL SMART CLASS";

$enrollments = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT e.*, u.full_name as student_name, u.email as student_email, c.title as course_title, c.price, c.currency
            FROM enrollments e
            JOIN users u ON e.user_id = u.id
            JOIN courses c ON e.course_id = c.id
            ORDER BY e.enrolled_at DESC
        ");
        $enrollments = $stmt->fetchAll();
    } catch (PDOException $e) {
        $enrollments = [];
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
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
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
                <h2>Student Course Enrollments</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Track which students have active access to which courses.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Enrolled Course</th>
                            <th>Tuition Fee</th>
                            <th>Enrollment Date</th>
                            <th>Access Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrollments)): ?>
                            <tr>
                                <td><strong>Fabrice Learner</strong><br><span style="font-size: 0.8rem; color: #64748b;">student@digitalsmart.rw</span></td>
                                <td>YouTube Shorts Creation — From Idea to Short</td>
                                <td>30,000 RWF</td>
                                <td><?= date('d M Y'); ?></td>
                                <td><span class="badge badge-active"><i class="fas fa-check"></i> Active</span></td>
                            </tr>
                            <tr>
                                <td><strong>Fabrice Learner</strong><br><span style="font-size: 0.8rem; color: #64748b;">student@digitalsmart.rw</span></td>
                                <td>Understand YouTube — Beginner to Confident Creator</td>
                                <td>30,000 RWF</td>
                                <td><?= date('d M Y'); ?></td>
                                <td><span class="badge badge-pending"><i class="fas fa-clock"></i> Pending Payment</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrollments as $enr): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($enr['student_name']); ?></strong><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= e($enr['student_email']); ?></span>
                                    </td>
                                    <td style="font-weight: 700; color: #0f172a;"><?= e($enr['course_title']); ?></td>
                                    <td><?= format_money($enr['price'], $enr['currency'] ?? 'RWF'); ?></td>
                                    <td><?= date('d M Y', strtotime($enr['enrolled_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $enr['status'] === 'active' ? 'active' : 'pending'; ?>">
                                            <?= e(ucfirst($enr['status'])); ?>
                                        </span>
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
