<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$course = null;
$modules = [];
$user = current_user();
$enrollment = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cat.name as category_name, u.full_name as instructor_name, u.bio as instructor_bio
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();

        if ($course) {
            // Fetch modules & lessons
            $modStmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = :cid ORDER BY order_num ASC");
            $modStmt->execute(['cid' => $courseId]);
            $rawModules = $modStmt->fetchAll();

            foreach ($rawModules as $m) {
                $lessStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = :mid ORDER BY order_num ASC");
                $lessStmt->execute(['mid' => $m['id']]);
                $m['lessons'] = $lessStmt->fetchAll();
                $modules[] = $m;
            }

            // Check enrollment if logged in
            if ($user) {
                $enrStmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
                $enrStmt->execute(['uid' => $user['id'], 'cid' => $courseId]);
                $enrollment = $enrStmt->fetch();
            }
        }
    } catch (PDOException $e) {
        $course = null;
    }
}

// Fallback if not found in DB
if (!$course) {
    $fallbackList = get_fallback_courses();
    foreach ($fallbackList as $fc) {
        if ($fc['id'] == $courseId) {
            $course = $fc;
            break;
        }
    }
    if (!$course) $course = $fallbackList[0];
}

// Handle Direct Enroll Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    require_login();
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (user_id, course_id, status)
                VALUES (:uid, :cid, 'pending')
                ON DUPLICATE KEY UPDATE status = status
            ");
            $stmt->execute(['uid' => $user['id'], 'cid' => $course['id']]);
            set_flash('success', "You have enrolled! Please complete your payment below to unlock all lessons.");
            header("Location: " . site_url('student/payment.php?course_id=' . $course['id']));
            exit;
        } catch (PDOException $e) {
            set_flash('error', "Enrollment error: " . $e->getMessage());
        }
    } else {
        set_flash('success', "Enrolled in preview mode! Please review the payment options.");
        header("Location: " . site_url('student/payment.php?course_id=' . $course['id']));
        exit;
    }
}

