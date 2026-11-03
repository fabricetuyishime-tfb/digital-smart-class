<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_role(['admin', 'teacher', 'student', 'accountant']);
$user = current_user();
$page_title = "Notifications — DIGITAL SMART CLASS";
$notificationError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notificationIdInput = $_POST['notification_id'] ?? null;
    $notificationId = is_scalar($notificationIdInput)
        ? filter_var($notificationIdInput, FILTER_VALIDATE_INT)
        : false;

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Your session expired. Refresh the page and try again.');
    } elseif (!$db_connected || !$pdo) {
        set_flash('error', 'Notifications are unavailable while the database is disconnected.');
    } elseif ($action === 'mark_all_read') {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
            $stmt->execute(['user_id' => $user['id']]);
            set_flash('success', 'All notifications have been marked as read.');
        } catch (PDOException $e) {
            log_database_error('Mark all notifications as read', $e);
            set_flash('error', 'Your notifications could not be updated. Please try again.');
        }
    } elseif ($action === 'mark_read' && $notificationId !== false && $notificationId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = :id AND user_id = :user_id AND is_read = 0
            ");
            $stmt->execute([
                'id' => $notificationId,
                'user_id' => $user['id']
            ]);
            if ($stmt->rowCount() > 0) {
                set_flash('success', 'Notification marked as read.');
            }
        } catch (PDOException $e) {
            log_database_error('Mark notification as read', $e);
            set_flash('error', 'The notification could not be updated. Please try again.');
        }
    } else {
        set_flash('error', 'Choose a valid notification action.');
    }

    header("Location: " . site_url('notifications.php'));
    exit;
}

$notifications = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, message, link, is_read, created_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute(['user_id' => $user['id']]);
        $notifications = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_database_error('Load user notifications', $e);
        $notificationError = 'Your notifications could not be loaded. Please try again later.';
    }
} else {
    $notificationError = 'Your notifications are unavailable while the database is disconnected.';
}

$dashboardUrls = [
    'admin' => 'admin/dashboard.php',
    'teacher' => 'teacher/dashboard.php',
    'student' => 'student/dashboard.php',
    'accountant' => 'accountant/dashboard.php'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="notifications-page">
    <div class="container">
        <header class="notifications-heading">
            <div>
                <span class="section-subtitle">Your account</span>
                <h1>Notifications</h1>
                <p>Important updates about your account and activity.</p>
            </div>
            <div class="notifications-heading-actions">
                <a class="btn btn-outline" href="<?= site_url($dashboardUrls[$user['role']]); ?>">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to dashboard
                </a>
                <?php if ($notifications && array_filter($notifications, static fn($notification) => !(int)$notification['is_read'])): ?>
                    <form action="<?= site_url('notifications.php'); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <button class="btn btn-primary" type="submit" name="action" value="mark_all_read">
                            <i class="fas fa-check-double" aria-hidden="true"></i> Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($notificationError): ?>
            <div class="alert alert-danger" role="alert"><?= e($notificationError); ?></div>
        <?php endif; ?>

        <section class="notification-list" aria-label="Your notifications">
            <?php if (!$notificationError && !$notifications): ?>
                <div class="notifications-empty">
                    <span class="notifications-empty-icon"><i class="far fa-bell" aria-hidden="true"></i></span>
                    <h2>You're all caught up</h2>
                    <p>New updates will appear here when they’re available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                        $internalLink = trim((string)($notification['link'] ?? ''));
                        $hasInternalLink = $internalLink !== ''
                            && !str_starts_with($internalLink, '//')
                            && !preg_match('/^[a-z][a-z0-9+.-]*:/i', $internalLink);
                    ?>
                    <article class="notification-item<?= (int)$notification['is_read'] ? '' : ' is-unread'; ?>">
                        <span class="notification-item-icon" aria-hidden="true">
                            <i class="<?= (int)$notification['is_read'] ? 'far fa-bell' : 'fas fa-bell'; ?>"></i>
                        </span>
                        <div class="notification-item-content">
                            <div class="notification-item-heading">
                                <h2><?= e($notification['title']); ?></h2>
                                <?php if (!(int)$notification['is_read']): ?>
                                    <span class="notification-unread-label">New</span>
                                <?php endif; ?>
                            </div>
                            <p><?= nl2br(e($notification['message'])); ?></p>
                            <time datetime="<?= e(date(DATE_ATOM, strtotime($notification['created_at']))); ?>">
                                <?= e(date('M j, Y · g:i A', strtotime($notification['created_at']))); ?>
                            </time>
                        </div>
                        <div class="notification-item-actions">
                            <?php if ($hasInternalLink): ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(site_url($internalLink)); ?>">Open</a>
                            <?php endif; ?>
                            <?php if (!(int)$notification['is_read']): ?>
                                <form action="<?= site_url('notifications.php'); ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="notification_id" value="<?= (int)$notification['id']; ?>">
                                    <button class="btn btn-outline btn-sm" type="submit" name="action" value="mark_read">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
