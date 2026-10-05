<?php
/**
 * Global Header Component
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = $page_title ?? 'DIGITAL SMART CLASS — Professional E-Learning Platform';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= site_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?= site_url('assets/css/auth.css'); ?>">
    <link rel="stylesheet" href="<?= site_url('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?= site_url('assets/css/responsive.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<?php if (!$db_connected): ?>
    <div class="db-banner">
        <i class="fas fa-info-circle"></i> <strong>Running in Preview Mode:</strong> MySQL is not connected.
        Start Apache &amp; MySQL in XAMPP and import <code>database/digital_smart_class.sql</code> to activate live database records.
    </div>
<?php endif; ?>

<div class="main-content">
    <div class="container" style="padding-top: 1rem;">
        <?= render_flash_messages(); ?>
    </div>
