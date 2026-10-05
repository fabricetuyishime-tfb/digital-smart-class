<?php
require_once __DIR__ . '/../includes/teacher-auth.php';
$user = current_user();
$page_title = "Instructor Profile — DIGITAL SMART CLASS";

$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $newPass = $_POST['new_password'] ?? '';

    if (empty($fullName)) {
        $error = "Full Name is required.";
    } elseif ($db_connected && $pdo) {
        try {
            if (!empty($newPass)) {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = :fn, phone = :ph, qualification = :qu, experience = :ex, password = :pw 
                    WHERE id = :id
                ");
                $stmt->execute(['fn' => $fullName, 'ph' => $phone, 'qu' => $qualification, 'ex' => $experience, 'pw' => $hashed, 'id' => $user['id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = :fn, phone = :ph, qualification = :qu, experience = :ex 
                    WHERE id = :id
                ");
                $stmt->execute(['fn' => $fullName, 'ph' => $phone, 'qu' => $qualification, 'ex' => $experience, 'id' => $user['id']]);
            }
            $_SESSION['user_name'] = $fullName;
            $msg = "Instructor profile updated successfully!";
        } catch (PDOException $e) {
            $error = "Update error: " . $e->getMessage();
        }
    } else {
        $msg = "Profile updated (Preview Mode)!";
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
            <li class="sidebar-item"><a href="<?= site_url('teacher/create-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Students</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Instructor Profile & Settings</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Update your credentials, biography, and security settings.</p>
            </div>
        </div>

        <?php if ($msg): ?><div class="alert alert-success"><?= e($msg); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; max-width: 650px;">
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Email (Account identifier)</label>
                    <input type="text" class="form-control" value="<?= e($user['email']); ?>" disabled style="background: var(--gray-100);">
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= e($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone & WhatsApp</label>
                    <input type="text" name="phone" class="form-control" value="+250 788 000 002">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="Certified YouTube Strategist">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Experience</label>
                        <input type="text" name="experience" class="form-control" value="6 Years Content Creation">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
