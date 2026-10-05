<?php
require_once __DIR__ . '/../includes/accountant-auth.php';
$user = current_user();
$page_title = "Revenue Audit — DIGITAL SMART CLASS";

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
            <li class="sidebar-item"><a href="<?= site_url('accountant/rejected.php'); ?>"><i class="fas fa-times-circle"></i> Rejected</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('accountant/revenue.php'); ?>"><i class="fas fa-coins"></i> Revenue</a></li>
            <li class="sidebar-item"><a href="<?= site_url('accountant/reports.php'); ?>"><i class="fas fa-file-invoice"></i> Reports</a></li>
            <li class="sidebar-item" style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 0.5rem;">
                <a href="<?= site_url('logout.php'); ?>" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Platform Revenue Analysis</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Gross collections breakdown across Mobile Money and local bank channels.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-title">MTN Mobile Money</div>
                <div class="stat-card-value" style="color: #ca8a04;">3,780,000 RWF</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Airtel Money</div>
                <div class="stat-card-value" style="color: #dc2626;">1,080,000 RWF</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Bank Deposits (BK)</div>
                <div class="stat-card-value" style="color: #2563eb;">540,000 RWF</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Total Approved Revenue</div>
                <div class="stat-card-value" style="color: var(--success);">5,400,000 RWF</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3 style="font-size: 1.15rem; font-weight: 700;">Revenue Per Masterclass</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Masterclass Title</th>
                            <th>Tuition Fee</th>
                            <th>Verified Students</th>
                            <th>Total Earned (RWF)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 700;">Understand YouTube — Beginner to Confident Creator</td>
                            <td>30,000 RWF</td>
                            <td>75 Students</td>
                            <td style="font-weight: 700; color: var(--success);">2,250,000 RWF</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">YouTube Shorts Creation — From Idea to Short</td>
                            <td>30,000 RWF</td>
                            <td>65 Students</td>
                            <td style="font-weight: 700; color: var(--success);">1,950,000 RWF</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">Make Long Videos With AI — Complete AI Video Creation</td>
                            <td>40,000 RWF</td>
                            <td>30 Students</td>
                            <td style="font-weight: 700; color: var(--success);">1,200,000 RWF</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
