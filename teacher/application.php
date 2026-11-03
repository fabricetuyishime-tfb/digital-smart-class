<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Apply to Become an Instructor — DIGITAL SMART CLASS";
$user = current_user();
$existingApplication = null;
$applicationLoadError = null;
$error = null;
$allowedApplicant = !$user || in_array($user['role'], ['student', 'teacher'], true);

if ($user && $user['role'] === 'teacher' && $db_connected && $pdo) {
    try {
        $statusStmt = $pdo->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $statusStmt->execute(['id' => $user['id']]);
        if ($statusStmt->fetchColumn() === 'active') {
            header("Location: " . site_url('teacher/dashboard.php'));
            exit;
        }
    } catch (PDOException $e) {
        log_database_error('Check teacher application access', $e);
        $applicationLoadError = 'Your application status could not be verified. Please try again later.';
    }
}

if ($user && $db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM teacher_applications WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $user['id']]);
        $existingApplication = $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        log_database_error('Load teacher application status', $e);
        $applicationLoadError = 'Your application status could not be loaded. Please try again later.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($user['full_name'] ?? ($_POST['full_name'] ?? ''));
    $email = trim($user['email'] ?? ($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and submit your application again.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Teacher applications are temporarily unavailable because the database is not connected. Please try again later.';
    } elseif (!$allowedApplicant) {
        $error = 'Only student accounts can apply to teach. Please sign in with a student account.';
    } elseif ($applicationLoadError) {
        $error = $applicationLoadError;
    } elseif ($existingApplication && in_array($existingApplication['status'], ['pending', 'approved'], true)) {
        $error = 'You already have an application under review or approved.';
    } elseif (empty($fullName) || empty($email) || empty($phone) || empty($qualification) || empty($experience)) {
        $error = 'Please fill in all mandatory application fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($fullName) > 120 || strlen($email) > 120 || strlen($phone) > 30 || strlen($qualification) > 200 || strlen($experience) > 100 || strlen($address) > 255) {
        $error = 'One or more fields exceed the allowed length. Please shorten your details and try again.';
    } elseif (!$user && (strlen($password) < 8 || $password !== ($_POST['confirm_password'] ?? ''))) {
        $error = strlen($password) < 8
            ? 'Please choose an account password with at least 8 characters.'
            : 'The password confirmation does not match.';
    } else {
        $savedFiles = [];
        $targetDir = __DIR__ . '/../uploads/teacher-documents/';
        $cleanupUploads = static function () use (&$savedFiles): void {
            foreach ($savedFiles as $savedFile) {
                if (is_file($savedFile)) {
                    unlink($savedFile);
                }
            }
        };
        $saveUpload = static function (string $field, array $allowedTypes) use ($targetDir, &$savedFiles): ?string {
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('Each document must be no larger than 10 MB and upload successfully.');
            }

            $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
            if (!isset($allowedTypes[$extension]) || !in_array($mimeType, $allowedTypes[$extension], true)) {
                throw new RuntimeException('Upload a valid PDF, Word document, or supported image for each document field.');
            }
            if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
                throw new RuntimeException('The document upload folder is unavailable. Please try again later.');
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $destination = $targetDir . $fileName;
            if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                throw new RuntimeException('A document could not be saved. Please try uploading it again.');
            }

            $savedFiles[] = $destination;
            return 'uploads/teacher-documents/' . $fileName;
        };

        try {
            $pdo->beginTransaction();
            $userId = $user['id'] ?? null;

            if (!$userId) {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $check->execute(['email' => $email]);
                if ($check->fetch()) {
                    throw new RuntimeException('An account already uses this email. Sign in first, then apply from your student account.');
                }

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
                $userId = (int)$pdo->lastInsertId();
            }

            $documentTypes = [
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp']
            ];
            $cvPath = $saveUpload('cv_file', array_intersect_key($documentTypes, array_flip(['pdf', 'doc', 'docx'])));
            $certPath = $saveUpload('certificate_file', $documentTypes);

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
            $pdo->commit();

            if (!$user) {
                login_user(['id' => $userId, 'full_name' => $fullName, 'email' => $email, 'role_name' => 'teacher', 'role_id' => 2]);
            }
            set_flash('success', 'Your teacher application has been submitted and is pending administrator review.');
            header("Location: " . site_url('teacher/application.php'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $cleanupUploads();
            log_database_error('Submit teacher application', $e);
            $error = $e->getCode() === '23000'
                ? 'An account already uses this email. Sign in first, then apply from your student account.'
                : 'Your application could not be submitted. Please try again.';
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $cleanupUploads();
            $error = $e->getMessage();
        }
    }
}

if (!$db_connected && !$error) {
    $error = 'Teacher applications are unavailable until the database connection is restored.';
}
$showApplicationForm = $allowedApplicant
    && !$applicationLoadError
    && (!$existingApplication || $existingApplication['status'] === 'rejected');

require_once __DIR__ . '/../includes/header.php';
?>

