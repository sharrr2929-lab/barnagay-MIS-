<?php
/**
 * modules/complaints/update_status.php
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
$status = post_str('status');
$validStatuses = ['Pending', 'Processing', 'Released', 'Resolved'];

if ($id && in_array($status, $validStatuses, true)) {
    $stmt = $pdo->prepare('UPDATE requests SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    audit_log($pdo, 'update', 'requests', $id, 'Status -> ' . $status);
    flash_success('Status updated.');
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/complaints/list.php');
exit;
