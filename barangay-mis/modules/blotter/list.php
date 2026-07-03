<?php
/**
 * modules/blotter/list.php
 * Viewable by all roles (tanod included); add/edit/delete restricted to staff roles.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT b.*, u.full_name AS filed_by_name FROM blotter_reports b LEFT JOIN users u ON u.id = b.filed_by WHERE 1=1';
$params = [];
if ($statusFilter !== '') {
    $sql .= ' AND b.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY b.incident_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$statuses = ['Open', 'Under Mediation', 'Settled', 'Endorsed to Police/Court'];

$pageTitle = 'Blotter Reports';
$activeMenu = 'blotter';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Blotter / Incident Reports</h2>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/blotter/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>File a Report</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Quick search..." onkeyup="quickFilter(this, 'blotterTable')">
            </div>
            <div class="col-md-3">
                <form method="get">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= clean($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= clean($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if (empty($reports)): ?>
            <div class="empty-state"><i class="bi bi-journal-text"></i>No blotter reports match this view.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="blotterTable">
            <thead><tr><th>Case #</th><th>Complainant</th><th>Respondent</th><th>Type</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="mono"><?= clean($r['case_number'] ?? '—') ?></td>
                    <td><?= clean($r['complainant']) ?></td>
                    <td><?= clean($r['respondent'] ?: '—') ?></td>
                    <td><?= clean($r['incident_type'] ?: '—') ?></td>
                    <td><?= format_datetime($r['incident_date'], 'M d, Y') ?></td>
                    <td><span class="status-pill <?= strtolower(str_replace([' ', '/'], ['-', '-'], $r['status'])) ?>"><span class="dot"></span><?= clean($r['status']) ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/blotter/view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>
                        <?php if ($canManage): ?>
                        <a href="<?= BASE_URL ?>/modules/blotter/form.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="blotterTableNoMatch" style="display:none;"><td colspan="7" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
