<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$user = current_user();
$page_title = "Manage Users — Digital Smart Class";

$usersList = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.*, r.display_name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
        ");
        $usersList = $stmt->fetchAll();
    } catch (PDOException $e) {
        $usersList = [];
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
                <span>Super Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Admin Overview</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/users.php'); ?>"><i class="fas fa-users-cog"></i> Manage Users</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-graduation-cap"></i> Manage Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments Verification</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Financial Reports</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Registered Platform Users</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">System administrators, instructors, accountants, and active students.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                            <th>System Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usersList)): ?>
                            <tr>
                                <td>#1</td>
                                <td><strong>Fabrice Administrator</strong></td>
                                <td>erc@gmail.com</td>
                                <td>+250788000001</td>
                                <td><span class="badge" style="background: #1e293b; color: white;">Administrator</span></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                            <tr>
                                <td>#2</td>
                                <td><strong>David Mugisha (Lead Instructor)</strong></td>
                                <td>teacher@digitalsmart.rw</td>
                                <td>+250788000002</td>
                                <td><span class="badge" style="background: #0284c7; color: white;">Instructor</span></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                            <tr>
                                <td>#3</td>
                                <td><strong>Aline Uwase</strong></td>
                                <td>student@digitalsmart.rw</td>
                                <td>+250788000003</td>
                                <td><span class="badge" style="background: #4f46e5; color: white;">Student</span></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                            <tr>
                                <td>#4</td>
                                <td><strong>Jean-Claude Kalisa (Finance)</strong></td>
                                <td>accountant@digitalsmart.rw</td>
                                <td>+250788000004</td>
                                <td><span class="badge" style="background: #059669; color: white;">Accountant</span></td>
                                <td><span class="badge badge-active">Active</span></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usersList as $u): ?>
                                <tr>
                                    <td>#<?= $u['id']; ?></td>
                                    <td><strong><?= e($u['full_name']); ?></strong></td>
                                    <td><?= e($u['email']); ?></td>
                                    <td><?= e($u['phone'] ?: 'N/A'); ?></td>
                                    <td><span class="badge" style="background: var(--primary-light); color: var(--primary);"><?= e($u['role_name']); ?></span></td>
                                    <td><span class="badge badge-<?= $u['status']; ?>"><?= e(ucfirst($u['status'])); ?></span></td>
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
