<?php
$page_title = "Courses — DIGITAL SMART CLASS";
require_once __DIR__ . '/includes/header.php';

// Fetch courses
$courses = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name 
            FROM courses c
            LEFT JOIN course_categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.status = 'published'
            ORDER BY c.id ASC
        ");
        $courses = $stmt->fetchAll();
    } catch (PDOException $e) {
        $courses = get_fallback_courses();
    }
} else {
    $courses = get_fallback_courses();
}
?>

<section class="page-hero">
    <div class="container">
        <div class="section-subtitle">Learn by doing</div>
        <h1>Course catalog</h1>
        <p>Practical, high-yield digital video and content creation masterclasses.</p>
    </div>
</section>

<div class="container" style="padding-top: 2.4rem; padding-bottom: 4rem;">

    <div class="course-grid">
        <?php foreach ($courses as $c): ?>
            <?php 
                $cardType = 'youtube';
                if (stripos($c['title'], 'Shorts') !== false) {
                    $cardType = 'shorts';
                } elseif (stripos($c['title'], 'AI') !== false) {
                    $cardType = 'ai';
                }
            ?>
            <div class="course-card">
                <div class="course-banner <?= $cardType; ?>">
                    <span class="course-level-badge"><?= e($c['level']); ?></span>
                    <div>
                        <div class="course-banner-icon">
                            <?php if ($cardType === 'youtube'): ?>
                                <i class="fab fa-youtube"></i>
                            <?php elseif ($cardType === 'shorts'): ?>
                                <i class="fas fa-bolt"></i>
                            <?php else: ?>
                                <i class="fas fa-robot"></i>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase;">
                            <?= e($c['category_name'] ?? 'Content Creation'); ?>
                        </div>
                    </div>
                </div>

                <div class="course-body">
                    <div class="course-category">
                        <?= e($c['category_name'] ?? 'Video Production'); ?>
                    </div>
                    <h3 class="course-title">
                        <?= e($c['title']); ?>
                    </h3>
                    <p class="course-subtitle">
                        <?= e($c['subtitle'] ?? substr(strip_tags($c['description']), 0, 110) . '...'); ?>
                    </p>

                    <div class="course-meta">
                        <div class="course-meta-item">
                            <i class="fas fa-book-open"></i> <?= e($c['total_lessons'] ?? 10); ?> Lessons
                        </div>
                        <div class="course-meta-item">
                            <i class="fas fa-calendar-alt"></i> <?= e($c['duration_weeks'] ?? '4 Weeks'); ?>
                        </div>
                    </div>

                    <div class="course-footer">
                        <div class="course-price">
                            <?= format_money($c['price'], $c['currency'] ?? 'RWF'); ?>
                        </div>
                        <a href="<?= site_url('course-details.php?id=' . $c['id']); ?>" class="btn btn-primary btn-sm">
                            View course
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
