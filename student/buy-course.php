<?php
require_once __DIR__ . '/../includes/student-auth.php';
$user = current_user();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 1;
$course = null;

if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch();
    } catch (PDOException $e) {
        $course = null;
    }
}
if (!$course) {
    $fallbackList = get_fallback_courses();
    $course = $fallbackList[0];
}

$page_title = "Buy Course: " . e($course['title']) . " — DIGITAL SMART CLASS";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4rem; max-width: 800px;">
    <!-- Course Purchase Header -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="badge" style="background: #eef2ff; color: #4f46e5; font-size: 0.85rem; padding: 0.35rem 1rem; border-radius: 9999px;">
                MTN Mobile Money Payment Gateway
            </span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #0f172a; margin-top: 0.75rem;">
                <?= e($course['title']); ?>
            </h1>
            <div style="font-size: 2.25rem; font-weight: 800; color: var(--primary); margin-top: 0.5rem;">
                <?= format_money($course['price'], $course['currency'] ?? 'RWF'); ?>
            </div>
        </div>

        <!-- 3-Step MTN Payment Instructions (Section 9) -->
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1.75rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #92400e; margin-bottom: 1rem;">
                <i class="fas fa-mobile-alt"></i> Step 1: Make Payment via MTN Mobile Money
            </h3>

            <ol style="padding-left: 1.5rem; line-height: 1.8; color: #78350f; font-size: 0.95rem;">
                <li>Pick up your phone and dial: <strong>*182*8*1*0788000001#</strong></li>
                <li>Enter Amount: <strong><?= number_format($course['price']); ?> RWF</strong></li>
                <li>Confirm Merchant Name: <strong>DIGITAL SMART CLASS LTD</strong></li>
                <li>Enter your MoMo PIN to complete the transaction.</li>
                <li><strong>Copy the payment SMS message</strong> or write down the Transaction Reference ID (e.g. <code>MP241004.1209.A12345</code>).</li>
            </ol>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.75rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 0.75rem;">
                <i class="fas fa-shield-alt" style="color: var(--success);"></i> Step 2: Submit Proof for Approval
            </h3>
            <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">
                After sending your payment, click the button below to submit your transaction ID. Our administrative and accounting team will review and approve your enrollment, instantly unlocking all course videos, notes, files, and presentations!
            </p>
        </div>

        <div style="text-align: center;">
            <a href="<?= site_url('student/payment.php?course_id=' . $course['id']); ?>" class="btn btn-primary btn-lg" style="padding: 1rem 3rem; font-size: 1.1rem; font-weight: 800;">
                <i class="fas fa-check-circle"></i> SUBMIT PAYMENT PROOF NOW
            </a>
            <div style="margin-top: 1rem;">
                <a href="<?= site_url('courses.php'); ?>" style="color: #64748b; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Cancel and return to courses
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
