<?php
$page_title = "Create Student Account — Digital Smart Class";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: " . site_url('student/dashboard.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($db_connected && $pdo) {
        try {
            // Check if email already registered
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                $error = "An account with this email address already exists. Please log in.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (role_id, full_name, email, phone, password, status)
                    VALUES (3, :full_name, :email, :phone, :password, 'active')
                ");
                $stmt->execute([
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $hashed
                ]);

                $newId = $pdo->lastInsertId();
                login_user([
                    'id' => $newId,
                    'full_name' => $full_name,
                    'email' => $email,
                    'role_name' => 'student',
                    'role_id' => 3
                ]);

                set_flash('success', "Welcome to Digital Smart Class, {$full_name}! Your student account has been created.");
                header("Location: " . site_url('student/dashboard.php'));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Registration error: " . $e->getMessage();
        }
    } else {
        // Preview mode registration
        login_user([
            'id' => rand(100, 999),
            'full_name' => $full_name,
            'email' => $email,
            'role_name' => 'student',
            'role_id' => 3
        ]);
        set_flash('success', "Account registered in Preview Mode! Welcome {$full_name}.");
        header("Location: " . site_url('student/dashboard.php'));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="hero-tagline" style="margin-bottom: 1rem;">New student</div>
            <h2>Create your account</h2>
            <p>Start learning in-demand digital and video creation skills</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('register.php'); ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name *</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required placeholder="e.g. Jean Eric Ndayisaba" value="<?= e($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="e.g. eric@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone / WhatsApp Number (for MoMo payments)</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="+250 788 000 000" value="<?= e($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (Minimum 6 characters) *</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password *</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fas fa-user-plus"></i> Create Student Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            Already have an account? <a href="<?= site_url('login.php'); ?>" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
