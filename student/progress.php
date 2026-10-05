<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "Learning Progress — DIGITAL SMART CLASS";

// Fetch student progress across enrolled courses
$coursesProgress = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.total_lessons, c.duration_hours,
                   (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.course_id = c.id AND lp.user_id = :uid AND lp.is_completed = 1) as completed_lessons
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = :uid AND e.status = 'active'
        ");
        $stmt->execute(['uid' => $user['id']]);
        $coursesProgress = $stmt->fetchAll();
    } catch (PDOException $e) {
        $coursesProgress = [];
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
            <li class="sidebar-item active"><a href="<?= site_url('student/progress.php'); ?>"><i class="fas fa-chart-line"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Learning Progress & Completion</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Monitor lesson completion and video watch percentages (90% threshold for completion).</p>
            </div>
        </div>

        <!-- Section 11 Video Progress Example Box -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; max-width: 750px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a;">
                    YouTube Shorts Creation — Lesson 5: Recording Shorts
                </h3>
                <span class="badge badge-active" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                    STATUS: COMPLETED
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.95rem; color: #475569;">
                <div>Video Duration: <strong>40 minutes</strong></div>
                <div>Watched: <strong>36 minutes</strong></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;">
                <span>Video Progress</span>
                <span style="color: var(--success);">90% (Threshold Met)</span>
            </div>
            <div class="progress-container">
                <div class="progress-bar-fill" style="width: 90%; background: #10b981;"></div>
            </div>
        </div>

        <!-- Course Progression Cards -->
        <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">All Enrolled Masterclasses</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php if (empty($coursesProgress)): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.75rem;">
                    <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">YouTube Shorts Creation — From Idea to Short</h4>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">10 of 12 Lessons Completed</p>
                    <div class="progress-container">
                        <div class="progress-bar-fill" style="width: 83%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-top: 0.5rem;">
                        <span style="font-weight: 700; color: var(--primary);">83% Completed</span>
                        <a href="<?= site_url('student/learning.php?course_id=2'); ?>" style="font-weight: 600;">Continue Learning &rarr;</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($coursesProgress as $cp): ?>
                    <?php 
                        $pct = $cp['total_lessons'] > 0 ? round(($cp['completed_lessons'] / $cp['total_lessons']) * 100) : 0;
                    ?>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.75rem;">
                        <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;"><?= e($cp['title']); ?></h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
                            <?= $cp['completed_lessons']; ?> of <?= $cp['total_lessons']; ?> Lessons Completed
                        </p>
                        <div class="progress-container">
                            <div class="progress-bar-fill" style="width: <?= $pct; ?>%;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-top: 0.5rem;">
                            <span style="font-weight: 700; color: var(--primary);"><?= $pct; ?>% Completed</span>
                            <a href="<?= site_url('student/learning.php?course_id=' . $cp['id']); ?>" style="font-weight: 600;">Open Lessons &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
