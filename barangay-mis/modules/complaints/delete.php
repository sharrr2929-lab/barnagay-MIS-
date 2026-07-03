<?php
/**
 * modules/complaints/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/complaints/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $pdo->prepare('DELETE FROM requests WHERE id = ?')->execute([$id]);
    audit_log($pdo, 'delete', 'requests', $id);
    flash_success('Request record deleted.');
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/complaints/list.php');
exit;
