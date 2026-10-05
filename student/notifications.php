<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "Notifications — DIGITAL SMART CLASS";

$notifications = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->execute(['uid' => $user['id']]);
        $notifications = $stmt->fetchAll();
    } catch (PDOException $e) {
        $notifications = [];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Student</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/progress.php'); ?>"><i class="fas fa-chart-line"></i> Progress</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('student/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Your Notifications</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">System announcements, payment approval receipts, and instructor notes.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; max-width: 800px;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 36px; height: 36px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h5 style="font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Payment Approved: YouTube Shorts Creation</h5>
                    <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 0.35rem;">
                        Your MTN Mobile Money payment of 30,000 RWF has been verified and approved by the finance team. All lessons, notes, and downloadable assets are now unlocked.
                    </p>
                    <span style="font-size: 0.75rem; color: #94a3b8;"><?= date('d M Y, H:i'); ?></span>
                </div>
            </div>

            <div style="padding: 1.25rem 1.5rem; display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 36px; height: 36px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div>
                    <h5 style="font-weight: 700; color: #0f172a; margin-bottom: 0.25rem;">Welcome to Digital Smart Class</h5>
                    <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 0.35rem;">
                        Thank you for registering your student account. Discover our high-income video production and AI content creation curriculum.
                    </p>
                    <span style="font-size: 0.75rem; color: #94a3b8;"><?= date('d M Y', strtotime('-1 day')); ?></span>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
