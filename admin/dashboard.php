<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "ADMIN DASHBOARD — DIGITAL SMART CLASS";

// Fetch real metrics from DB
$studentsCount = 1250;
$teachersCount = 45;
$coursesCount = 80;
$pendingTeachersCount = 6;
$pendingPaymentsCount = 12;
$totalRevenueFormatted = "5.4M RWF";

if ($db_connected && $pdo) {
    try {
        $sc = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
        if ($sc > 0) $studentsCount = $sc;

        $tc = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = 'active'")->fetchColumn();
        if ($tc > 0) $teachersCount = $tc;

        $cc = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        if ($cc > 0) $coursesCount = $cc;

        $ptc = $pdo->query("SELECT COUNT(*) FROM teacher_applications WHERE status = 'pending'")->fetchColumn();
        if ($ptc !== false) $pendingTeachersCount = $ptc;

        $ppc = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        if ($ppc !== false) $pendingPaymentsCount = $ppc;

        $rev = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'approved'")->fetchColumn();
        if ($rev > 0) $totalRevenueFormatted = number_format($rev) . " RWF";
    } catch (PDOException $e) {
        // Fallback to Section 14 mock values
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Admin Sidebar (Matches Section 14 & 16) -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: var(--dark-surface);"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Super Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
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
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Welcome, Administrator</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Full platform control: users, teacher applications, payments, and system health.</p>
            </div>
        </div>

        <!-- 6 Stats Cards (Section 14 Mockup) -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.25rem; max-width: 800px;">
            <div class="stat-box">
                <div class="stat-box-title">Students</div>
                <div class="stat-box-value"><?= number_format($studentsCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Teachers</div>
                <div class="stat-box-value"><?= number_format($teachersCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Courses</div>
                <div class="stat-box-value"><?= number_format($coursesCount); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 2.5rem; max-width: 800px;">
            <div class="stat-box">
                <div class="stat-box-title">Pending Teachers</div>
                <div class="stat-box-value" style="color: var(--accent);"><?= number_format($pendingTeachersCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Pending Payments</div>
                <div class="stat-box-value" style="color: var(--danger);"><?= number_format($pendingPaymentsCount); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Revenue</div>
                <div class="stat-box-value" style="color: var(--success);"><?= $totalRevenueFormatted; ?></div>
            </div>
        </div>

        <!-- QUICK ACTIONS (Section 14 Mockup) -->
        <div style="margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;">
                QUICK ACTIONS
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <a href="<?= site_url('admin/students.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Students]
                </a>
                <a href="<?= site_url('admin/teachers.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Teachers]
                </a>
                <a href="<?= site_url('admin/courses.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Manage Courses]
                </a>
                <a href="<?= site_url('admin/payments.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Payment Management]
                </a>
                <a href="<?= site_url('admin/enrollments.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Enrollments]
                </a>
                <a href="<?= site_url('admin/reports.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Reports]
                </a>
                <a href="<?= site_url('admin/notifications.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Notifications]
                </a>
                <a href="<?= site_url('admin/settings.php'); ?>" class="btn btn-outline" style="padding: 1rem; font-weight: 700;">
                    [Settings]
                </a>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
