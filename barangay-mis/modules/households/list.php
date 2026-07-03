<?php
/**
 * modules/households/list.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$households = $pdo->query(
    "SELECT h.*, p.name AS purok_name,
            CONCAT(hr.first_name, ' ', hr.last_name) AS head_name,
            (SELECT COUNT(*) FROM residents r WHERE r.household_id = h.id) AS member_count
     FROM households h
     LEFT JOIN puroks p ON p.id = h.purok_id
     LEFT JOIN residents hr ON hr.id = h.head_resident_id
     ORDER BY h.household_number"
)->fetchAll();

$pageTitle = 'Households';
$activeMenu = 'households';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Household Records</h2>
        <a href="<?= BASE_URL ?>/modules/households/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Household</a>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control mb-3" style="max-width:320px;" placeholder="Quick search..." onkeyup="quickFilter(this, 'householdsTable')">

        <?php if (empty($households)): ?>
            <div class="empty-state"><i class="bi bi-houses"></i>No households recorded yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="householdsTable">
            <thead><tr><th>Household #</th><th>Head of Household</th><th>Purok</th><th>Address</th><th>Members</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($households as $h): ?>
                <tr>
                    <td class="mono fw-semibold"><?= clean($h['household_number']) ?></td>
                    <td><?= clean($h['head_name'] ?? '—') ?></td>
                    <td><?= clean($h['purok_name'] ?? '—') ?></td>
                    <td><?= clean($h['address'] ?: '—') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= (int)$h['member_count'] ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/households/form.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= BASE_URL ?>/modules/households/delete.php" class="d-inline" data-confirm="Delete household <?= clean($h['household_number']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="householdsTableNoMatch" style="display:none;"><td colspan="6" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
