<?php
/**
 * Shared Navbar Component
 * Supports Guest and Authenticated Role Views
 */
$currentUser = current_user();

// Unread notifications count if logged in
$unreadNotifications = 0;
if ($currentUser && $db_connected && $pdo) {
    try {
        $nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $nStmt->execute(['uid' => $currentUser['id']]);
        $unreadNotifications = $nStmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        $unreadNotifications = 0;
    }
}
?>
<nav class="navbar">
    <div class="container nav-container">
        <a href="<?= site_url('index.php'); ?>" class="brand-logo">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-text">DIGITAL<span>SMART</span>CLASS</div>
        </a>

        <ul class="nav-links">
            <li><a href="<?= site_url('index.php'); ?>" class="nav-link">Home</a></li>
            <li><a href="<?= site_url('courses.php'); ?>" class="nav-link">Courses</a></li>
            <li><a href="<?= site_url('about.php'); ?>" class="nav-link">About</a></li>
            <li><a href="<?= site_url('contact.php'); ?>" class="nav-link">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <?php if (is_logged_in()): ?>
                <!-- Notification Bell -->
                <a href="<?= site_url('student/notifications.php'); ?>" class="nav-icon-link" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?= $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>

                <?php 
                    $dashUrl = site_url('student/dashboard.php');
                    $roleLabel = 'Student';
                    if (has_role('admin')) { $dashUrl = site_url('admin/dashboard.php'); $roleLabel = 'Admin'; }
                    elseif (has_role('teacher')) { $dashUrl = site_url('teacher/dashboard.php'); $roleLabel = 'Teacher'; }
                    elseif (has_role('accountant')) { $dashUrl = site_url('accountant/dashboard.php'); $roleLabel = 'Accountant'; }
                ?>
                <a href="<?= $dashUrl; ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-user-circle"></i> <?= e($currentUser['full_name']); ?> (<?= $roleLabel; ?>)
                </a>
                <a href="<?= site_url('logout.php'); ?>" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?= site_url('login.php'); ?>" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= site_url('register.php'); ?>" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
