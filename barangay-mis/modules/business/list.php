<?php
/**
 * modules/business/list.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$businesses = $pdo->query(
    "SELECT b.*, CONCAT(r.first_name, ' ', r.last_name) AS owner_name
     FROM businesses b
     LEFT JOIN residents r ON r.id = b.owner_resident_id
     ORDER BY b.date_expiry ASC"
)->fetchAll();

$pageTitle = 'Business Permits';
$activeMenu = 'business';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Business Permits</h2>
        <a href="<?= BASE_URL ?>/modules/business/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Business</a>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control mb-3" style="max-width:320px;" placeholder="Quick search..." onkeyup="quickFilter(this, 'bizTable')">

        <?php if (empty($businesses)): ?>
            <div class="empty-state"><i class="bi bi-shop"></i>No businesses registered yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="bizTable">
            <thead><tr><th>Business Name</th><th>Owner</th><th>Type</th><th>Permit #</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($businesses as $b):
                $expiringSoon = $b['status'] === 'Active' && $b['date_expiry'] && strtotime($b['date_expiry']) <= strtotime('+60 days');
            ?>
                <tr>
                    <td class="fw-semibold"><?= clean($b['business_name']) ?></td>
                    <td><?= clean($b['owner_name'] ?? '—') ?></td>
                    <td><?= clean($b['business_type'] ?: '—') ?></td>
                    <td class="mono"><?= clean($b['permit_number'] ?: '—') ?></td>
                    <td><?= format_date($b['date_expiry']) ?> <?php if ($expiringSoon): ?><span class="badge bg-warning text-dark ms-1">Soon</span><?php endif; ?></td>
                    <td><span class="status-pill <?= strtolower($b['status']) ?>"><span class="dot"></span><?= clean($b['status']) ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/business/print.php?id=<?= (int)$b['id'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-printer"></i></a>
                        <a href="<?= BASE_URL ?>/modules/business/form.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= BASE_URL ?>/modules/business/delete.php" class="d-inline" data-confirm="Delete permit for <?= clean($b['business_name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="bizTableNoMatch" style="display:none;"><td colspan="7" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
