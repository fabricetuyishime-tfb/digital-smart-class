<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['teacher', 'admin']);
$user = current_user();
$page_title = "Create New Course — Digital Smart Class";

$error = null;
$success = null;

// Fetch categories
$categories = [];
if ($db_connected && $pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
    } catch (PDOException $e) {
        $categories = [];
    }
}
if (empty($categories)) {
    $categories = [
        ['id' => 1, 'name' => 'YouTube & Content Creation'],
        ['id' => 2, 'name' => 'Short-Form Video & Viral Media'],
        ['id' => 3, 'name' => 'AI & Video Automation'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $catId = (int)($_POST['category_id'] ?? 1);
    $subtitle = trim($_POST['subtitle'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $level = $_POST['level'] ?? 'Beginner';
    $price = (float)($_POST['price'] ?? 30000);
    $duration = (int)($_POST['duration_hours'] ?? 5);
    $lessons = (int)($_POST['total_lessons'] ?? 10);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

    if (empty($title) || empty($desc)) {
        $error = "Please fill in all mandatory fields.";
    } elseif ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO courses (category_id, teacher_id, title, slug, subtitle, description, level, price, currency, duration_hours, total_lessons, status)
                VALUES (:cid, :tid, :title, :slug, :sub, :descr, :lvl, :price, 'RWF', :dur, :les, 'published')
            ");
            $stmt->execute([
                'cid' => $catId,
                'tid' => $user['id'],
                'title' => $title,
                'slug' => $slug . '-' . time(),
                'sub' => $subtitle,
                'descr' => $desc,
                'lvl' => $level,
                'price' => $price,
                'dur' => $duration,
                'les' => $lessons
            ]);
            set_flash('success', "Course '{$title}' created and published successfully!");
            header("Location: " . site_url('teacher/courses.php'));
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        set_flash('success', "Course saved in preview mode!");
        header("Location: " . site_url('teacher/courses.php'));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #0284c7;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Instructor</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('teacher/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/add-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Course Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Enrolled Students</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Create New Masterclass</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Publish a new high-impact digital course to the catalog.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
        <?php endif; ?>

        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 2.25rem; max-width: 800px;">
            <form action="<?= site_url('teacher/add-course.php'); ?>" method="POST">
                <div class="form-group">
                    <label class="form-label" for="title">Course Title *</label>
                    <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. YouTube Creator Academy: From Absolute Beginner to Confident Pro">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category *</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= e($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="level">Difficulty Level *</label>
                        <select name="level" id="level" class="form-control" required>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="All Levels">All Levels</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subtitle">Subtitle / Hook *</label>
                    <input type="text" name="subtitle" id="subtitle" class="form-control" required placeholder="e.g. Master channel architecture, algorithm psychology, and clickable thumbnails.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Full Description & Learning Outcomes *</label>
                    <textarea name="description" id="description" class="form-control" rows="5" required placeholder="Describe what students will learn, tools required, and expected results..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label" for="price">Price (RWF) *</label>
                        <input type="number" name="price" id="price" class="form-control" required value="30000" step="500">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="duration_hours">Estimated Hours</label>
                        <input type="number" name="duration_hours" id="duration_hours" class="form-control" value="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="total_lessons">Total Lessons</label>
                        <input type="number" name="total_lessons" id="total_lessons" class="form-control" value="12">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
                    <i class="fas fa-check-circle"></i> Save & Publish Course
                </button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
