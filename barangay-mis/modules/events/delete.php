<?php
/**
 * modules/events/delete.php
 * Deleting an event cascades to its event_attendees rows (FK ON DELETE CASCADE).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/events/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT title FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $e = $stmt->fetch();
    if ($e) {
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'events', $id, $e['title']);
        flash_success('Event deleted.');
    } else {
        flash_error('Event not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/events/list.php');
exit;
