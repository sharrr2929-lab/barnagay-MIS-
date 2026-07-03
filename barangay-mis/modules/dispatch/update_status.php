<?php
/**
 * modules/dispatch/update_status.php
 * Updates a call's status/responder and auto-stamps lifecycle timestamps
 * the first time each stage is reached, so response time can be measured.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
    exit;
}

verify_csrf();
$id          = post_int('id');
$newStatus   = post_str('status');
$responderId = post_int('responder_id');
$validStatuses = ['Pending', 'Dispatched', 'En Route', 'On Scene', 'Resolved', 'Closed'];

if (!$id || !in_array($newStatus, $validStatuses, true)) {
    flash_error('Invalid update request.');
    header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM dispatch_calls WHERE id = ?');
$stmt->execute([$id]);
$call = $stmt->fetch();

if (!$call) {
    flash_error('Dispatch call not found.');
    header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
    exit;
}

$sets   = ['status = ?'];
$values = [$newStatus];

$sets[] = 'responder_id = ?';
$values[] = $responderId ?: null;

if ($newStatus === 'Dispatched' && empty($call['dispatched_at'])) {
    $sets[] = 'dispatched_at = NOW()';
}
if ($newStatus === 'On Scene' && empty($call['arrived_at'])) {
    $sets[] = 'arrived_at = NOW()';
}
if (in_array($newStatus, ['Resolved', 'Closed'], true) && empty($call['resolved_at'])) {
    $sets[] = 'resolved_at = NOW()';
}

$values[] = $id;
$sql = 'UPDATE dispatch_calls SET ' . implode(', ', $sets) . ' WHERE id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute($values);

audit_log($pdo, 'update', 'dispatch_calls', $id, 'Status -> ' . $newStatus);
flash_success('Dispatch call updated.');

header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
exit;
