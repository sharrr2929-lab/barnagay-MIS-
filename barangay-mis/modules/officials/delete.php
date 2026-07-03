<?php
/**
 * modules/officials/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/officials/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT full_name FROM officials WHERE id = ?');
    $stmt->execute([$id]);
    $o = $stmt->fetch();
    if ($o) {
        $pdo->prepare('DELETE FROM officials WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'officials', $id, $o['full_name']);
        flash_success('Official removed.');
    } else {
        flash_error('Record not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/officials/list.php');
exit;
