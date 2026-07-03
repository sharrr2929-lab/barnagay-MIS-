<?php
/**
 * modules/business/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/business/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT business_name FROM businesses WHERE id = ?');
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if ($b) {
        $pdo->prepare('DELETE FROM businesses WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'businesses', $id, $b['business_name']);
        flash_success('Business record deleted.');
    } else {
        flash_error('Record not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/business/list.php');
exit;
