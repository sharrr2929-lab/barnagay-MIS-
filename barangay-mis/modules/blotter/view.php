<?php
/**
 * modules/blotter/view.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT b.*, u.full_name AS filed_by_name FROM blotter_reports b LEFT JOIN users u ON u.id = b.filed_by WHERE b.id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    flash_error('Blotter report not found.');
    header('Location: ' . BASE_URL . '/modules/blotter/list.php');
    exit;
}

$barangayName = setting($pdo, 'barangay_name', 'Barangay Malaya');

$pageTitle = 'Blotter Report ' . ($report['case_number'] ?? '');
$activeMenu = 'blotter';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= BASE_URL ?>/modules/blotter/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    <div class="d-flex gap-2">
        <button onclick="printSection()" class="btn btn-sm btn-light border"><i class="bi bi-printer me-1"></i>Print</button>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/blotter/form.php?id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="panel" style="max-width:820px;">
    <div class="panel-header">
        <div>
            <div class="text-soft small">Republic of the Philippines · <?= clean($barangayName) ?></div>
            <h2 class="mb-0">Barangay Blotter Report</h2>
        </div>
        <span class="status-pill <?= strtolower(str_replace([' ', '/'], ['-', '-'], $report['status'])) ?>"><span class="dot"></span><?= clean($report['status']) ?></span>
    </div>
    <div class="panel-body">
        <div class="row g-3 mb-3">
            <div class="col-sm-4"><div class="text-soft small">Case Number</div><div class="mono fw-semibold"><?= clean($report['case_number'] ?? '—') ?></div></div>
            <div class="col-sm-4"><div class="text-soft small">Date &amp; Time</div><div><?= format_datetime($report['incident_date']) ?></div></div>
            <div class="col-sm-4"><div class="text-soft small">Incident Type</div><div><?= clean($report['incident_type'] ?: '—') ?></div></div>
            <div class="col-sm-6"><div class="text-soft small">Complainant</div><div class="fw-semibold"><?= clean($report['complainant']) ?></div></div>
            <div class="col-sm-6"><div class="text-soft small">Respondent</div><div><?= clean($report['respondent'] ?: '—') ?></div></div>
            <div class="col-12"><div class="text-soft small">Location</div><div><?= clean($report['location'] ?: '—') ?></div></div>
        </div>
        <hr>
        <div class="text-soft small mb-1">Narrative</div>
        <p style="white-space:pre-wrap;"><?= clean($report['narrative'] ?: 'No narrative recorded.') ?></p>
        <hr>
        <div class="text-soft small">Filed by <?= clean($report['filed_by_name'] ?? 'System') ?> on <?= format_date($report['created_at']) ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
