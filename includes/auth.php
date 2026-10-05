<?php
/**
 * Authentication and Role-Based Access Control (RBAC)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user array or null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'role'      => $_SESSION['user_role'] ?? 'student',
        'role_id'   => $_SESSION['user_role_id'] ?? 3,
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email'     => $_SESSION['user_email'] ?? '',
    ];
}

/**
 * Check if the user has a specific role
 */
function has_role($role) {
    if (!is_logged_in()) return false;
    $roles = is_array($role) ? $role : [$role];
    return in_array(strtolower($_SESSION['user_role'] ?? ''), array_map('strtolower', $roles));
}

/**
 * Require user to be logged in
 */
function require_login($redirectUrl = null) {
    if (!is_logged_in()) {
        $url = $redirectUrl ?? site_url('login.php');
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header("Location: " . $url);
        exit;
    }
}

/**
 * Enforce role access control. E.g. require_role(['admin', 'accountant'])
 */
function require_role($allowedRoles, $fallbackUrl = null) {
    require_login();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!has_role($roles)) {
        $_SESSION['flash_error'] = 'Unauthorized access: You do not have permission for this section.';
        $url = $fallbackUrl ?? site_url('index.php');
        header("Location: " . $url);
        exit;
    }
}

/**
 * Log in a user by populating session variables
 */
function login_user($user) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_name']    = $user['full_name'];
    $_SESSION['user_email']   = $user['email'];
    $_SESSION['user_role']    = $user['role_name'] ?? 'student';
    $_SESSION['user_role_id'] = $user['role_id'] ?? 3;
}

/**
 * Terminate user session
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
