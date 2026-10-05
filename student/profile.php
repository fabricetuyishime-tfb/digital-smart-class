<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');
$user = current_user();
$page_title = "Profile Settings — Digital Smart Class";

$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $newPass = $_POST['new_password'] ?? '';

    if (empty($fullName)) {
        $error = "Full Name cannot be empty.";
    } elseif ($db_connected && $pdo) {
        try {
            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    $error = "New password must be at least 6 characters.";
                } else {
                    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = :fn, phone = :ph, password = :pw WHERE id = :id");
                    $stmt->execute(['fn' => $fullName, 'ph' => $phone, 'pw' => $hashed, 'id' => $user['id']]);
                    $_SESSION['user_name'] = $fullName;
                    $msg = "Profile and password updated successfully!";
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = :fn, phone = :ph WHERE id = :id");
                $stmt->execute(['fn' => $fullName, 'ph' => $phone, 'id' => $user['id']]);
                $_SESSION['user_name'] = $fullName;
                $msg = "Profile updated successfully!";
            }
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
            <div class="sidebar-user-avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Student</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Browse Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('student/payment.php'); ?>"><i class="fas fa-receipt"></i> Payment Records</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Student Profile</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Manage your personal account settings and security.</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= e($msg); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 2rem; max-width: 600px;">
            <form action="<?= site_url('student/profile.php'); ?>" method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address (Read-only)</label>
                    <input type="text" class="form-control" value="<?= e($user['email']); ?>" disabled style="background: var(--gray-100);">
                </div>

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= e($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone / WhatsApp Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="+250 788 000 000">
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">Change Password (Leave blank to keep unchanged)</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-save"></i> Save Profile Changes
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
