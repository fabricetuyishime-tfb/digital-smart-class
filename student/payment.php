<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');
$user = current_user();
$page_title = "Payment & Verification — Digital Smart Class";

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 1;
$error = null;
$historyError = null;
$paymentUnavailable = null;

$courses = [];
if ($db_connected && $pdo) {
    try {
        $cStmt = $pdo->query("SELECT id, title, price, currency FROM courses WHERE status = 'published'");
        $courses = $cStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load payment course options', $e);
        $error = 'Course payment options could not be loaded. Please try again later.';
    }
} else {
    $paymentUnavailable = 'Payment submission is temporarily unavailable because the course database is not connected. No payment will be recorded in preview mode.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT);
    $method = trim($_POST['payment_method'] ?? '');
    $txRef = trim($_POST['transaction_ref'] ?? '');
    $selectedCourseId = $courseId ?: $selectedCourseId;
    $allowedMethods = ['MTN Mobile Money', 'Airtel Money', 'Bank Transfer', 'Cash'];

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and submit your payment again.';
    } elseif (!$db_connected || !$pdo) {
        $error = 'Payment submissions require a live database connection. No payment record was created.';
    } elseif (!$courseId || $courseId < 1) {
        $error = 'Choose a valid course before submitting payment.';
    } elseif (!in_array($method, $allowedMethods, true)) {
        $error = 'Choose a valid payment method.';
    } elseif ($txRef === '' || strlen($txRef) > 100) {
        $error = 'Enter a transaction reference between 1 and 100 characters.';
    } elseif ($error === null) {
        $savedProof = null;
        try {
            $pdo->beginTransaction();
            $courseStmt = $pdo->prepare("SELECT price, currency FROM courses WHERE id = :id AND status = 'published' LIMIT 1");
            $courseStmt->execute(['id' => $courseId]);
            $course = $courseStmt->fetch();
            if (!$course) {
                throw new RuntimeException('This course is no longer available for purchase.');
            }

            $existingEnrollmentStmt = $pdo->prepare("
                SELECT id, status FROM enrollments
                WHERE user_id = :user_id AND course_id = :course_id
                LIMIT 1 FOR UPDATE
            ");
            $existingEnrollmentStmt->execute(['user_id' => $user['id'], 'course_id' => $courseId]);
            $existingEnrollment = $existingEnrollmentStmt->fetch();
            if ($existingEnrollment && in_array($existingEnrollment['status'], ['active', 'completed'], true)) {
                throw new RuntimeException('You already have access to this course.');
            }

            $pendingPaymentStmt = $pdo->prepare("
                SELECT id FROM payments
                WHERE user_id = :user_id AND course_id = :course_id AND status = 'pending'
                LIMIT 1 FOR UPDATE
            ");
            $pendingPaymentStmt->execute(['user_id' => $user['id'], 'course_id' => $courseId]);
            if ($pendingPaymentStmt->fetch()) {
                throw new RuntimeException('A payment for this course is already awaiting review. Check your payment history for its status.');
            }

            $duplicateReferenceStmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_ref = :transaction_ref LIMIT 1");
            $duplicateReferenceStmt->execute(['transaction_ref' => $txRef]);
            if ($duplicateReferenceStmt->fetch()) {
                throw new RuntimeException('This transaction reference has already been submitted. Check the reference and try again.');
            }

            $proofPath = null;
            if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK || $_FILES['proof_file']['size'] > 10 * 1024 * 1024) {
                    throw new RuntimeException('The payment proof must upload successfully and be no larger than 10 MB.');
                }
                $extension = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['proof_file']['tmp_name']);
                $proofTypes = [
                    'pdf' => ['application/pdf'],
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'webp' => ['image/webp']
                ];
                if (!isset($proofTypes[$extension]) || !in_array($mimeType, $proofTypes[$extension], true)) {
                    throw new RuntimeException('Upload payment proof as a PDF, JPG, PNG, or WebP image.');
                }

                $targetDir = __DIR__ . '/../uploads/payments/';
                if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
                    throw new RuntimeException('The payment proof folder is unavailable. Please try again later.');
                }
                $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                $savedProof = $targetDir . $fileName;
                if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $savedProof)) {
                    $savedProof = null;
                    throw new RuntimeException('The payment proof could not be saved. Please try uploading it again.');
                }
                $proofPath = 'uploads/payments/' . $fileName;
            }

            $enr = $pdo->prepare("
                INSERT INTO enrollments (user_id, course_id, status)
                VALUES (:uid, :cid, 'pending')
                ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), status = IF(status = 'cancelled', 'pending', status)
            ");
            $enr->execute(['uid' => $user['id'], 'cid' => $courseId]);
            $enrollmentId = (int)$pdo->lastInsertId();

            $payStmt = $pdo->prepare("
                INSERT INTO payments (enrollment_id, user_id, course_id, amount, currency, payment_method, transaction_ref, proof_file, status)
                VALUES (:eid, :uid, :cid, :amount, :currency, :method, :txref, :proof, 'pending')
            ");
            $payStmt->execute([
                'eid' => $enrollmentId ?: null,
                'uid' => $user['id'],
                'cid' => $courseId,
                'amount' => $course['price'],
                'currency' => $course['currency'] ?: 'RWF',
                'method' => $method,
                'txref' => $txRef,
                'proof' => $proofPath
            ]);
            $pdo->commit();

            set_flash('success', 'Payment receipt submitted successfully. It is now awaiting finance-team verification.');
            header("Location: " . site_url('student/payment.php?course_id=' . $courseId));
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($savedProof && is_file($savedProof)) {
                unlink($savedProof);
            }
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($savedProof && is_file($savedProof)) {
                unlink($savedProof);
            }
            log_database_error('Submit payment', $e);
            $error = 'Your payment details could not be submitted. Please try again.';
        }
    }
}

