<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');
$user = current_user();
$page_title = "My Courses — Digital Smart Class";

$enrollments = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, c.title as course_title, c.subtitle, c.level, c.price, c.currency, c.duration_hours, c.total_lessons, c.thumbnail
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = :uid
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute(['uid' => $user['id']]);
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
            <div class="sidebar-user-avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Student</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Browse Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/payment.php'); ?>"><i class="fas fa-receipt"></i> Payment Records</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>My Enrolled Courses</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Access your active learning curriculum and video lessons.</p>
            </div>
        </div>

        <?php if (empty($enrollments)): ?>
            <div style="background: var(--white); padding: 3rem; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <i class="fas fa-graduation-cap" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem; display: block;"></i>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900);">No active courses found</h3>
                <p style="color: var(--gray-600); margin: 0.5rem 0 1.5rem;">Explore our courses and start building in-demand digital creation skills today.</p>
                <a href="<?= site_url('student/courses.php'); ?>" class="btn btn-primary">Browse Course Catalog</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                <?php foreach ($enrollments as $enr): ?>
                    <div style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="background: linear-gradient(135deg, var(--dark-surface), var(--dark)); color: white; padding: 1.5rem; text-align: center;">
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; margin-bottom: 0.5rem;"><?= e($enr['level']); ?></span>
                            <h4 style="font-size: 1.15rem; font-weight: 700;"><?= e($enr['course_title']); ?></h4>
                        </div>
                        <div style="padding: 1.5rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <p style="font-size: 0.85rem; color: var(--gray-600); margin-bottom: 1.25rem;">
                                <?= e($enr['subtitle']); ?>
                            </p>
                            
                            <div style="margin-bottom: 1.25rem;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.35rem;">
                                    <span>Course Status</span>
                                    <span>
                                        <?php if ($enr['status'] === 'active'): ?>
                                            <strong style="color: var(--success);"><i class="fas fa-check-circle"></i> Unlocked</strong>
                                        <?php else: ?>
                                            <strong style="color: var(--accent);"><i class="fas fa-clock"></i> Payment Pending</strong>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <?php if ($enr['status'] === 'active'): ?>
                                    <a href="<?= site_url('student/course-details.php?id=' . $enr['course_id']); ?>" class="btn btn-primary btn-block">
                                        <i class="fas fa-play"></i> Access Lessons
                                    </a>
                                <?php else: ?>
                                    <a href="<?= site_url('student/payment.php?course_id=' . $enr['course_id']); ?>" class="btn btn-outline btn-block">
                                        <i class="fas fa-receipt"></i> Verify Payment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
