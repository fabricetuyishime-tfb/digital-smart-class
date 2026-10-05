<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student');
$user = current_user();
$page_title = "Payment & Verification — Digital Smart Class";

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 1;
$error = null;
$success = null;

// Fetch courses for dropdown
$courses = [];
if ($db_connected && $pdo) {
    try {
        $cStmt = $pdo->query("SELECT id, title, price, currency FROM courses WHERE status = 'published'");
        $courses = $cStmt->fetchAll();
    } catch (PDOException $e) {
        $courses = get_fallback_courses();
    }
} else {
    $courses = get_fallback_courses();
}

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 1);
    $amount = (float)($_POST['amount'] ?? 30000);
    $method = trim($_POST['payment_method'] ?? 'MTN Mobile Money');
    $txRef = trim($_POST['transaction_ref'] ?? '');
    
    if (empty($txRef)) {
        $error = "Please provide the Transaction Reference ID or Mobile Money confirmation code.";
    } elseif ($db_connected && $pdo) {
        try {
            // Handle optional proof upload
            $proofPath = null;
            if (!empty($_FILES['proof_file']['name'])) {
                $targetDir = __DIR__ . '/../uploads/payments/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['proof_file']['name']);
                $targetFilePath = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $targetFilePath)) {
                    $proofPath = 'uploads/payments/' . $fileName;
                }
            }

            // Ensure enrollment record exists
            $enr = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (:uid, :cid, 'pending') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
            $enr->execute(['uid' => $user['id'], 'cid' => $courseId]);
            $enrollmentId = $pdo->lastInsertId();

            // Insert payment record
            $payStmt = $pdo->prepare("
                INSERT INTO payments (enrollment_id, user_id, course_id, amount, currency, payment_method, transaction_ref, proof_file, status)
                VALUES (:eid, :uid, :cid, :amount, 'RWF', :method, :txref, :proof, 'pending')
            ");
            $payStmt->execute([
                'eid' => $enrollmentId ?: null,
                'uid' => $user['id'],
                'cid' => $courseId,
                'amount' => $amount,
                'method' => $method,
                'txref' => $txRef,
                'proof' => $proofPath
            ]);

            set_flash('success', "Payment receipt submitted successfully! Our accounting team will verify it shortly.");
            header("Location: " . site_url('student/payment.php'));
            exit;
        } catch (PDOException $e) {
            $error = "Payment submission error: " . $e->getMessage();
        }
    } else {
        set_flash('success', "Payment proof recorded (Preview Mode)! Once approved, you will have instant access.");
        header("Location: " . site_url('student/payment.php'));
        exit;
    }
}

// Fetch user's previous payments
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
        $myPayments = [];
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

        <!-- Payment Instructions Banner -->
        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--gray-900);">
                <i class="fas fa-wallet" style="color: var(--primary);"></i> How to Pay in Rwanda (RWF)
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 1rem; border-radius: var(--radius-md);">
                    <strong style="color: #92400e; font-size: 0.95rem;">MTN Mobile Money (MoMo)</strong>
                    <p style="font-size: 0.85rem; color: #78350f; margin-top: 0.35rem;">
                        Dial: <strong>*182*8*1*0788000001#</strong><br>
                        Recipient: <strong>Digital Smart Class Ltd</strong><br>
                        Save the SMS transaction ID.
                    </p>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: var(--radius-md);">
                    <strong style="color: #991b1b; font-size: 0.95rem;">Airtel Money</strong>
                    <p style="font-size: 0.85rem; color: #7f1d1d; margin-top: 0.35rem;">
                        Dial: <strong>*500*4*...</strong><br>
                        Number: <strong>0730000002</strong><br>
                        Keep the SMS confirmation reference.
                    </p>
                </div>

                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: var(--radius-md);">
                    <strong style="color: #1e40af; font-size: 0.95rem;">Bank of Kigali (BK)</strong>
                    <p style="font-size: 0.85rem; color: #1e3a8a; margin-top: 0.35rem;">
                        Account: <strong>00045-01234567-89</strong><br>
                        Name: <strong>Digital Smart Class Ltd</strong><br>
                        Upload deposit slip screenshot.
                    </p>
                </div>
            </div>
        </div>

        <!-- Submission Form -->
        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.25rem;">
                Submit Payment Confirmation
            </h3>

            <form action="<?= site_url('student/payment.php'); ?>" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="course_id">Course Enrolling In *</label>
                        <select name="course_id" id="course_id" class="form-control" required onchange="updateAmount(this)">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id']; ?>" data-price="<?= $c['price']; ?>" <?= $selectedCourseId == $c['id'] ? 'selected' : ''; ?>>
                                    <?= e($c['title']); ?> (<?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="amount">Tuition Amount (RWF) *</label>
                        <input type="number" name="amount" id="amount" class="form-control" required value="30000">
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
                    <input type="file" name="proof_file" id="proof_file" class="form-control" accept="image/*,.pdf">
                    <div class="form-hint">Upload a screenshot of the MoMo confirmation message or bank deposit slip for faster verification.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
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
        document.getElementById('amount').value = price;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
