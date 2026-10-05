<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Platform Notifications — DIGITAL SMART CLASS";

// Handle Broadcast Notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['message'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $targetRole = $_POST['target_role'] ?? 'all';

    if (!empty($title) && !empty($message) && $db_connected && $pdo) {
        try {
            $query = "INSERT INTO notifications (user_id, title, message) SELECT id, :title, :msg FROM users";
            if ($targetRole === 'students') $query .= " WHERE role_id = 3";
            elseif ($targetRole === 'teachers') $query .= " WHERE role_id = 2";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['title' => $title, 'msg' => $message]);
            set_flash('success', 'Broadcast notification dispatched successfully!');
            header("Location: " . site_url('admin/notifications.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
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
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Broadcast System Notifications</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Send alerts to students, teachers, or all platform users.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; max-width: 650px;">
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Notification Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. New YouTube Masterclass Live!">
                </div>

                <div class="form-group">
                    <label class="form-label">Audience</label>
                    <select name="target_role" class="form-control">
                        <option value="all">All Users (Students + Teachers + Staff)</option>
                        <option value="students">Students Only</option>
                        <option value="teachers">Teachers Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Message Body</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="Type announcement details..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
