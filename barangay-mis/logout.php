<?php
/**
 * logout.php
 * Destroys the session and returns to the login screen.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    require_once __DIR__ . '/includes/functions.php';
    audit_log($pdo, 'logout', 'users', current_user_id());
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
