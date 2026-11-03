<?php
require_once __DIR__ . '/../includes/accountant-auth.php';
$user = current_user();
$page_title = "ACCOUNTANT DASHBOARD — DIGITAL SMART CLASS";

$demoMode = !empty($_SESSION['demo_mode']);
$totalPayments = 0;
$pendingPayments = 0;
$approvedPayments = 0;
$rejectedPayments = 0;
$totalRevenue = 0;
$dashboardError = null;

if ($db_connected && $pdo) {
    try {
        $totalPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
        $pendingPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        $approvedPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'approved'")->fetchColumn();
        $rejectedPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'rejected'")->fetchColumn();
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'approved'")->fetchColumn();
    } catch (PDOException $e) {
        log_database_error('Load accountant dashboard metrics', $e);
        $dashboardError = 'Payment metrics could not be loaded. Please refresh the page.';
    }
} elseif ($demoMode) {
    $totalPayments = 500;
    $pendingPayments = 12;
    $approvedPayments = 470;
    $rejectedPayments = 18;
    $totalRevenue = 5400000;
}

// Fetch recent transactions
$transactions = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.full_name as student_name, c.title as course_title
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN courses c ON p.course_id = c.id
            ORDER BY FIELD(p.status, 'pending') DESC, p.created_at DESC
            LIMIT 10
        ");
        $transactions = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load recent accountant transactions', $e);
        $dashboardError = 'Recent transactions could not be loaded.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Accountant Sidebar (Finance Only, No Courses/Users Modification) -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #059669;"><i class="fas fa-calculator"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Finance & Accounting</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active"><a href="<?= site_url('accountant/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/approved.php'); ?>"><i class="fas fa-check-circle"></i> Approved</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/rejected.php'); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/revenue.php'); ?>"><i class="fas fa-coins"></i> Revenue</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/reports.php'); ?>"><i class="fas fa-file-invoice"></i> Reports</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <?php if ($dashboardError): ?>
            <div class="alert alert-danger" role="alert"><?= e($dashboardError); ?></div>
        <?php endif; ?>
        <div class="dashboard-title-bar">
            <div>
                <h2>ACCOUNTANT DASHBOARD</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Financial verification of student MTN Mobile Money & Airtel transactions.</p>
            </div>
        </div>

        <!-- 5 Stats (Section 15 Mockup) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
            <div class="stat-box">
                <div class="stat-box-title">Total Payments</div>
                <div class="stat-box-value"><?= number_format($totalPayments); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Pending Payments</div>
                <div class="stat-box-value" style="color: var(--accent);"><?= number_format($pendingPayments); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Approved Payments</div>
                <div class="stat-box-value" style="color: var(--success);"><?= number_format($approvedPayments); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Rejected Payments</div>
                <div class="stat-box-value" style="color: var(--danger);"><?= number_format($rejectedPayments); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-title">Total Revenue</div>
                <div class="stat-box-value" style="color: var(--primary);"><?= number_format($totalRevenue); ?> RWF</div>
            </div>
        </div>

        <!-- PAYMENT TRANSACTIONS TABLE (Section 15 Mockup) -->
        <div class="table-card">
            <div class="table-card-header">
                <h3 style="font-size: 1.15rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">
                    PAYMENT TRANSACTIONS
                </h3>
                <a href="<?= site_url('accountant/payments.php'); ?>" class="btn btn-outline-primary btn-sm">View Full Ledger</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="7" class="table-empty-state">No payment transactions to display yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><strong><?= e($t['student_name']); ?></strong></td>
                                    <td><?= e($t['course_title']); ?></td>
                                    <td style="font-weight: 700;"><?= format_money($t['amount'], $t['currency'] ?? 'RWF'); ?></td>
                                    <td><?= e($t['payment_method']); ?></td>
                                    <td><code><?= e($t['transaction_ref']); ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $t['status']; ?>"><?= e(ucfirst($t['status'])); ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('accountant/payments.php'); ?>" class="btn btn-sm btn-outline">
                                            Manage
                                        </a>
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
