<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'accountant']);
$user = current_user();
$page_title = "Manage Payments — Digital Smart Class";

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['action'])) {
    $payId = (int)$_POST['payment_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    if ($db_connected && $pdo) {
        try {
            // Update payment record
            $upPay = $pdo->prepare("UPDATE payments SET status = :st, reviewed_by = :rb WHERE id = :id");
            $upPay->execute(['st' => $newStatus, 'rb' => $user['id'], 'id' => $payId]);

            // If approved, activate the enrollment
            if ($action === 'approve') {
                $pData = $pdo->prepare("SELECT user_id, course_id FROM payments WHERE id = :id");
                $pData->execute(['id' => $payId]);
                $payment = $pData->fetch();

                if ($payment) {
                    $upEnr = $pdo->prepare("UPDATE enrollments SET status = 'active' WHERE user_id = :uid AND course_id = :cid");
                    $upEnr->execute(['uid' => $payment['user_id'], 'cid' => $payment['course_id']]);
                }
            }

            set_flash('success', "Payment #{$payId} marked as " . ucfirst($newStatus) . " successfully!");
            header("Location: " . site_url('admin/payments.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('error', "Database error: " . $e->getMessage());
        }
    } else {
        set_flash('success', "Payment status updated to " . ucfirst($newStatus) . " (Preview Mode)!");
        header("Location: " . site_url('admin/payments.php'));
        exit;
    }
}

// Fetch all payments
$payments = [];
if ($db_connected && $pdo) {
    try {
        $pStmt = $pdo->query("
            SELECT p.*, u.full_name as student_name, u.email as student_email, u.phone as student_phone, c.title as course_title
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN courses c ON p.course_id = c.id
            ORDER BY FIELD(p.status, 'pending') DESC, p.created_at DESC
        ");
        $payments = $pStmt->fetchAll();
    } catch (PDOException $e) {
        $payments = [];
    }
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
        </div>

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
                            <!-- Demo Row for preview -->
                            <tr>
                                <td>
                                    <strong>Aline Uwase</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--gray-500);">+250 788 000 003</span>
                                </td>
                                <td>YouTube Creator Academy</td>
                                <td style="font-weight: 700;">30,000 RWF</td>
                                <td>
                                    <strong>MTN Mobile Money</strong><br>
                                    <code>MP241004.1209.A12345</code>
                                </td>
                                <td><?= date('d M Y'); ?></td>
                                <td><span class="badge badge-pending">Pending Review</span></td>
                                <td>
                                    <form action="" method="POST" style="display: inline-block;">
                                        <input type="hidden" name="payment_id" value="1">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
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
                                            <br><a href="<?= site_url($p['proof_file']); ?>" target="_blank" style="font-size: 0.8rem; color: var(--primary);"><i class="fas fa-paperclip"></i> View Screenshot</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($p['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['status']; ?>"><?= e(ucfirst($p['status'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <form action="" method="POST" style="display: flex; gap: 0.35rem;">
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
