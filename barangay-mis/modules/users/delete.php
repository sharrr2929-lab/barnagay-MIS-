<?php
/**
 * modules/users/delete.php
 * Prevents an admin from deleting their own currently-logged-in account.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ADMIN_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/users/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id === current_user_id()) {
    flash_error('You cannot delete your own account while logged in.');
} elseif ($id) {
    $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'users', $id, $u['username']);
        flash_success('User account deleted.');
    } else {
        flash_error('User not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/users/list.php');
exit;
