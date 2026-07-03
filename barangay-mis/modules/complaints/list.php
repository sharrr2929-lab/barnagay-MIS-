<?php
/**
 * modules/complaints/list.php
 * Helpdesk-style tracking for document requests / general complaints.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT q.*, CONCAT(r.first_name, ' ', r.last_name) AS resident_name
        FROM requests q LEFT JOIN residents r ON r.id = q.resident_id WHERE 1=1";
$params = [];
if ($statusFilter !== '') {
    $sql .= ' AND q.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY q.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$statuses = ['Pending', 'Processing', 'Released', 'Resolved'];

$pageTitle = 'Requests & Complaints';
$activeMenu = 'requests';
$breadcrumbEyebrow = 'Front Desk';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Requests &amp; Complaints</h2>
        <a href="<?= BASE_URL ?>/modules/complaints/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Log Request</a>
    </div>
    <div class="panel-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Quick search..." onkeyup="quickFilter(this, 'reqTable')">
            </div>
            <div class="col-md-3">
                <form method="get">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if (empty($requests)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i>No requests logged yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="reqTable">
            <thead><tr><th>Requestor</th><th>Type</th><th>Details</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= clean($r['resident_name'] ?: $r['requestor_name']) ?></td>
                    <td><?= clean($r['request_type']) ?></td>
                    <td class="text-truncate" style="max-width:260px;"><?= clean($r['details'] ?: '—') ?></td>
                    <td><?= format_date($r['created_at']) ?></td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>/modules/complaints/update_status.php" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="text-end">
                        <form method="post" action="<?= BASE_URL ?>/modules/complaints/delete.php" class="d-inline" data-confirm="Delete this request record?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="reqTableNoMatch" style="display:none;"><td colspan="6" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
