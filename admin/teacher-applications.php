<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Teacher Applications — DIGITAL SMART CLASS";

$reviewError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['action'])) {
    $appId = (int)$_POST['app_id'];
    $action = $_POST['action'];
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $reviewError = 'Your session expired. Refresh the page and try again.';
    } elseif (!in_array($action, ['approve', 'reject'], true) || $appId < 1) {
        $reviewError = 'Choose a valid application action.';
    } elseif (!$db_connected || !$pdo) {
        $reviewError = 'Application reviews are unavailable because the database is not connected.';
    } else {
        try {
            $pdo->beginTransaction();
            $appStmt = $pdo->prepare("SELECT user_id, status FROM teacher_applications WHERE id = :id FOR UPDATE");
            $appStmt->execute(['id' => $appId]);
            $application = $appStmt->fetch();
            if (!$application || $application['status'] !== 'pending') {
                throw new RuntimeException('This application has already been reviewed or no longer exists. Refresh the page to see the latest status.');
            }

            $updateApplication = $pdo->prepare("
                UPDATE teacher_applications
                SET status = :status, reviewed_by = :reviewed_by
                WHERE id = :id AND status = 'pending'
            ");
            $updateApplication->execute([
                'status' => $newStatus,
                'reviewed_by' => $user['id'],
                'id' => $appId
            ]);

            $updateUser = $pdo->prepare("
                UPDATE users
                SET role_id = CASE WHEN :role_status = 'approved' THEN 2 ELSE role_id END,
                    status = CASE
                        WHEN :active_status = 'approved' THEN 'active'
                        WHEN role_id = 2 THEN 'pending'
                        ELSE status
                    END
                WHERE id = :user_id
            ");
            $updateUser->execute([
                'role_status' => $newStatus,
                'active_status' => $newStatus,
                'user_id' => $application['user_id']
            ]);
            $notification = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, link)
                VALUES (:user_id, :title, :message, 'teacher/application.php')
            ");
            $notification->execute([
                'user_id' => $application['user_id'],
                'title' => $newStatus === 'approved' ? 'Teacher application approved' : 'Teacher application update',
                'message' => $newStatus === 'approved'
                    ? 'Your teacher application was approved. Sign in again to access the teacher dashboard.'
                    : 'Your teacher application was not approved. Review the application page if you wish to apply again.'
            ]);

            $pdo->commit();
            set_flash('success', "Teacher application #{$appId} has been " . strtoupper($newStatus) . " successfully.");
            header("Location: " . site_url('admin/teacher-applications.php'));
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $reviewError = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_database_error('Review teacher application', $e);
            $reviewError = 'The teacher application could not be updated. Please try again.';
        }
    }
}

$applications = [];
$applicationLoadError = null;
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM teacher_applications
            ORDER BY CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END, created_at DESC
        ");
        $applications = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load teacher applications for review', $e);
        $applicationLoadError = 'Teacher applications could not be loaded. Please try again later.';
    }
} else {
    $applicationLoadError = 'Teacher applications cannot be loaded while the database is unavailable.';
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

        <?php if ($reviewError): ?>
            <div class="alert alert-danger" role="alert"><?= e($reviewError); ?></div>
        <?php endif; ?>
        <?php if ($applicationLoadError): ?>
            <div class="alert alert-danger" role="alert"><?= e($applicationLoadError); ?></div>
        <?php endif; ?>

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
                                <td colspan="6" class="table-empty-state">
                                    <?= $applicationLoadError ? 'Application records are unavailable.' : 'No teacher applications yet. New submissions will appear here.'; ?>
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
                                        <div class="application-document-links">
                                            <?php if (!empty($app['cv_file'])): ?>
                                                <a href="<?= site_url('admin/teacher-document.php?id=' . (int)$app['id'] . '&document=cv'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-file-lines"></i> CV</a>
                                            <?php endif; ?>
                                            <?php if (!empty($app['certificate_file'])): ?>
                                                <a href="<?= site_url('admin/teacher-document.php?id=' . (int)$app['id'] . '&document=certificate'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-award"></i> Credential</a>
                                            <?php endif; ?>
                                            <?php if (empty($app['cv_file']) && empty($app['certificate_file'])): ?>
                                                <span class="table-muted">No documents</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <form action="" method="POST" class="application-review-actions">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                                <input type="hidden" name="app_id" value="<?= (int)$app['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i> Reject
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
