<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();

// Start new session to show clean flash message
session_start();
set_flash('info', 'You have been safely logged out.');
header("Location: " . site_url('login.php'));
exit;
