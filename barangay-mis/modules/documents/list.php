<?php
/**
 * modules/documents/list.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$documents = $pdo->query(
    "SELECT d.*, r.first_name, r.middle_name, r.last_name, r.suffix, u.full_name AS issued_by_name
     FROM documents_issued d
     JOIN residents r ON r.id = d.resident_id
     LEFT JOIN users u ON u.id = d.issued_by
     ORDER BY d.issued_at DESC"
)->fetchAll();

$totalFees = array_sum(array_column($documents, 'amount'));

$pageTitle = 'Certificates & Documents';
$activeMenu = 'documents';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-file-earmark-text fs-5"></i></div><div class="stat-number"><?= count($documents) ?></div><div class="stat-label">Documents Issued (all time)</div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-cash-coin fs-5"></i></div><div class="stat-number"><?= format_currency($totalFees) ?></div><div class="stat-label">Total Fees Collected</div></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Issuance Log</h2>
        <a href="<?= BASE_URL ?>/modules/documents/issue.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Issue Document</a>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control mb-3" style="max-width:320px;" placeholder="Quick search..." onkeyup="quickFilter(this, 'docsTable')">

        <?php if (empty($documents)): ?>
            <div class="empty-state"><i class="bi bi-file-earmark-text"></i>No documents issued yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="docsTable">
            <thead><tr><th>Resident</th><th>Document Type</th><th>Purpose</th><th>Fee</th><th>Issued</th><th>Issued By</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
                <tr>
                    <td><?= clean(full_name_of($d)) ?></td>
                    <td><?= clean($d['document_type']) ?></td>
                    <td><?= clean($d['purpose'] ?: '—') ?></td>
                    <td><?= format_currency($d['amount']) ?></td>
                    <td><?= format_date($d['issued_at']) ?></td>
                    <td><?= clean($d['issued_by_name'] ?? '—') ?></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/documents/print.php?id=<?= (int)$d['id'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-printer"></i></a>
                        <form method="post" action="<?= BASE_URL ?>/modules/documents/delete.php" class="d-inline" data-confirm="Delete this issuance record?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="docsTableNoMatch" style="display:none;"><td colspan="7" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
