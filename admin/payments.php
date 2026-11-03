<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'accountant']);
$user = current_user();
$page_title = "Manage Payments — Digital Smart Class";
$paymentsLoadError = null;

if (isset($_GET['export']) && $_GET['export'] === 'csv' && $db_connected && $pdo) {
    try {
        $exportStmt = $pdo->query("
            SELECT p.id, u.full_name AS student, u.email, c.title AS course, p.amount,
                   p.currency, p.payment_method, p.transaction_ref, p.status, p.created_at
            FROM payments p
            JOIN users u ON u.id = p.user_id
            JOIN courses c ON c.id = p.course_id
            ORDER BY p.created_at DESC
        ");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="digital-smart-class-payments.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Student', 'Email', 'Course', 'Amount', 'Currency', 'Method', 'Transaction Reference', 'Status', 'Created']);
        while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    } catch (PDOException $e) {
        log_database_error('Export payments', $e);
        set_flash('error', 'The payment export could not be generated. Please try again.');
        header("Location: " . site_url('admin/payments.php'));
        exit;
    }
} elseif (isset($_GET['export']) && $_GET['export'] === 'csv') {
    set_flash('error', 'The payment export is unavailable because the database is not connected.');
    header("Location: " . site_url('admin/payments.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['action'])) {
    $payId = filter_var($_POST['payment_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Your session expired. Refresh the page and review the payment again.');
        header("Location: " . site_url('admin/payments.php'));
        exit;
    } elseif (!$payId || !in_array($action, ['approve', 'reject'], true)) {
        set_flash('error', 'The payment review request is invalid.');
        header("Location: " . site_url('admin/payments.php'));
        exit;
    } elseif (!$db_connected || !$pdo) {
        set_flash('error', 'Payment reviews require an active database connection. No changes were made.');
        header("Location: " . site_url('admin/payments.php'));
        exit;
    } else {
        try {
            $pdo->beginTransaction();
            $paymentStmt = $pdo->prepare("SELECT user_id, course_id, status FROM payments WHERE id = :id LIMIT 1 FOR UPDATE");
            $paymentStmt->execute(['id' => $payId]);
            $payment = $paymentStmt->fetch();
            if (!$payment || $payment['status'] !== 'pending') {
                throw new RuntimeException('This payment has already been reviewed or no longer exists.');
            }

            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $upPay = $pdo->prepare("UPDATE payments SET status = :status, reviewed_by = :reviewed_by WHERE id = :id AND status = 'pending'");
            $upPay->execute(['status' => $newStatus, 'reviewed_by' => $user['id'], 'id' => $payId]);
            if ($upPay->rowCount() !== 1) {
                throw new RuntimeException('The payment was already reviewed. Refresh the list before trying again.');
            }

            if ($action === 'approve') {
                $enrollmentStmt = $pdo->prepare("
                    SELECT id FROM enrollments
                    WHERE user_id = :user_id AND course_id = :course_id
                    LIMIT 1 FOR UPDATE
                ");
                $enrollmentStmt->execute(['user_id' => $payment['user_id'], 'course_id' => $payment['course_id']]);
                $enrollment = $enrollmentStmt->fetch();
                if (!$enrollment) {
                    throw new RuntimeException('The matching enrollment could not be found. The payment was not approved.');
                }
                $upEnrollment = $pdo->prepare("
                    UPDATE enrollments
                    SET status = 'active', activated_at = COALESCE(activated_at, CURRENT_TIMESTAMP)
                    WHERE id = :id
                ");
                $upEnrollment->execute(['id' => $enrollment['id']]);
            }

            $notification = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link)
                VALUES (:user_id, :title, :message, :link)
            ");
            $notification->execute([
                'user_id' => $payment['user_id'],
                'title' => $action === 'approve' ? 'Payment approved' : 'Payment needs attention',
                'message' => $action === 'approve'
                    ? 'Your payment has been approved and course access is now active.'
                    : 'Your payment was not approved. Please contact support or submit a corrected payment reference.',
                'link' => 'student/payment.php'
            ]);

            $pdo->commit();
            set_flash('success', "Payment #{$payId} marked as " . ucfirst($newStatus) . '.');
            header("Location: " . site_url('admin/payments.php'));
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', $e->getMessage());
            header("Location: " . site_url('admin/payments.php'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_database_error('Review payment', $e);
            set_flash('error', 'The payment could not be updated. Please try again.');
            header("Location: " . site_url('admin/payments.php'));
            exit;
        }
    }
}

$payments = [];
if ($db_connected && $pdo) {
    try {
        $pStmt = $pdo->query("
            SELECT p.*, u.full_name as student_name, u.email as student_email, u.phone as student_phone, c.title as course_title
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN courses c ON p.course_id = c.id
            ORDER BY CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END, p.created_at DESC
        ");
        $payments = $pStmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load payment ledger', $e);
        $paymentsLoadError = 'Payment records could not be loaded. Please refresh the page.';
    }
} else {
    $paymentsLoadError = 'Payment records are unavailable because the database is not connected.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: var(--dark-surface);"><i class="fas fa-user-shield"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span><?= e(ucfirst($user['role'])); ?></span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Admin Overview</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-graduation-cap"></i> Manage Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments Verification</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Financial Reports</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Payment Approvals & Ledger</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Verify transaction references from MTN MoMo, Airtel Money, and bank deposits.</p>
            </div>
            <a href="<?= site_url('admin/payments.php?export=csv'); ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>

        <?php if ($paymentsLoadError): ?>
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?= e($paymentsLoadError); ?></div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Amount (RWF)</th>
                            <th>Payment Method & Code</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    No payment records to display.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($p['student_name']); ?></strong><br>
                                        <span style="font-size: 0.8rem; color: var(--gray-500);"><?= e($p['student_phone'] ?: $p['student_email']); ?></span>
                                    </td>
                                    <td><?= e($p['course_title']); ?></td>
                                    <td style="font-weight: 700;"><?= format_money($p['amount'], $p['currency'] ?? 'RWF'); ?></td>
                                    <td>
                                        <strong><?= e($p['payment_method']); ?></strong><br>
                                        <code><?= e($p['transaction_ref']); ?></code>
                                        <?php if (!empty($p['proof_file'])): ?>
                                            <br><a href="<?= site_url('admin/payment-document.php?id=' . (int)$p['id']); ?>" target="_blank" rel="noopener" style="font-size: 0.8rem; color: var(--primary);"><i class="fas fa-paperclip"></i> View Proof</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($p['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['status']; ?>"><?= e(ucfirst($p['status'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <form action="" method="POST" style="display: flex; gap: 0.35rem;">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="payment_id" value="<?= $p['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Approve & Unlock Course">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reject Payment">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--gray-400);">Completed</span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
