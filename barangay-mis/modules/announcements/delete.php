<?php
/**
 * modules/announcements/delete.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/announcements/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT title FROM announcements WHERE id = ?');
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if ($a) {
        $pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'announcements', $id, $a['title']);
        flash_success('Announcement deleted.');
    } else {
        flash_error('Announcement not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/announcements/list.php');
exit;
