<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$user = current_user();
$page_title = "Course Categories — DIGITAL SMART CLASS";

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_name'])) {
    $name = trim($_POST['cat_name']);
    $desc = trim($_POST['cat_desc'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    if (!empty($name) && $db_connected && $pdo) {
        $stmt = $pdo->prepare("INSERT INTO course_categories (name, slug, description) VALUES (:name, :slug, :desc)");
        $stmt->execute(['name' => $name, 'slug' => $slug, 'desc' => $desc]);
        set_flash('success', 'New course category added!');
        header("Location: " . site_url('admin/categories.php'));
        exit;
    }
}

$categories = [];
if ($db_connected && $pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM course_categories ORDER BY id ASC")->fetchAll();
    } catch (PDOException $e) {
        $categories = [];
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
            <li class="sidebar-item"><a href="<?= site_url('admin/teacher-applications.php'); ?>"><i class="fas fa-id-card"></i> Teacher Applications</a></li>
            <li class="sidebar-item"><a href="<?= site_url('admin/courses.php'); ?>"><i class="fas fa-book"></i> Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('admin/categories.php'); ?>"><i class="fas fa-folder"></i> Categories</a></li>
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
                <h2>Manage Course Categories</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Group your platform curriculum by media disciplines.</p>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; max-width: 600px;">
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 1rem;">Add New Category</h4>
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="cat_name" class="form-control" required placeholder="e.g. YouTube & Content Creation">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="cat_desc" class="form-control" placeholder="Short description of this track...">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
            </form>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td><strong>YouTube & Content Creation</strong></td>
                                <td><code>youtube-content-creation</code></td>
                                <td>Master channel building, packaging, and monetization</td>
                            </tr>
                            <tr>
                                <td><strong>Content Creation</strong></td>
                                <td><code>content-creation</code></td>
                                <td>Vertical video production, viral algorithms, and hooks</td>
                            </tr>
                            <tr>
                                <td><strong>AI & Content Creation</strong></td>
                                <td><code>ai-content-creation</code></td>
                                <td>AI scriptwriting, neural voiceovers, and automated production</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?= e($cat['name']); ?></strong></td>
                                    <td><code><?= e($cat['slug']); ?></code></td>
                                    <td><?= e($cat['description']); ?></td>
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
