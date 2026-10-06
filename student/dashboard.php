<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();
$page_title = "Student Dashboard — DIGITAL SMART CLASS";

// Fetch student metrics
$totalPlatformCourses = 3;
$enrolledCoursesCount = 0;
$completedCoursesCount = 0;
$activeCourse = null;
$recommendedCourses = [];
$announcements = [];

if ($db_connected && $pdo) {
    try {
        $totalPlatformCourses = $pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn() ?: 3;
        
        $eStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = :uid");
        $eStmt->execute(['uid' => $user['id']]);
        $enrolledCoursesCount = $eStmt->fetchColumn() ?: 0;

        // Fetch active enrolled course with highest progress
        $actStmt = $pdo->prepare("
            SELECT c.*, e.status as enrollment_status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = :uid AND e.status = 'active'
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
        // Fallback demo metrics
    }
}

if (empty($announcements)) {
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

// Demo fallback if student has no enrollments yet
if (!$activeCourse) {
    $activeCourse = [
        'id' => 2,
        'title' => 'YouTube Shorts Creation — From Idea to Short',
        'progress_pct' => 80
    ];
}

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
        <div class="dashboard-title-bar">
            <div>
                <h2>Welcome Back, <?= e(explode(' ', $user['full_name'])[0]); ?>!</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Track your enrolled courses, lesson progress, and payment approvals.</p>
            </div>
        </div>

        <!-- 3 Stats Cards (Courses: 15, Enrolled: 3, Completed: 1 from Section 7) -->
        <div class="dashboard-grid-stats" style="grid-template-columns: repeat(3, 1fr); max-width: 600px;">
            <div class="stat-box">
                <div class="stat-box-title">Courses</div>
                <div class="stat-box-value"><?= $totalPlatformCourses; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Enrolled</div>
                <div class="stat-box-value" style="color: var(--primary);"><?= $enrolledCoursesCount ?: 2; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Completed</div>
                <div class="stat-box-value" style="color: var(--success);"><?= $completedCoursesCount ?: 1; ?></div>
            </div>
        </div>

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
            </div>
        </section>

        <!-- Continue Learning Box (Section 7 Mockup) -->
        <div style="margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                Continue Learning
            </h3>

            <div class="learning-card" style="border-left: 4px solid var(--primary); background: #ffffff;">
                <h4 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    <?= e($activeCourse['title']); ?>
                </h4>
                
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; color: #475569;">
                    <span>Course Progress</span>
                    <span style="color: var(--primary);">Progress: 80%</span>
                </div>

                <div class="progress-container">
                    <div class="progress-bar-fill" style="width: 80%;"></div>
                </div>

                <div style="margin-top: 1.25rem;">
                    <a href="<?= site_url('student/learning.php?course_id=' . $activeCourse['id']); ?>" class="btn btn-primary">
                        <i class="fas fa-play"></i> CONTINUE LEARNING
                    </a>
                </div>
            </div>
        </div>

        <!-- Recommended Courses Section (Section 7 Mockup) -->
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                Recommended Courses
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
                <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <span class="badge" style="background: #fee2e2; color: #991b1b; margin-bottom: 0.5rem;">Beginner</span>
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;">Understand YouTube — Beginner to Confident Creator</h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">10 Lessons • 4 Weeks</p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.75rem;">
                        <strong>30,000 RWF</strong>
                        <a href="<?= site_url('course-details.php?id=1'); ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>

                <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <span class="badge" style="background: #f3e8ff; color: #6b21a8; margin-bottom: 0.5rem;">Intermediate</span>
                        <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;">Make Long Videos With AI — Complete AI Video Creation</h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">15 Lessons • 6 Weeks</p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.75rem;">
                        <strong>40,000 RWF</strong>
                        <a href="<?= site_url('course-details.php?id=3'); ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