$myPayments = [];
if ($db_connected && $pdo) {
    try {
        $pStmt = $pdo->prepare("
            SELECT p.*, c.title as course_title
            FROM payments p
            JOIN courses c ON p.course_id = c.id
            WHERE p.user_id = :uid
            ORDER BY p.created_at DESC
        ");
        $pStmt->execute(['uid' => $user['id']]);
        $myPayments = $pStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load student payment history', $e);
        $historyError = 'Payment history could not be loaded. Please refresh the page.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
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
            <li class="sidebar-item">
                <a href="<?= site_url('student/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/courses.php'); ?>"><i class="fas fa-compass"></i> Browse Courses</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/my-courses.php'); ?>"><i class="fas fa-play-circle"></i> My Courses</a>
            </li>
            <li class="sidebar-item active">
                <a href="<?= site_url('student/payment.php'); ?>"><i class="fas fa-receipt"></i> Payment Records</a>
            </li>
            <li class="sidebar-item">
                <a href="<?= site_url('student/profile.php'); ?>"><i class="fas fa-user-cog"></i> Profile Settings</a>
            </li>
        </ul>
    </aside>

    <!-- Main Payment Area -->
    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Course Payment & Verification</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Send tuition payment via Mobile Money or Bank and submit your transaction code.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($paymentUnavailable): ?>
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?= e($paymentUnavailable); ?></div>
        <?php endif; ?>
        <?php if ($historyError): ?>
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?= e($historyError); ?></div>
        <?php endif; ?>

        <!-- Payment Instructions Banner -->
        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900);">
                <i class="fas fa-wallet" style="color: var(--primary);"></i> Before making a payment
            </h4>
            <p style="color: var(--gray-600); margin: 0;">
                Confirm the current official payment details with Smart Class support before sending money. Once paid, submit the transaction reference below; your course access is enabled only after the finance team verifies it.
            </p>

            <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px dashed var(--gray-200); display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; justify-content: space-between;">
                <span style="font-size: 0.9rem; color: var(--gray-600);">
                    <i class="fas fa-headset" style="color: var(--primary);"></i>
                    Stuck on a payment? Our support team is ready to help on WhatsApp.
                </span>
                <a class="btn btn-primary btn-sm" href="<?= e(support_whatsapp_url('Hello support, I need help completing my course payment. My transaction ID is: ')); ?>" target="_blank" rel="noopener" style="background: #25d366; border-color: #25d366;">
                    <i class="fab fa-whatsapp"></i> Chat with support
                </a>
            </div>
        </div>

        <!-- Submission Form -->
        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.25rem;">
                Submit Payment Confirmation
            </h3>

            <form action="<?= site_url('student/payment.php'); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="course_id">Course Enrolling In *</label>
                        <select name="course_id" id="course_id" class="form-control" required onchange="updateAmount(this)" <?= empty($courses) ? 'disabled' : ''; ?>>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['id']; ?>" data-price="<?= e($c['price']); ?>" data-currency="<?= e($c['currency'] ?? 'RWF'); ?>" <?= $selectedCourseId == $c['id'] ? 'selected' : ''; ?>>
                                    <?= e($c['title']); ?> (<?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="amount">Tuition Amount</label>
                        <input type="text" id="amount" class="form-control" readonly value="">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="payment_method">Payment Method Used *</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="MTN Mobile Money">MTN Mobile Money (MoMo)</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Bank Transfer">Bank of Kigali / Equity Bank</option>
                            <option value="Cash">Cash Deposit</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="transaction_ref">Transaction ID / SMS Reference *</label>
                        <input type="text" name="transaction_ref" id="transaction_ref" class="form-control" required placeholder="e.g. MP241004.1209.A12345 or sender phone">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="proof_file">Optional Proof Screenshot (JPG, PNG, PDF)</label>
                    <input type="file" name="proof_file" id="proof_file" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf">
                    <div class="form-hint">Optional: upload a PDF, JPG, PNG, or WebP proof image (maximum 10 MB).</div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;" <?= empty($courses) || !$db_connected ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle"></i> Submit Payment for Approval
                </button>
            </form>
        </div>

        <!-- Previous Payments Table -->
        <div class="table-card">
            <div class="table-card-header">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--gray-900);">Your Payment History</h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Ref Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myPayments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    No payment records submitted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myPayments as $p): ?>
                                <tr>
                                    <td><?= date('d M Y, H:i', strtotime($p['created_at'])); ?></td>
                                    <td style="font-weight: 600; color: var(--gray-900);"><?= e($p['course_title']); ?></td>
                                    <td><?= format_money($p['amount'], $p['currency'] ?? 'RWF'); ?></td>
                                    <td><?= e($p['payment_method']); ?></td>
                                    <td><code><?= e($p['transaction_ref']); ?></code></td>
                                    <td>
                                        <?php if ($p['status'] === 'approved'): ?>
                                            <span class="badge badge-approved"><i class="fas fa-check"></i> Approved</span>
                                        <?php elseif ($p['status'] === 'rejected'): ?>
                                            <span class="badge badge-rejected"><i class="fas fa-times"></i> Rejected</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending Review</span>
                                        <?php endif; ?>
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

<script>
function updateAmount(select) {
    const opt = select.options[select.selectedIndex];
    const price = opt.getAttribute('data-price');
    if (price) {
        const currency = opt.getAttribute('data-currency') || 'RWF';
        document.getElementById('amount').value = `${Number(price).toLocaleString()} ${currency}`;
    }
}
updateAmount(document.getElementById('course_id'));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
