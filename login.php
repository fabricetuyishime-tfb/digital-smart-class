<?php
$page_title = "Log In — Digital Smart Class";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if ($role === 'admin') header("Location: " . site_url('admin/dashboard.php'));
    elseif ($role === 'teacher') header("Location: " . site_url('teacher/dashboard.php'));
    elseif ($role === 'accountant') header("Location: " . site_url('accountant/dashboard.php'));
    else header("Location: " . site_url('student/dashboard.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } elseif ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is currently " . htmlspecialchars($user['status']) . ". Please contact support.";
                } else {
                    login_user($user);
                    set_flash('success', "Welcome back, " . $user['full_name'] . "!");
                    
                    if ($user['role_name'] === 'admin') {
                        header("Location: " . site_url('admin/dashboard.php'));
                    } elseif ($user['role_name'] === 'teacher') {
                        header("Location: " . site_url('teacher/dashboard.php'));
                    } elseif ($user['role_name'] === 'accountant') {
                        header("Location: " . site_url('accountant/dashboard.php'));
                    } else {
                        header("Location: " . site_url('student/dashboard.php'));
                    }
                    exit;
                }
            } else {
                $error = "Invalid email or password. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database authentication error: " . $e->getMessage();
        }
    } else {
        // Fallback demo login when database is not yet connected
        $demoUsers = [
            'erc@gmail.com' => ['id' => 1, 'full_name' => 'Demo Admin', 'email' => 'erc@gmail.com', 'role_name' => 'admin', 'role_id' => 1],
            'teacher@digitalsmart.rw' => ['id' => 2, 'full_name' => 'Demo Teacher', 'role_name' => 'teacher', 'role_id' => 2],
            'student@digitalsmart.rw' => ['id' => 3, 'full_name' => 'Demo Student', 'role_name' => 'student', 'role_id' => 3],
            'accountant@digitalsmart.rw' => ['id' => 4, 'full_name' => 'Demo Accountant', 'role_name' => 'accountant', 'role_id' => 4],
        ];

        if (isset($demoUsers[$email]) && $password === 'Password@123') {
            login_user($demoUsers[$email]);
            set_flash('success', "Logged in as " . $demoUsers[$email]['full_name'] . " (Demo Mode)!");
            $role = $demoUsers[$email]['role_name'];
            if ($role === 'admin') header("Location: " . site_url('admin/dashboard.php'));
            elseif ($role === 'teacher') header("Location: " . site_url('teacher/dashboard.php'));
            elseif ($role === 'accountant') header("Location: " . site_url('accountant/dashboard.php'));
            else header("Location: " . site_url('student/dashboard.php'));
            exit;
        } else {
            $error = "Invalid demo credentials. Use the Demo Quick-Fill options below.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="hero-tagline" style="margin-bottom: 1rem;">Secure access</div>
            <h2>Welcome back</h2>
            <p>Sign in to your Digital Smart Class dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('login.php'); ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="you@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                    <span style="font-size: 0.8rem; color: var(--gray-500);">Default: Password@123</span>
                </div>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fas fa-sign-in-alt"></i> Sign in
            </button>
        </form>

        <div class="demo-panel">
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gray-500); margin-bottom: 0.75rem; text-align: center;">
                Quick demo accounts
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="fillLogin('erc@gmail.com')">
                    👑 Admin
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="fillLogin('teacher@digitalsmart.rw')">
                    👨‍🏫 Teacher
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="fillLogin('student@digitalsmart.rw')">
                    🎓 Student
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="fillLogin('accountant@digitalsmart.rw')">
                    💰 Accountant
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            Don't have an account yet? <a href="<?= site_url('register.php'); ?>" style="font-weight: 600;">Sign up here</a>
        </div>
    </div>
</div>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'Password@123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
