<?php
$page_title = "DIGITAL SMART CLASS — Build Your Skills. Create Your Future.";
require_once __DIR__ . '/includes/header.php';

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

<section class="hero">
    <div class="container">
        <div class="hero-tagline"><i class="fas fa-certificate"></i> Rwanda's creator academy</div>
        <h1 class="hero-title">Learn. Create. <span>Grow.</span></h1>
        <p class="hero-lead">Practical digital skills from professional instructors.</p>
        <p class="hero-desc">Build a YouTube channel, master Shorts, and produce long-form video with AI — at a fair price in RWF.</p>

        <div class="hero-actions">
            <a href="<?= site_url('courses.php'); ?>" class="btn btn-primary btn-lg">
                Explore courses <i class="fas fa-arrow-right"></i>
            </a>
            <a href="<?= site_url('register.php'); ?>" class="btn btn-outline btn-lg">
                Join now
            </a>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <h3><?= count($courses); ?>+</h3>
                <p>Flagship programs</p>
            </div>
            <div class="stat-item">
                <h3>100%</h3>
                <p>Practical workflows</p>
            </div>
            <div class="stat-item">
                <h3>MoMo</h3>
                <p>Local payment support</p>
            </div>
        </div>
    </div>
</section>

<section class="why-section">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Why learners choose us</div>
            <h2 class="section-title">A professional classroom for digital creators</h2>
            <p class="section-desc">Clear structure, vetted teachers, and protected content after payment approval.</p>
        </div>

        <div class="why-grid">
            <div class="feature-box">
                <div class="feature-icon green"><i class="fas fa-bolt"></i></div>
                <h4>Practical learning</h4>
                <p>Actionable real-world workflows without unnecessary fluff.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon violet"><i class="fas fa-chalkboard-teacher"></i></div>
                <h4>Expert teachers</h4>
                <p>Vetted, experienced creators verified by platform admin.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon blue"><i class="fas fa-clock"></i></div>
                <h4>Flexible learning</h4>
                <p>Self-paced video modules, notes, and downloadable guides.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon amber"><i class="fas fa-tag"></i></div>
                <h4>Affordable courses</h4>
                <p>High-value training priced fairly in local currency (RWF).</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h4>Progress tracking</h4>
                <p>Automatic lesson completion markers and watch percentage.</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon rose"><i class="fas fa-shield-alt"></i></div>
                <h4>Secure platform</h4>
                <p>MTN MoMo verification and protected course content.</p>
            </div>
        </div>
    </div>
</section>

<section class="container" style="padding-top: 4rem; padding-bottom: 3rem;" id="courses">
    <div class="section-header">
        <div class="section-subtitle">Catalog</div>
        <h2 class="section-title">Featured courses</h2>
        <p class="section-desc">Start with YouTube foundations, then scale into Shorts and AI-assisted long-form video.</p>
    </div>

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
                        <div style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; position: relative; z-index: 1;">
                            <?= e($c['category_name'] ?? 'Content Creation'); ?>
                        </div>
                    </div>
                </div>

                <div class="course-body">
                    <h3 class="course-title"><?= e($c['title']); ?></h3>
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
</section>

<section class="cta-band">
    <div class="container">
        <h2>Start learning today</h2>
        <p>Create an account, choose a course, and enroll with MTN MoMo or Airtel Money.</p>
        <a href="<?= site_url('courses.php'); ?>" class="btn btn-primary btn-lg">
            Browse all courses
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
