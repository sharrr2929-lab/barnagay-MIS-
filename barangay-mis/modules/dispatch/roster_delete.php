<?php
/**
 * modules/dispatch/roster_delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/dispatch/roster.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT full_name FROM tanod_roster WHERE id = ?');
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    if ($t) {
        $pdo->prepare('DELETE FROM tanod_roster WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'tanod_roster', $id, $t['full_name']);
        flash_success('Removed from roster.');
    } else {
        flash_error('Entry not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/dispatch/roster.php');
exit;
