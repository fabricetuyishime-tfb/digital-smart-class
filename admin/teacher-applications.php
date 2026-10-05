<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Teacher Applications — DIGITAL SMART CLASS";

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['action'])) {
    $appId = (int)$_POST['app_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE teacher_applications SET status = :st, reviewed_by = :rb WHERE id = :id");
            $stmt->execute(['st' => $newStatus, 'rb' => $user['id'], 'id' => $appId]);

            // If approved, update user status to active
            if ($action === 'approve') {
                $uStmt = $pdo->prepare("SELECT user_id FROM teacher_applications WHERE id = :id");
                $uStmt->execute(['id' => $appId]);
                $targetUserId = $uStmt->fetchColumn();

                if ($targetUserId) {
                    $upU = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = :uid");
                    $upU->execute(['uid' => $targetUserId]);
                }
            }

            set_flash('success', "Teacher application #{$appId} has been " . strtoupper($newStatus) . " successfully!");
            header("Location: " . site_url('admin/teacher-applications.php'));
            exit;
        } catch (PDOException $e) {
            set_flash('error', "Database error: " . $e->getMessage());
        }
    } else {
        set_flash('success', "Teacher application marked as " . strtoupper($newStatus) . " (Preview Mode)!");
        header("Location: " . site_url('admin/teacher-applications.php'));
        exit;
    }
}

// Fetch applications
$applications = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM teacher_applications ORDER BY FIELD(status, 'pending') DESC, created_at DESC");
        $applications = $stmt->fetchAll();
    } catch (PDOException $e) {
        $applications = [];
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
            <li class="sidebar-item"><a href="<?= site_url('admin/dashboard.php'); ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/students.php'); ?>"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/teachers.php'); ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Teacher Applications & Approvals</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Review credentials, CVs, and grant teaching privileges.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Qualification & Experience</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Documents</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td>
                                    <strong>Sarah Keza</strong><br>
                                    <span style="font-size: 0.8rem; color: #64748b;">newteacher@digitalsmart.rw</span>
                                </td>
                                <td>
                                    Video Production Diploma<br>
                                    <span style="font-size: 0.8rem; color: #64748b;">3 Years Freelancing</span>
                                </td>
                                <td>+250 788 000 005</td>
                                <td><span class="badge badge-pending">Pending Review</span></td>
                                <td><a href="#" style="font-size: 0.85rem;"><i class="fas fa-file-pdf"></i> View CV</a></td>
                                <td>
                                    <form action="" method="POST" style="display: flex; gap: 0.35rem;">
                                        <input type="hidden" name="app_id" value="1">
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
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($app['full_name']); ?></strong><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= e($app['email']); ?></span>
                                    </td>
                                    <td>
                                        <?= e($app['qualification']); ?><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= e($app['experience']); ?></span>
                                    </td>
                                    <td><?= e($app['phone']); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $app['status']; ?>">
                                            <?= e(ucfirst($app['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['cv_file'])): ?>
                                            <a href="<?= site_url($app['cv_file']); ?>" target="_blank" style="font-size: 0.85rem;"><i class="fas fa-file-pdf"></i> CV</a>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: #94a3b8;">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <form action="" method="POST" style="display: flex; gap: 0.35rem;">
                                                <input type="hidden" name="app_id" value="<?= $app['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: #64748b; text-transform: capitalize;"><?= e($app['status']); ?></span>
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
