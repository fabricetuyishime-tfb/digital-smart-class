<?php
/**
 * DIGITAL SMART CLASS — Secured Learning Page
 * Strict Backend Access Enforcement (Section 19)
 */
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 2;
$currentLessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : null;

// =========================================================================
// SECTION 19: STRICT BACKEND PERMISSION CHECK
// =========================================================================
$isEnrolledAndActive = false;
$course = null;

if ($db_connected && $pdo) {
    try {
        // 1. Fetch course details
        $cStmt = $pdo->prepare("
            SELECT c.*, u.full_name as teacher_name 
            FROM courses c
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        $cStmt->execute(['id' => $courseId]);
        $course = $cStmt->fetch();

        if (!$course) {
            set_flash('error', 'Course not found.');
            header("Location: " . site_url('student/my-courses.php'));
            exit;
        }

        // 2. Check if student has APPROVED enrollment (Admin override allowed for previewing)
        if ($user['role'] === 'admin') {
            $isEnrolledAndActive = true;
        } else {
            $enrStmt = $pdo->prepare("
                SELECT status FROM enrollments 
                WHERE user_id = :uid AND course_id = :cid
                LIMIT 1
            ");
            $enrStmt->execute(['uid' => $user['id'], 'cid' => $courseId]);
            $enrollmentStatus = $enrStmt->fetchColumn();

            if ($enrollmentStatus === 'active' || $enrollmentStatus === 'completed') {
                $isEnrolledAndActive = true;
            } elseif ($enrollmentStatus === 'pending') {
                set_flash('error', 'Access Denied: Your enrollment is PENDING payment verification. Please wait for the accountant to approve your payment.');
                header("Location: " . site_url('student/payment.php?course_id=' . $courseId));
                exit;
            } else {
                set_flash('error', 'Access Denied: You must purchase and have an approved enrollment to access course lessons.');
                header("Location: " . site_url('course-details.php?id=' . $courseId));
                exit;
            }
        }
    } catch (PDOException $e) {
        $isEnrolledAndActive = false;
    }
} else {
    // Demo preview mode
    $isEnrolledAndActive = true;
    $fallbackList = get_fallback_courses();
    $course = $fallbackList[1]; // Course 2
}

// Handle "MARK AS COMPLETED" Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_completed') {
    $lessonToComplete = (int)$_POST['lesson_id'];
    if ($db_connected && $pdo) {
        try {
            $compStmt = $pdo->prepare("
                INSERT INTO lesson_progress (user_id, course_id, lesson_id, is_completed)
                VALUES (:uid, :cid, :lid, 1)
                ON DUPLICATE KEY UPDATE is_completed = 1, completed_at = NOW()
            ");
            $compStmt->execute([
                'uid' => $user['id'],
                'cid' => $courseId,
                'lid' => $lessonToComplete
            ]);
            set_flash('success', 'Lesson marked as COMPLETED! Your course progress has been updated.');
            header("Location: " . site_url('student/learning.php?course_id=' . $courseId . '&lesson_id=' . $lessonToComplete));
            exit;
        } catch (PDOException $e) {
            set_flash('error', 'Progress update error: ' . $e->getMessage());
        }
    } else {
        set_flash('success', 'Lesson marked as COMPLETED (Preview Mode)!');
        header("Location: " . site_url('student/learning.php?course_id=' . $courseId . '&lesson_id=' . $lessonToComplete));
        exit;
    }
}

// Fetch Modules, Lessons, and Materials
$modules = [];
$completedLessonIds = [];
$currentLesson = null;
$totalLessonsCount = 0;

if ($db_connected && $pdo) {
    try {
        // Fetch completed lesson IDs for this user
        $pStmt = $pdo->prepare("SELECT lesson_id FROM lesson_progress WHERE user_id = :uid AND course_id = :cid AND is_completed = 1");
        $pStmt->execute(['uid' => $user['id'], 'cid' => $courseId]);
        $completedLessonIds = $pStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // Fetch modules
        $mStmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = :cid ORDER BY order_num ASC");
        $mStmt->execute(['cid' => $courseId]);
        $rawModules = $mStmt->fetchAll();

        foreach ($rawModules as $mod) {
            $lStmt = $pdo->prepare("SELECT * FROM lessons WHERE module_id = :mid ORDER BY order_num ASC");
            $lStmt->execute(['mid' => $mod['id']]);
            $mod['lessons'] = $lStmt->fetchAll();
            $totalLessonsCount += count($mod['lessons']);

            foreach ($mod['lessons'] as $les) {
                if ($currentLessonId && $les['id'] == $currentLessonId) {
                    $currentLesson = $les;
                }
            }
            $modules[] = $mod;
        }

        // Default to first lesson if not set
        if (!$currentLesson && !empty($modules[0]['lessons'])) {
            $currentLesson = $modules[0]['lessons'][0];
            $currentLessonId = $currentLesson['id'];
        }
    } catch (PDOException $e) {
        $modules = [];
    }
}

// Calculate progress percentage
$progressPercentage = 0;
if ($totalLessonsCount > 0) {
    $progressPercentage = round((count($completedLessonIds) / $totalLessonsCount) * 100);
} else {
    $progressPercentage = 80; // Default matching Section 10 mockup
}

// Fetch materials for current course/lesson
$materials = [];
if ($db_connected && $pdo) {
    try {
        $matStmt = $pdo->prepare("SELECT * FROM materials WHERE course_id = :cid");
        $matStmt->execute(['cid' => $courseId]);
        $materials = $matStmt->fetchAll();
    } catch (PDOException $e) {
        $materials = [];
    }
}

$page_title = e($course['title']) . " — Learning Room";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
    <!-- Course Title & Instructor Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700; color: var(--primary); letter-spacing: 0.05em;">
                <i class="fas fa-graduation-cap"></i> DIGITAL SMART CLASS
            </div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin: 0.25rem 0;">
                <?= e($course['title']); ?>
            </h1>
            <div style="font-size: 0.95rem; color: #64748b;">
                Teacher: <strong><?= e($course['teacher_name'] ?? 'John Doe'); ?></strong>
            </div>
        </div>

        <a href="<?= site_url('student/my-courses.php'); ?>" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> My Courses
        </a>
    </div>

    <!-- Overall Course Progress Bar (Section 10 Mockup) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;">
            <span>Course Progress</span>
            <span style="color: var(--primary);"><?= $progressPercentage; ?>% COMPLETED</span>
        </div>
        <div class="progress-container" style="height: 12px;">
            <div class="progress-bar-fill" style="width: <?= $progressPercentage; ?>%;"></div>
        </div>
        <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.35rem;">
            <?= count($completedLessonIds); ?> of <?= $totalLessonsCount ?: 12; ?> lessons finished. 90% watched or marked complete to earn certificate.
        </div>
    </div>

    <!-- Main Learning Layout: Left Player + Right Curriculum (Section 10) -->
    <div class="learning-layout">
        <!-- Left Column: Video Player, Notes, Materials -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a;">
                    <?= e($currentLesson['title'] ?? 'Lesson 5: Recording Shorts'); ?>
                </h2>
                <?php if (in_array($currentLesson['id'] ?? 0, $completedLessonIds)): ?>
                    <span class="badge badge-active"><i class="fas fa-check"></i> Completed</span>
                <?php else: ?>
                    <span class="badge badge-pending">In Progress</span>
                <?php endif; ?>
            </div>

            <!-- Video Player (Section 10 Mockup) -->
            <div class="video-container">
                <?php if (!empty($currentLesson['video_url'])): ?>
                    <iframe src="<?= e($currentLesson['video_url']); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                <?php else: ?>
                    <div class="video-placeholder">
                        <i class="fas fa-play-circle" style="font-size: 4.5rem; color: var(--primary); margin-bottom: 1rem; opacity: 0.9;"></i>
                        <h3 style="font-size: 1.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            VIDEO PLAYER
                        </h3>
                        <p style="font-size: 0.9rem; color: #94a3b8; margin-top: 0.5rem;">
                            Lesson Duration: <?= $currentLesson['duration_minutes'] ?? 20; ?> minutes
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mark As Completed Action Button (Section 10 & 11) -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="mark_completed">
                    <input type="hidden" name="lesson_id" value="<?= $currentLesson['id'] ?? 1; ?>">
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.85rem 2.5rem; font-weight: 800; letter-spacing: 0.05em;">
                        <i class="fas fa-check-circle"></i> MARK AS COMPLETED
                    </button>
                </form>
            </div>

            <!-- Lesson Notes (Section 10 Mockup) -->
            <div class="lesson-notes-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a;">
                        <i class="fas fa-file-alt" style="color: var(--primary);"></i> Lesson Notes
                    </h3>
                    <button type="button" class="btn btn-outline btn-sm" onclick="alert('Viewing expanded lesson notes.')">
                        [ Read Notes ]
                    </button>
                </div>
                <div style="line-height: 1.8; color: #334155; font-size: 0.95rem;">
                    <p>
                        <?= nl2br(e($currentLesson['content'] ?? 'In this lesson, you will master the foundational vertical video frameworks needed to retain viewer attention in the first 3 seconds. Focus on the lighting, clean audio, and high-energy delivery.')); ?>
                    </p>
                </div>
            </div>

            <!-- Learning Materials & Downloads (Section 10 Mockup) -->
            <div class="lesson-notes-box" style="margin-top: 1.5rem;">
                <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem;">
                    <i class="fas fa-folder-open" style="color: var(--secondary);"></i> Learning Materials
                </h3>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="alert('Downloading lesson PDF checklist.')">
                        <i class="fas fa-file-pdf" style="color: #ef4444;"></i> [ Download PDF ]
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="alert('Opening presentation slide deck.')">
                        <i class="fas fa-file-powerpoint" style="color: #ea580c;"></i> [ View Presentation ]
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar Curriculum (Section 10 Mockup) -->
        <div>
            <div class="curriculum-sidebar">
                <div style="background: var(--dark-surface); color: #ffffff; padding: 1.25rem; font-weight: 800; font-size: 1rem; text-transform: uppercase;">
                    Course Modules
                </div>

                <?php if (empty($modules)): ?>
                    <!-- Section 10 Mockup Modules Representation -->
                    <div class="curriculum-module-header">MODULE 1 — INTRODUCTION</div>
                    <a href="#" class="lesson-list-item">
                        <span><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i> Lesson 1: Understanding Shorts</span>
                    </a>
                    <a href="#" class="lesson-list-item">
                        <span><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i> Lesson 2: Finding Ideas</span>
                    </a>
                    <a href="#" class="lesson-list-item">
                        <span><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i> Lesson 3: Creating Hooks</span>
                    </a>

                    <div class="curriculum-module-header">MODULE 2 — CREATION</div>
                    <a href="#" class="lesson-list-item">
                        <span><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i> Lesson 4: Writing Scripts</span>
                    </a>
                    <a href="#" class="lesson-list-item active">
                        <span><i class="fas fa-play" style="color: var(--primary); margin-right: 0.5rem;"></i> Lesson 5: Recording Shorts</span>
                    </a>
                    <a href="#" class="lesson-list-item locked">
                        <span><i class="fas fa-lock" style="margin-right: 0.5rem;"></i> Lesson 6: Editing Shorts</span>
                    </a>
                <?php else: ?>
                    <?php foreach ($modules as $mIndex => $mod): ?>
                        <div class="curriculum-module-header">
                            <?= e(strtoupper($mod['title'])); ?>
                        </div>
                        <?php foreach ($mod['lessons'] as $les): ?>
                            <?php 
                                $isCurrent = ($currentLessonId == $les['id']);
                                $isDone = in_array($les['id'], $completedLessonIds);
                            ?>
                            <a href="<?= site_url('student/learning.php?course_id=' . $courseId . '&lesson_id=' . $les['id']); ?>" class="lesson-list-item <?= $isCurrent ? 'active' : ''; ?>">
                                <span>
                                    <?php if ($isDone): ?>
                                        <i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>
                                    <?php elseif ($isCurrent): ?>
                                        <i class="fas fa-play" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                    <?php else: ?>
                                        <i class="far fa-circle" style="color: var(--gray-400); margin-right: 0.5rem;"></i>
                                    <?php endif; ?>
                                    <?= e($les['title']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
