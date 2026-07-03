<?php
/**
 * modules/blotter/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/blotter/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT case_number FROM blotter_reports WHERE id = ?');
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if ($b) {
        $pdo->prepare('DELETE FROM blotter_reports WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'blotter_reports', $id, $b['case_number'] ?? '');
        flash_success('Blotter report deleted.');
    } else {
        flash_error('Report not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/blotter/list.php');
exit;
