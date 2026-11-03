<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "Student Dashboard — DIGITAL SMART CLASS";
$demoMode = !empty($_SESSION['demo_mode']);
$dashboardError = null;

// Fetch student metrics
$totalPlatformCourses = 3;
$enrolledCoursesCount = 0;
$completedCoursesCount = 0;
$activeCourse = null;
$recommendedCourses = [];
$announcements = [];

if ($db_connected && $pdo) {
    try {
        $totalPlatformCourses = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn();
        
        $eStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = :uid");
        $eStmt->execute(['uid' => $user['id']]);
        $enrolledCoursesCount = (int)$eStmt->fetchColumn();

        $completedStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = :uid AND status = 'completed'");
        $completedStmt->execute(['uid' => $user['id']]);
        $completedCoursesCount = (int)$completedStmt->fetchColumn();

        $actStmt = $pdo->prepare("
            SELECT c.*, e.status as enrollment_status,
                   (SELECT COUNT(*) FROM lesson_progress lp
                    WHERE lp.course_id = c.id AND lp.user_id = e.user_id AND lp.is_completed = 1) AS completed_lessons
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = :uid AND e.status = 'active'
            ORDER BY e.enrolled_at DESC
            LIMIT 1
        ");
        $actStmt->execute(['uid' => $user['id']]);
        $activeCourse = $actStmt->fetch();

        // Recommended courses (courses student not enrolled in)
        $recStmt = $pdo->prepare("
            SELECT * FROM courses 
            WHERE id NOT IN (SELECT course_id FROM enrollments WHERE user_id = :uid)
            AND status = 'published'
            LIMIT 3
        ");
        $recStmt->execute(['uid' => $user['id']]);
        $recommendedCourses = $recStmt->fetchAll();

        $announcementStmt = $pdo->prepare("
            SELECT id, title, message, link, is_read, created_at
            FROM notifications
            WHERE user_id = :uid
            ORDER BY is_read ASC, created_at DESC
            LIMIT 4
        ");
        $announcementStmt->execute(['uid' => $user['id']]);
        $announcements = $announcementStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load student dashboard', $e);
        $dashboardError = 'Some dashboard information could not be loaded. Please refresh the page.';
    }
}

if (empty($announcements) && $demoMode) {
    $announcements = [
        [
            'id' => 0,
            'title' => 'Welcome to your learning space',
            'message' => 'Explore your courses, continue your lessons, and check back here for important updates from your instructors.',
            'link' => 'student/courses.php',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 0,
            'title' => 'Keep your learning streak alive',
            'message' => 'Complete one lesson today and keep building your skills in YouTube, Shorts, and AI video creation.',
            'link' => 'student/progress.php',
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
}

if (!$db_connected && $demoMode && !$activeCourse) {
    $activeCourse = [
        'id' => 2,
        'title' => 'YouTube Shorts Creation — From Idea to Short',
        'progress_pct' => 80,
        'completed_lessons' => 8,
        'total_lessons' => 10
    ];
}
if (!$db_connected && $demoMode) {
    $recommendedCourses = array_slice(get_fallback_courses(), 0, 3);
}
$activeCourseProgress = $activeCourse
    ? min(100, (int)round(((int)($activeCourse['completed_lessons'] ?? 0) / max(1, (int)($activeCourse['total_lessons'] ?? 0))) * 100))
    : 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Student Sidebar (Matches Section 7 Mockup) -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Student</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item active">
                <a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Courses</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/progress.php'); ?>"><i class="fas fa-chart-line"></i> Progress</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a>
            </li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard-main">
        <?php if ($dashboardError): ?>
            <div class="alert alert-danger" role="alert"><?= e($dashboardError); ?></div>
        <?php endif; ?>
        <div class="dashboard-title-bar">
            <div>
                <h2>Welcome Back, <?= e(explode(' ', $user['full_name'])[0]); ?>!</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Track your enrolled courses, lesson progress, and payment approvals.</p>
            </div>
        </div>

        <div class="dashboard-grid-stats portal-stats-grid">
            <div class="stat-box">
                <div class="stat-box-title">Courses</div>
                <div class="stat-box-value"><?= $totalPlatformCourses; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Enrolled</div>
                <div class="stat-box-value" style="color: var(--primary);"><?= $enrolledCoursesCount; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Completed</div>
                <div class="stat-box-value" style="color: var(--success);"><?= $completedCoursesCount; ?></div>
            </div>
        </div>

        <section class="dashboard-promo-card" aria-label="Smart Class channel recovery campaign">
            <div class="dashboard-promo-copy">
                <span class="dashboard-promo-tag">Smart Class support</span>
                <h3>YOUTUBE CHANNEL DELETED?</h3>
                <p>We recover hacked, suspended, or deleted channels and protect your audience from future losses.</p>
                <div class="dashboard-promo-actions">
                    <a href="<?= site_url('contact.php'); ?>" class="btn btn-primary btn-sm">Get a free consultation</a>
                </div>
            </div>
            <div class="dashboard-promo-image">
                <img src="<?= site_url('assets/images/smart-class-channel-rescue.png'); ?>" alt="Smart Class advertisement for channel recovery">
            </div>
        </section>

        <section class="student-announcements" aria-labelledby="announcements-title">
            <div class="student-announcements-header">
                <div>
                    <span class="announcement-eyebrow"><i class="fas fa-sparkles"></i> Stay up to date</span>
                    <h3 id="announcements-title">Latest Announcements</h3>
                    <p>Important updates and helpful messages for your learning journey.</p>
                </div>
                <a href="<?= site_url('student/notifications.php'); ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-bell"></i> View all
                </a>
            </div>

            <div class="announcement-list">
                <?php if (empty($announcements)): ?>
                    <p class="dashboard-empty-state">You’re all caught up. Important course and platform updates will appear here.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="announcement-card <?= empty($announcement['is_read']) ? 'announcement-unread' : ''; ?>">
                        <div class="announcement-card-icon">
                            <i class="fas <?= empty($announcement['is_read']) ? 'fa-bullhorn' : 'fa-book-open'; ?>"></i>
                        </div>
                        <div class="announcement-card-content">
                            <div class="announcement-card-meta">
                                <span class="announcement-label"><?= empty($announcement['is_read']) ? 'New update' : 'Learning note'; ?></span>
                                <time datetime="<?= e($announcement['created_at']); ?>">
                                    <?= date('d M Y', strtotime($announcement['created_at'])); ?>
                                </time>
                            </div>
                            <h4><?= e($announcement['title']); ?></h4>
                            <p><?= e($announcement['message']); ?></p>
                            <?php if (!empty($announcement['link'])): ?>
                                <a href="<?= site_url($announcement['link']); ?>" class="announcement-link">
                                    Explore update <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="dashboard-content-section">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                Continue Learning
            </h3>

            <?php if ($activeCourse): ?>
                <div class="learning-card student-continue-card">
                    <h4><?= e($activeCourse['title']); ?></h4>
                    <div class="student-course-progress-label">
                        <span>Course progress</span>
                        <strong><?= $activeCourseProgress; ?>%</strong>
                    </div>
                    <div class="progress-container" role="progressbar" aria-label="Course progress" aria-valuenow="<?= $activeCourseProgress; ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar-fill" style="width: <?= $activeCourseProgress; ?>%;"></div>
                    </div>
                    <a href="<?= site_url('student/learning.php?course_id=' . (int)$activeCourse['id']); ?>" class="btn btn-primary">
                        <i class="fas fa-play"></i> Continue learning
                    </a>
                </div>
            <?php else: ?>
                <div class="dashboard-empty-state">
                    <h4>No active course yet</h4>
                    <p>Once a payment is approved, your course and lesson progress will appear here.</p>
                    <a href="<?= site_url('student/courses.php'); ?>" class="btn btn-outline">Browse courses</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-content-section">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                Recommended Courses
            </h3>

            <div class="student-recommended-grid">
                <?php if (empty($recommendedCourses)): ?>
                    <div class="dashboard-empty-state">
                        <p>You’re enrolled in every available course. Check back soon for new programs.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recommendedCourses as $course): ?>
                        <article class="stat-box student-recommended-card">
                            <div>
                                <span class="badge student-recommended-level"><?= e($course['level'] ?? 'All levels'); ?></span>
                                <h4><?= e($course['title']); ?></h4>
                                <p><?= e($course['total_lessons'] ?? 'Self-paced'); ?> lessons
                                    <?php if (!empty($course['duration_weeks'])): ?> · <?= e($course['duration_weeks']); ?><?php endif; ?>
                                </p>
                            </div>
                            <div class="student-recommended-footer">
                                <strong><?= format_money($course['price'] ?? 0, $course['currency'] ?? 'RWF'); ?></strong>
                                <a href="<?= site_url('student/course-details.php?id=' . (int)$course['id']); ?>" class="btn btn-sm btn-outline-primary">View course</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
