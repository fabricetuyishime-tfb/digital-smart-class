<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Apply to Become an Instructor — DIGITAL SMART CLASS";

// Check if user is logged in
$user = current_user();
$existingApplication = null;

if ($user && $db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $user['id']]);
        $existingApplication = $stmt->fetch();
    } catch (PDOException $e) {
        $existingApplication = null;
    }
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($fullName) || empty($email) || empty($phone) || empty($qualification)) {
        $error = "Please fill in all mandatory application fields.";
    } elseif ($db_connected && $pdo) {
        try {
            $userId = $user ? $user['id'] : null;

            // If guest applying, create teacher account in status 'pending'
            if (!$userId) {
                if (empty($password) || strlen($password) < 6) {
                    $error = "Please provide an account password (minimum 6 characters).";
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $insU = $pdo->prepare("
                        INSERT INTO users (role_id, full_name, email, phone, password, qualification, experience, address, status)
                        VALUES (2, :fn, :em, :ph, :pw, :qu, :ex, :ad, 'pending')
                    ");
                    $insU->execute([
                        'fn' => $fullName,
                        'em' => $email,
                        'ph' => $phone,
                        'pw' => $hashed,
                        'qu' => $qualification,
                        'ex' => $experience,
                        'ad' => $address
                    ]);
                    $userId = $pdo->lastInsertId();
                    login_user(['id' => $userId, 'full_name' => $fullName, 'email' => $email, 'role_name' => 'teacher', 'role_id' => 2]);
                }
            }

            if (!$error && $userId) {
                // Handle file uploads (CV, Certificate, Photo)
                $cvPath = null;
                $certPath = null;
                $photoPath = null;

                $targetDir = __DIR__ . '/../uploads/teacher-documents/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                if (!empty($_FILES['cv_file']['name'])) {
                    $cvName = 'cv_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['cv_file']['name']);
                    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $targetDir . $cvName)) {
                        $cvPath = 'uploads/teacher-documents/' . $cvName;
                    }
                }

                if (!empty($_FILES['certificate_file']['name'])) {
                    $certName = 'cert_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['certificate_file']['name']);
                    if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $targetDir . $certName)) {
                        $certPath = 'uploads/teacher-documents/' . $certName;
                    }
                }

                $insApp = $pdo->prepare("
                    INSERT INTO teacher_applications (user_id, full_name, email, phone, qualification, experience, address, cv_file, certificate_file, bio, status)
                    VALUES (:uid, :fn, :em, :ph, :qu, :ex, :ad, :cv, :cert, :bio, 'pending')
                ");
                $insApp->execute([
                    'uid' => $userId,
                    'fn' => $fullName,
                    'em' => $email,
                    'ph' => $phone,
                    'qu' => $qualification,
                    'ex' => $experience,
                    'ad' => $address,
                    'cv' => $cvPath,
                    'cert' => $certPath,
                    'bio' => $bio
                ]);

                set_flash('success', "Your teacher application has been submitted! It is currently PENDING review by the platform administrator.");
                header("Location: " . site_url('teacher/application.php'));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Submission error: " . $e->getMessage();
        }
    } else {
        set_flash('success', "Teacher application submitted in Preview Mode! Awaiting admin approval.");
        header("Location: " . site_url('teacher/application.php'));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 4rem; max-width: 800px;">
    <!-- If application exists and is pending -->
    <?php if ($existingApplication && $existingApplication['status'] === 'pending'): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; background: #fef3c7; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1.25rem;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
                Application Under Review
            </h2>
            <p style="font-size: 1rem; color: #64748b; max-width: 550px; margin: 0 auto 1.5rem;">
                Your teacher application is currently <strong>PENDING</strong> administrator review. Once approved, you will automatically receive teaching privileges to create courses and upload curricula.
            </p>
            <div class="badge badge-pending" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
                Status: Pending Admin Approval
            </div>
        </div>
    <?php endif; ?>

    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em;">
                CREATE TEACHER ACCOUNT
            </h1>
            <p style="color: #64748b; font-size: 0.95rem; margin-top: 0.25rem;">
                Share your digital skills. Teachers receive access only after administrator approval.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <form action="<?= site_url('teacher/application.php'); ?>" method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. David Mugisha" value="<?= e($user['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= e($user['email'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">Phone & WhatsApp *</label>
                    <input type="text" name="phone" class="form-control" required placeholder="+250 788 000 000">
                </div>

                <?php if (!$user): ?>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Address / Location</label>
                        <input type="text" name="address" class="form-control" placeholder="Kigali, Rwanda">
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">Qualification / Degree *</label>
                    <input type="text" name="qualification" class="form-control" required placeholder="e.g. Certified Video Editor / BSc Tech">
                </div>

                <div class="form-group">
                    <label class="form-label">Experience (Years & Field) *</label>
                    <input type="text" name="experience" class="form-control" required placeholder="e.g. 5 Years in YouTube & AI Video">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Instructor Bio & Teaching Plan</label>
                <textarea name="bio" class="form-control" rows="3" placeholder="Briefly describe what courses you plan to create and teach..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-top: 1rem;">
                <div class="form-group">
                    <label class="form-label">Upload CV / Resume (PDF)</label>
                    <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                </div>

                <div class="form-group">
                    <label class="form-label">Upload Certificate / Credentials (PDF or Image)</label>
                    <input type="file" name="certificate_file" class="form-control" accept=".pdf,image/*">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em;">
                [ SUBMIT APPLICATION ]
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
