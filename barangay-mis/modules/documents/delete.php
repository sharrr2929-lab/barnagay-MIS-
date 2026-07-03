<?php
/**
 * modules/documents/delete.php
 * POST-only. Removes an issuance log entry (e.g. to correct a mistake).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/documents/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT document_type FROM documents_issued WHERE id = ?');
    $stmt->execute([$id]);
    $d = $stmt->fetch();
    if ($d) {
        $pdo->prepare('DELETE FROM documents_issued WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'documents_issued', $id, $d['document_type']);
        flash_success('Issuance record deleted.');
    } else {
        flash_error('Record not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/documents/list.php');
exit;
