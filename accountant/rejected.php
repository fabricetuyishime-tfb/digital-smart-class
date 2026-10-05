<?php
require_once __DIR__ . '/../includes/accountant-auth.php';
$user = current_user();
$page_title = "Rejected Payments — DIGITAL SMART CLASS";

$rejectedList = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.full_name as student_name, c.title as course_title
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN courses c ON p.course_id = c.id
            WHERE p.status = 'rejected'
            ORDER BY p.updated_at DESC
        ");
        $rejectedList = $stmt->fetchAll();
    } catch (PDOException $e) {
        $rejectedList = [];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #059669;"><i class="fas fa-calculator"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Finance & Accounting</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('accountant/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/payments.php'); ?>"><i class="fas fa-receipt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/approved.php'); ?>"><i class="fas fa-check-circle"></i> Approved</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('accountant/rejected.php'); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/revenue.php'); ?>"><i class="fas fa-coins"></i> Revenue</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/reports.php'); ?>"><i class="fas fa-file-invoice"></i> Reports</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Rejected Payments Ledger</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Unverified, invalid, or fraudulent Mobile Money transaction references.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Amount</th>
                            <th>Invalid Transaction ID</th>
                            <th>Reason / Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rejectedList)): ?>
                            <tr>
                                <td><strong>Demo Student</strong></td>
                                <td>Understand YouTube</td>
                                <td>30,000 RWF</td>
                                <td><code>INVALID_CODE_123</code></td>
                                <td><span class="badge badge-rejected">SMS Code Not Found</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rejectedList as $rp): ?>
                                <tr>
                                    <td><strong><?= e($rp['student_name']); ?></strong></td>
                                    <td><?= e($rp['course_title']); ?></td>
                                    <td><?= format_money($rp['amount'], $rp['currency'] ?? 'RWF'); ?></td>
                                    <td><code><?= e($rp['transaction_ref']); ?></code></td>
                                    <td><span class="badge badge-rejected"><?= e($rp['accountant_note'] ?: 'Rejected'); ?></span></td>
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
