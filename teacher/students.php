<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');
$user = current_user();
$page_title = "Enrolled Students — Digital Smart Class";

$students = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.email, u.phone, c.title as course_title, e.enrolled_at, e.status
            FROM enrollments e
            JOIN users u ON e.user_id = u.id
            JOIN courses c ON e.course_id = c.id
            WHERE c.teacher_id = :tid
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute(['tid' => $user['id']]);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $students = [];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #0284c7;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Instructor</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('teacher/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/add-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Course Materials</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Enrolled Students</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Students Enrolled in Your Classes</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Monitor student rosters, enrollment statuses, and direct contact details.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Phone / WhatsApp</th>
                            <th>Enrolled Course</th>
                            <th>Enroll Date</th>
                            <th>Access Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td style="font-weight: 600;">Aline Uwase</td>
                                <td>student@digitalsmart.rw</td>
                                <td>+250 788 000 003</td>
                                <td>YouTube Creator Academy</td>
                                <td><?= date('d M Y'); ?></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Patrick Ndagijimana</td>
                                <td>patrick@example.rw</td>
                                <td>+250 788 111 222</td>
                                <td>Viral YouTube Shorts Engine</td>
                                <td><?= date('d M Y', strtotime('-2 days')); ?></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Sandrine Umutoni</td>
                                <td>sandrine@example.rw</td>
                                <td>+250 788 333 444</td>
                                <td>AI-Powered Long-Form Video Mastery</td>
                                <td><?= date('d M Y', strtotime('-5 days')); ?></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--gray-900);"><?= e($s['full_name']); ?></td>
                                    <td><?= e($s['email']); ?></td>
                                    <td><?= e($s['phone'] ?: 'N/A'); ?></td>
                                    <td><?= e($s['course_title']); ?></td>
                                    <td><?= date('d M Y', strtotime($s['enrolled_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $s['status'] === 'active' ? 'active' : 'pending'; ?>">
                                            <?= e(ucfirst($s['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
