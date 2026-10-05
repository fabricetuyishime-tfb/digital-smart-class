<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Platform Settings — DIGITAL SMART CLASS";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_flash('success', 'System settings saved successfully!');
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
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>System & Payment Gateway Settings</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Configure platform parameters, Mobile Money numbers, and completion rules.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.25rem; max-width: 750px;">
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Platform Name</label>
                    <input type="text" class="form-control" value="DIGITAL SMART CLASS">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Default Currency</label>
                        <input type="text" class="form-control" value="RWF">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Video Completion Threshold</label>
                        <input type="text" class="form-control" value="90% (Watched/Marked)">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">MTN Mobile Money Merchant Code</label>
                        <input type="text" class="form-control" value="*182*8*1*0788000001#">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Airtel Money Code</label>
                        <input type="text" class="form-control" value="0730000002">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bank of Kigali Account</label>
                    <input type="text" class="form-control" value="00045-01234567-89 (DIGITAL SMART CLASS LTD)">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-save"></i> Save Platform Settings
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
