<?php
/**
 * modules/households/delete.php
 * POST-only. Residents in this household are NOT deleted — their
 * household_id is set to NULL (FK ON DELETE SET NULL).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/households/list.php');
    exit;
}

verify_csrf();
$id = post_int('id');

if ($id) {
    $stmt = $pdo->prepare('SELECT household_number FROM households WHERE id = ?');
    $stmt->execute([$id]);
    $h = $stmt->fetch();
    if ($h) {
        $pdo->prepare('DELETE FROM households WHERE id = ?')->execute([$id]);
        audit_log($pdo, 'delete', 'households', $id, $h['household_number']);
        flash_success('Household deleted. Its members were kept but unlinked.');
    } else {
        flash_error('Household not found.');
    }
} else {
    flash_error('Invalid request.');
}

header('Location: ' . BASE_URL . '/modules/households/list.php');
exit;
