<?php
/**
 * modules/residents/list.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$purokFilter = isset($_GET['purok_id']) && $_GET['purok_id'] !== '' ? (int)$_GET['purok_id'] : null;

$sql = "SELECT r.*, h.household_number, p.name AS purok_name
        FROM residents r
        LEFT JOIN households h ON h.id = r.household_id
        LEFT JOIN puroks p ON p.id = r.purok_id
        WHERE 1=1";
$params = [];
if ($purokFilter) {
    $sql .= ' AND r.purok_id = ?';
    $params[] = $purokFilter;
}
$sql .= ' ORDER BY r.last_name, r.first_name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

$puroks = get_puroks($pdo);

$pageTitle = 'Residents';
$activeMenu = 'residents';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Resident Records</h2>
        <a href="<?= BASE_URL ?>/modules/residents/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Resident</a>
    </div>
    <div class="panel-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Quick search by name, contact..." onkeyup="quickFilter(this, 'residentsTable')">
            </div>
            <div class="col-md-3">
                <form method="get" class="d-flex">
                    <select name="purok_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Puroks</option>
                        <?php foreach ($puroks as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $purokFilter === (int)$p['id'] ? 'selected' : '' ?>><?= clean($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-md-auto ms-auto text-soft align-self-center small">
                <?= count($residents) ?> resident(s)
            </div>
        </div>

        <?php if (empty($residents)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                No residents found. Click "Add Resident" to register the first one.
            </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="residentsTable">
            <thead>
                <tr>
                    <th></th><th>Name</th><th>Age / Sex</th><th>Purok</th><th>Household</th><th>Contact</th><th>Flags</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($residents as $r): ?>
                <tr>
                    <td><span class="avatar-circle"><?= clean(mb_strtoupper(mb_substr($r['first_name'], 0, 1))) ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>/modules/residents/view.php?id=<?= (int)$r['id'] ?>" class="fw-semibold text-decoration-none">
                            <?= clean(full_name_of($r)) ?>
                        </a>
                    </td>
                    <td><?= calculate_age($r['birthdate']) ?> yrs · <?= clean($r['sex']) ?></td>
                    <td><?= clean($r['purok_name'] ?? '—') ?></td>
                    <td><?= clean($r['household_number'] ?? '—') ?></td>
                    <td><?= clean($r['contact_number'] ?: '—') ?></td>
                    <td>
                        <?php if ($r['is_voter']): ?><span class="badge bg-light text-dark border me-1" title="Registered Voter">Voter</span><?php endif; ?>
                        <?php if ($r['is_senior']): ?><span class="badge bg-light text-dark border me-1" title="Senior Citizen">Senior</span><?php endif; ?>
                        <?php if ($r['is_pwd']): ?><span class="badge bg-light text-dark border me-1" title="Person with Disability">PWD</span><?php endif; ?>
                        <?php if ($r['is_4ps']): ?><span class="badge bg-light text-dark border" title="4Ps Beneficiary">4Ps</span><?php endif; ?>
                    </td>
                    <td><span class="status-pill <?= clean($r['status']) ?>"><span class="dot"></span><?= clean(ucfirst($r['status'])) ?></span></td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/residents/view.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-eye me-2"></i>View Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/residents/form.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/documents/issue.php?resident_id=<?= (int)$r['id'] ?>"><i class="bi bi-file-earmark-text me-2"></i>Issue Document</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="<?= BASE_URL ?>/modules/residents/delete.php" data-confirm="Delete <?= clean(full_name_of($r)) ?>? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="residentsTableNoMatch" style="display:none;"><td colspan="9" class="text-center text-soft py-3">No matching residents.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
