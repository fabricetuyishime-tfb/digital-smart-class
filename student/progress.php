<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "Learning Progress — DIGITAL SMART CLASS";

// Fetch student progress across enrolled courses
$coursesProgress = [];
$recentVideoProgress = [];
$progressError = null;
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.total_lessons, c.duration_hours, e.status,
                   (SELECT COUNT(*) FROM lesson_progress lp
                    WHERE lp.course_id = c.id AND lp.user_id = :progress_uid AND lp.is_completed = 1) as completed_lessons
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = :enrollment_uid AND e.status IN ('active', 'completed')
        ");
        $stmt->execute(['progress_uid' => $user['id'], 'enrollment_uid' => $user['id']]);
        $coursesProgress = $stmt->fetchAll();

        $videoStmt = $pdo->prepare("
            SELECT c.title AS course_title, l.title AS lesson_title,
                   vp.watch_percentage, vp.watched_seconds, vp.total_seconds, vp.is_completed, vp.updated_at
            FROM video_progress vp
            JOIN lessons l ON l.id = vp.lesson_id
            JOIN modules m ON m.id = l.module_id
            JOIN courses c ON c.id = m.course_id
            WHERE vp.user_id = :uid
            ORDER BY vp.updated_at DESC
            LIMIT 3
        ");
        $videoStmt->execute(['uid' => $user['id']]);
        $recentVideoProgress = $videoStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load student progress', $e);
        $progressError = 'Your learning progress could not be loaded. Please refresh the page.';
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
        <?php if ($progressError): ?>
            <div class="alert alert-danger" role="alert"><?= e($progressError); ?></div>
        <?php endif; ?>
        <div class="dashboard-title-bar">
            <div>
                <h2>Learning Progress & Completion</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Monitor lesson completion and video watch percentages (90% threshold for completion).</p>
            </div>
        </div>

        <section class="progress-activity-section" aria-labelledby="recent-video-title">
            <h3 id="recent-video-title">Recent video activity</h3>
            <?php if (empty($recentVideoProgress)): ?>
                <div class="dashboard-empty-state">Video watch progress will appear here as you study lessons.</div>
            <?php else: ?>
                <div class="progress-activity-list">
                    <?php foreach ($recentVideoProgress as $video): ?>
                        <?php $videoPercent = min(100, max(0, (int)round((float)$video['watch_percentage']))); ?>
                        <article class="progress-activity-card">
                            <div class="progress-activity-heading">
                                <div>
                                    <h4><?= e($video['lesson_title']); ?></h4>
                                    <p><?= e($video['course_title']); ?></p>
                                </div>
                                <span class="badge <?= !empty($video['is_completed']) ? 'badge-approved' : 'badge-pending'; ?>">
                                    <?= !empty($video['is_completed']) ? 'Completed' : 'In progress'; ?>
                                </span>
                            </div>
                            <div class="student-course-progress-label">
                                <span><?= (int)floor((int)$video['watched_seconds'] / 60); ?> minutes watched</span>
                                <strong><?= $videoPercent; ?>%</strong>
                            </div>
                            <div class="progress-container" role="progressbar" aria-label="Video watch progress" aria-valuenow="<?= $videoPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar-fill" style="width: <?= $videoPercent; ?>%;"></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="progress-courses-section">
            <h3>Course progress</h3>
            <div class="progress-course-grid">
            <?php if (empty($coursesProgress)): ?>
                <div class="dashboard-empty-state">
                    <h4>No course progress yet</h4>
                    <p>Enroll in a course to see lesson completion and progress here.</p>
                    <a href="<?= site_url('student/courses.php'); ?>" class="btn btn-outline">Browse courses</a>
                </div>
            <?php else: ?>
                <?php foreach ($coursesProgress as $cp): ?>
                    <?php $pct = (int)$cp['total_lessons'] > 0 ? min(100, (int)round(((int)$cp['completed_lessons'] / (int)$cp['total_lessons']) * 100)) : 0; ?>
                    <article class="progress-course-card">
                        <h4><?= e($cp['title']); ?></h4>
                        <p><?= (int)$cp['completed_lessons']; ?> of <?= (int)$cp['total_lessons']; ?> lessons completed</p>
                        <div class="progress-container" role="progressbar" aria-label="Course progress" aria-valuenow="<?= $pct; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar-fill" style="width: <?= $pct; ?>%;"></div>
                        </div>
                        <div class="progress-course-footer">
                            <span><?= $pct; ?>% complete · <?= e(ucfirst($cp['status'])); ?></span>
                            <a href="<?= site_url('student/learning.php?course_id=' . (int)$cp['id']); ?>">Open course</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