$page_title = e($course['title']) . " — Digital Smart Class";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <!-- Course Hero Header -->
    <div style="background: linear-gradient(135deg, var(--dark-surface), var(--dark)); color: var(--white); padding: 3rem; border-radius: var(--radius-lg); margin-bottom: 2.5rem; box-shadow: var(--shadow-md);">
        <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: space-between; align-items: center;">
            <div style="flex: 1; min-width: 300px;">
                <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 0.3rem 0.8rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; color: #67e8f9;">
                    <i class="fas fa-tag"></i> <?= e($course['category_name'] ?? 'Flagship'); ?> • <?= e($course['level']); ?>
                </div>
                <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 1rem;">
                    <?= e($course['title']); ?>
                </h1>
                <p style="font-size: 1.05rem; color: #cbd5e1; margin-bottom: 1.5rem; max-width: 750px;">
                    <?= e($course['subtitle']); ?>
                </p>

                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.9rem; color: #94a3b8;">
                    <div><i class="fas fa-user-tie" style="color: #38bdf8;"></i> Instructor: <?= e($course['instructor_name'] ?? 'David Mugisha'); ?></div>
                    <div><i class="fas fa-book-open" style="color: #38bdf8;"></i> <?= e($course['total_lessons']); ?> Lessons</div>
                    <div><i class="fas fa-clock" style="color: #38bdf8;"></i> <?= e($course['duration_hours']); ?> Hours Content</div>
                </div>
            </div>

            <!-- Enrollment Card -->
            <div style="background: var(--white); color: var(--gray-900); padding: 2rem; border-radius: var(--radius-lg); width: 320px; box-shadow: var(--shadow-xl); text-align: center;">
                <div style="font-size: 0.85rem; color: var(--gray-500); font-weight: 600; text-transform: uppercase;">Course Tuition</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin: 0.5rem 0 1.25rem;">
                    <?= format_money($course['price'], $course['currency'] ?? 'RWF'); ?>
                </div>

                <?php if ($enrollment && $enrollment['status'] === 'active'): ?>
                    <div class="alert alert-success" style="padding: 0.75rem; font-size: 0.9rem; margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i> You are fully enrolled!
                    </div>
                    <a href="#curriculum" class="btn btn-primary btn-block">
                        <i class="fas fa-play"></i> Watch Lessons Now
                    </a>
                <?php elseif ($enrollment && $enrollment['status'] === 'pending'): ?>
                    <div class="alert alert-info" style="padding: 0.75rem; font-size: 0.85rem; margin-bottom: 1rem;">
                        <i class="fas fa-clock"></i> Payment awaiting verification
                    </div>
                    <a href="<?= site_url('student/payment.php?course_id=' . $course['id']); ?>" class="btn btn-outline btn-block">
                        <i class="fas fa-receipt"></i> View Payment Details
                    </a>
                <?php else: ?>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="enroll">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-lock-open"></i> Enroll in Course
                        </button>
                    </form>
                    <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.75rem;">
                        Instant access upon Mobile Money approval
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Course Overview & Curriculum Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem;" id="curriculum">
        <!-- Left Column: Details & Modules -->
        <div>
            <div style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 2rem;">
                <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--gray-900); margin-bottom: 1rem;">Course Overview</h3>
                <p style="color: var(--gray-700); line-height: 1.7; font-size: 1rem;">
                    <?= nl2br(e($course['description'])); ?>
                </p>
            </div>

            <!-- Curriculum Modules -->
            <div style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--gray-900); margin-bottom: 1.5rem;">
                    <i class="fas fa-list-ul" style="color: var(--primary);"></i> Structured Curriculum
                </h3>

                <?php if (empty($modules)): ?>
                    <!-- Fallback Highlights Display -->
                    <div style="list-style: none;">
                        <?php foreach (($course['highlights'] ?? []) as $index => $h): ?>
                            <div style="padding: 1rem; border: 1px solid var(--gray-200); border-radius: var(--radius-md); margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--primary-light); color: var(--primary); font-weight: 700; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                        <?= $index + 1; ?>
                                    </span>
                                    <span style="font-weight: 600; color: var(--gray-800);"><?= e($h); ?></span>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--gray-500);"><i class="fas fa-lock"></i> Premium</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($modules as $mod): ?>
                        <div style="margin-bottom: 1.5rem; border: 1px solid var(--gray-200); border-radius: var(--radius-md); overflow: hidden;">
                            <div style="background: var(--gray-100); padding: 0.85rem 1.25rem; font-weight: 700; font-size: 0.95rem; color: var(--gray-800); display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-folder" style="color: var(--primary); margin-right: 0.5rem;"></i> <?= e($mod['title']); ?></span>
                                <span style="font-size: 0.8rem; color: var(--gray-500);"><?= count($mod['lessons'] ?? []); ?> Lessons</span>
                            </div>
                            <div style="padding: 0.5rem 1.25rem;">
                                <?php foreach (($mod['lessons'] ?? []) as $les): ?>
                                    <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                                            <i class="fas fa-play-circle" style="color: <?= (!empty($les['is_free_preview']) || ($enrollment && $enrollment['status'] === 'active')) ? 'var(--primary)' : 'var(--gray-400)'; ?>;"></i>
                                            <span style="font-size: 0.9rem; color: var(--gray-800); font-weight: 500;">
                                                <?= e($les['title']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <?php if (!empty($les['is_free_preview'])): ?>
                                                <span class="badge" style="background: #e0f2fe; color: #0369a1;">Free Preview</span>
                                            <?php elseif (!$enrollment || $enrollment['status'] !== 'active'): ?>
                                                <span style="font-size: 0.75rem; color: var(--gray-400);"><i class="fas fa-lock"></i> Locked</span>
                                            <?php else: ?>
                                                <span class="badge badge-active">Available</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Learning Highlights & Instructor -->
        <div>
            <div style="background: var(--white); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--gray-900);">What You Will Learn</h4>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> High-converting video packaging and algorithms
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Practical tools, mobile & desktop software
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Monetization models and brand partnership scripts
                    </li>
                    <li style="display: flex; gap: 0.5rem; font-size: 0.9rem; color: var(--gray-700);">
                        <i class="fas fa-check" style="color: var(--success); margin-top: 0.2rem;"></i> Direct feedback on your channel and assignments
                    </li>
                </ul>
            </div>

            <div style="background: var(--white); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900);">Instructor</h4>
                <p style="font-weight: 600; color: var(--primary); font-size: 0.95rem; margin-bottom: 0.35rem;">
                    <?= e($course['instructor_name'] ?? 'David Mugisha'); ?>
                </p>
                <p style="font-size: 0.85rem; color: var(--gray-600); line-height: 1.5;">
                    Lead Content Specialist at Digital Smart Class with over 5 years experience scaling YouTube channels and automated video workflows across East Africa.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