<section class="teacher-application-page">
    <div class="container teacher-application-container">
        <header class="teacher-application-heading">
            <span class="section-subtitle">Teach with Digital Smart Class</span>
            <h1>Share your skills. Help creators grow.</h1>
            <p>Apply to teach practical digital skills. Our team reviews each application before enabling instructor tools.</p>
        </header>

        <?php if ($applicationLoadError): ?>
            <div class="alert alert-danger" role="alert"><?= e($applicationLoadError); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <?php if ($user && $existingApplication): ?>
            <?php
                $applicationStatus = $existingApplication['status'];
                $statusIcon = $applicationStatus === 'approved' ? 'fa-circle-check' : ($applicationStatus === 'rejected' ? 'fa-circle-xmark' : 'fa-hourglass-half');
                $statusTitle = $applicationStatus === 'approved' ? 'Application approved' : ($applicationStatus === 'rejected' ? 'Application not approved' : 'Application under review');
            ?>
            <section class="teacher-application-status status-<?= e($applicationStatus); ?>" aria-live="polite">
                <span class="teacher-application-status-icon"><i class="fas <?= e($statusIcon); ?>" aria-hidden="true"></i></span>
                <div>
                    <span class="teacher-application-status-label">Application status: <?= e(ucfirst($applicationStatus)); ?></span>
                    <h2><?= e($statusTitle); ?></h2>
                    <?php if ($applicationStatus === 'pending'): ?>
                        <p>Your application is waiting for administrator review. You can sign in to check this page; instructor tools will be available after approval.</p>
                    <?php elseif ($applicationStatus === 'approved'): ?>
                        <p>Your application was approved. Sign in again to refresh your account permissions and open the teacher dashboard.</p>
                    <?php else: ?>
                        <p>You may update your details and submit a new application for review.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!$allowedApplicant): ?>
            <section class="teacher-application-card">
                <h2>Use a student account to apply</h2>
                <p>Teacher applications must be linked to a student account. Sign in as a student or create a student account before applying.</p>
                <a class="btn btn-primary" href="<?= site_url('login.php'); ?>">Sign in</a>
            </section>
        <?php elseif ($showApplicationForm): ?>
            <section class="teacher-application-card">
                <div class="teacher-application-card-heading">
                    <span class="feature-icon blue"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span>
                    <div>
                        <h2><?= $existingApplication ? 'Update your application' : 'Instructor application'; ?></h2>
                        <p>Fields marked with * are required. Your account stays limited until approval.</p>
                    </div>
                </div>

                <form action="<?= site_url('teacher/application.php'); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                    <div class="teacher-application-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required maxlength="120" autocomplete="name" value="<?= e($user['full_name'] ?? $_POST['full_name'] ?? ''); ?>" <?= $user ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email address *</label>
                            <input type="email" id="email" name="email" class="form-control" required maxlength="120" autocomplete="email" value="<?= e($user['email'] ?? $_POST['email'] ?? ''); ?>" <?= $user ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone / WhatsApp *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" required maxlength="30" autocomplete="tel" placeholder="+250 788 000 000" value="<?= e($_POST['phone'] ?? ''); ?>">
                        </div>
                        <?php if (!$user): ?>
                            <div class="form-group">
                                <label class="form-label" for="password">Create password *</label>
                                <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                                <span class="form-hint">Use at least 8 characters. You can check your application status after submitting.</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label" for="qualification">Qualification *</label>
                            <input type="text" id="qualification" name="qualification" class="form-control" required maxlength="200" placeholder="e.g. Video production diploma" value="<?= e($_POST['qualification'] ?? ($existingApplication['qualification'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="experience">Relevant experience *</label>
                            <input type="text" id="experience" name="experience" class="form-control" required maxlength="100" placeholder="e.g. 5 years creating YouTube content" value="<?= e($_POST['experience'] ?? ($existingApplication['experience'] ?? '')); ?>">
                        </div>
                        <div class="form-group teacher-application-form-wide">
                            <label class="form-label" for="address">Location</label>
                            <input type="text" id="address" name="address" class="form-control" maxlength="255" autocomplete="address-level2" placeholder="Kigali, Rwanda" value="<?= e($_POST['address'] ?? ($existingApplication['address'] ?? '')); ?>">
                        </div>
                        <div class="form-group teacher-application-form-wide">
                            <label class="form-label" for="bio">Bio and teaching plan</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4" placeholder="Describe the practical skills and courses you would like to teach."><?= e($_POST['bio'] ?? ($existingApplication['bio'] ?? '')); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cv_file">CV / resume</label>
                            <input type="file" id="cv_file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                            <span class="form-hint">PDF or Word document, up to 10 MB.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="certificate_file">Certificate / credentials</label>
                            <input type="file" id="certificate_file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <span class="form-hint">PDF or image, up to 10 MB.</span>
                        </div>
                    </div>

                    <div class="teacher-application-submit">
                        <p><i class="fas fa-shield-alt" aria-hidden="true"></i> Your documents are reviewed by administrators and are not publicly accessible.</p>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane" aria-hidden="true"></i> Submit application</button>
                    </div>
                </form>
            </section>
        <?php elseif (!$user && !$applicationLoadError): ?>
            <section class="teacher-application-card">
                <h2>Sign in to check your application</h2>
                <p>After you submit your application, you can sign in to see its review status.</p>
                <a class="btn btn-outline" href="<?= site_url('login.php'); ?>">Sign in</a>
            </section>
        <?php endif; ?>

        <p class="teacher-application-help">Need help? <a href="<?= site_url('contact.php'); ?>">Contact our support team</a>.</p>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
