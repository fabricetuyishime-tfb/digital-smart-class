<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');
$user = current_user();
$page_title = "Course Announcements — Digital Smart Class";
$error = null;
$courses = [];

if ($db_connected && $pdo) {
    $courseStmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title");
    $courseStmt->execute(['tid' => $user['id']]);
    $courses = $courseStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected && $pdo) {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$courseId || $title === '' || $message === '') {
        $error = 'Choose a course and provide both a title and message.';
    } else {
        $courseStmt = $pdo->prepare("SELECT title FROM courses WHERE id = :cid AND teacher_id = :tid");
        $courseStmt->execute(['cid' => $courseId, 'tid' => $user['id']]);
        $course = $courseStmt->fetch();

        if (!$course) {
            $error = 'You can only announce updates for your own courses.';
        } else {
            $recipientStmt = $pdo->prepare("
                SELECT DISTINCT user_id FROM enrollments
                WHERE course_id = :cid AND status IN ('active', 'completed')
            ");
            $recipientStmt->execute(['cid' => $courseId]);
            $insert = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link)
                VALUES (:uid, :title, :message, :link)
            ");
            $recipients = $recipientStmt->fetchAll();
            foreach ($recipients as $recipient) {
                $insert->execute([
                    'uid' => $recipient['user_id'],
                    'title' => $title,
                    'message' => $message,
                    'link' => 'student/notifications.php'
                ]);
            }
            set_flash('success', 'Announcement sent to ' . count($recipients) . ' enrolled student(s).');
            header('Location: ' . site_url('teacher/announcements.php'));
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #0284c7;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="sidebar-user-info"><h5><?= e($user['full_name']); ?></h5><span>Instructor</span></div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('teacher/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/announcements.php'); ?>"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Course Announcements</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Send lesson updates and important messages to enrolled students.</p>
            </div>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>
        <div style="background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 2rem; max-width: 700px;">
            <?php if (empty($courses)): ?>
                <div class="alert alert-info">Create a course before sending an announcement.</div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="course_id">Course</label>
                        <select name="course_id" id="course_id" class="form-control" required>
                            <option value="">Select your course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int)$course['id']; ?>"><?= e($course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="title">Announcement title</label>
                        <input type="text" name="title" id="title" class="form-control" maxlength="150" required placeholder="New lesson available">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="message">Message</label>
                        <textarea name="message" id="message" class="form-control" rows="5" required placeholder="Tell students what changed..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Announcement</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
