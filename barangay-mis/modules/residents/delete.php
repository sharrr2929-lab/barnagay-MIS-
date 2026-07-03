<?php
/**
 * modules/residents/delete.php
 * POST-only handler. Deleting a resident cascades to their documents_issued
 * rows (FK ON DELETE CASCADE) and detaches them from event_attendees.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/residents/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT first_name, last_name FROM residents WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();

    if ($r) {
        $pdo->prepare('DELETE FROM residents WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'residents', $id, $r['first_name'] . ' ' . $r['last_name']);
        flash_success('Resident record deleted.');
    } else {
        flash_error('Resident not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/residents/list.php');
exit;
