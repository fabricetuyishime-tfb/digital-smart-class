<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Student Progress — DIGITAL SMART CLASS";

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
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/payments.php'); ?>"><i class="fas fa-money-check-alt"></i> Payments</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/enrollments.php'); ?>"><i class="fas fa-user-check"></i> Enrollments</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/progress.php'); ?>"><i class="fas fa-tasks"></i> Progress</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/accountants.php'); ?>"><i class="fas fa-calculator"></i> Accountants</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/reports.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Reports</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/notifications.php'); ?>"><i class="fas fa-bell"></i> Notifications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Student Completion & Progress Audit</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Monitor student lesson progress, video watch minutes, and completion certificates.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Progress %</th>
                            <th>Completed Lessons</th>
                            <th>Certificate Eligibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Fabrice Learner</strong><br><span style="font-size: 0.8rem; color: #64748b;">student@digitalsmart.rw</span></td>
                            <td>YouTube Shorts Creation — From Idea to Short</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="progress-container" style="width: 120px; margin: 0;">
                                        <div class="progress-bar-fill" style="width: 80%;"></div>
                                    </div>
                                    <span style="font-weight: 700; color: var(--primary);">80%</span>
                                </div>
                            </td>
                            <td>10 / 12 Lessons</td>
                            <td><span class="badge badge-pending">Needs 90%+</span></td>
                        </tr>
                        <tr>
                            <td><strong>Aline Uwase</strong><br><span style="font-size: 0.8rem; color: #64748b;">aline@example.rw</span></td>
                            <td>Understand YouTube — Beginner to Confident Creator</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="progress-container" style="width: 120px; margin: 0;">
                                        <div class="progress-bar-fill" style="width: 100%; background: #10b981;"></div>
                                    </div>
                                    <span style="font-weight: 700; color: var(--success);">100%</span>
                                </div>
                            </td>
                            <td>10 / 10 Lessons</td>
                            <td><span class="badge badge-active"><i class="fas fa-certificate"></i> Certified</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
