<?php
/**
 * modules/dispatch/escalate.php
 * One-click escalation: carries a dispatch call's details into a new
 * formal Blotter Report and links the two records together.
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
$id = post_int('id');

$stmt = $pdo->prepare('SELECT * FROM dispatch_calls WHERE id = ?');
$stmt->execute([$id]);
$call = $stmt->fetch();

if (!$call) {
    flash_error('Dispatch call not found.');
    header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
    exit;
}

if (!empty($call['linked_blotter_id'])) {
    flash_info('This call has already been escalated to the blotter.');
    header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
    exit;
}

$pdo->beginTransaction();
try {
    $caseNumber = generate_reference_number($pdo, 'blotter_reports', 'case_number', 'BLT');
    $narrative = trim(
        ($call['description'] ?: '') .
        "\n\n[Escalated from Dispatch Call #{$call['id']}, logged " . format_datetime($call['called_at']) . ".]"
    );

    $stmt = $pdo->prepare(
        'INSERT INTO blotter_reports (case_number, complainant, respondent, incident_type, incident_date, location, narrative, status, filed_by)
         VALUES (?,?,?,?,?,?,?,\'Open\',?)'
    );
    $stmt->execute([
        $caseNumber, $call['caller_name'], null, $call['incident_type'], $call['called_at'],
        $call['location'], $narrative, current_user_id(),
    ]);
    $blotterId = (int)$pdo->lastInsertId();

    $pdo->prepare('UPDATE dispatch_calls SET linked_blotter_id = ? WHERE id = ?')->execute([$blotterId, $id]);

    $pdo->commit();
    audit_log($pdo, 'escalate', 'dispatch_calls', $id, "Escalated to blotter {$caseNumber}");
    flash_success("Escalated to Blotter Report {$caseNumber}.");
} catch (Exception $e) {
    $pdo->rollBack();
    flash_error('Could not escalate this call. Please try again.');
}

header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
exit;
