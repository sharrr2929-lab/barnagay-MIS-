<?php
/**
 * modules/residents/view.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT r.*, h.household_number, p.name AS purok_name
     FROM residents r
     LEFT JOIN households h ON h.id = r.household_id
     LEFT JOIN puroks p ON p.id = r.purok_id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$resident = $stmt->fetch();

if (!$resident) {
    flash_error('Resident not found.');
    header('Location: ' . BASE_URL . '/modules/residents/list.php');
    exit;
}

$householdMembers = [];
if ($resident['household_id']) {
    $stmt = $pdo->prepare('SELECT id, first_name, middle_name, last_name, suffix, birthdate, sex FROM residents WHERE household_id = ? AND id != ? ORDER BY birthdate ASC');
    $stmt->execute([$resident['household_id'], $id]);
    $householdMembers = $stmt->fetchAll();
}

$stmt = $pdo->prepare('SELECT * FROM documents_issued WHERE resident_id = ? ORDER BY issued_at DESC');
$stmt->execute([$id]);
$documents = $stmt->fetchAll();

$pageTitle = 'Resident Profile';
$activeMenu = 'residents';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_URL ?>/modules/residents/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/documents/issue.php?resident_id=<?= $id ?>" class="btn btn-sm btn-gold"><i class="bi bi-file-earmark-plus me-1"></i>Issue Document</a>
        <a href="<?= BASE_URL ?>/modules/residents/form.php?id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-body text-center">
                <?php if (!empty($resident['photo'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/photos/<?= clean($resident['photo']) ?>" alt="Photo" class="rounded-circle mb-3" style="width:110px;height:110px;object-fit:cover;">
                <?php else: ?>
                    <div class="avatar-circle mx-auto mb-3" style="width:110px;height:110px;font-size:2.2rem;"><?= clean(mb_strtoupper(mb_substr($resident['first_name'], 0, 1))) ?></div>
                <?php endif; ?>
                <h3 class="mb-0"><?= clean(full_name_of($resident)) ?></h3>
                <div class="text-soft mb-2"><?= calculate_age($resident['birthdate']) ?> years old · <?= clean($resident['sex']) ?> · <?= clean($resident['civil_status']) ?></div>
                <span class="status-pill <?= clean($resident['status']) ?>"><span class="dot"></span><?= clean(ucfirst($resident['status'])) ?></span>
                <hr>
                <div class="text-start small">
                    <?php if ($resident['is_voter']): ?><span class="badge bg-light text-dark border me-1 mb-1">Registered Voter</span><?php endif; ?>
                    <?php if ($resident['is_senior']): ?><span class="badge bg-light text-dark border me-1 mb-1">Senior Citizen</span><?php endif; ?>
                    <?php if ($resident['is_pwd']): ?><span class="badge bg-light text-dark border me-1 mb-1">PWD</span><?php endif; ?>
                    <?php if ($resident['is_4ps']): ?><span class="badge bg-light text-dark border me-1 mb-1">4Ps Beneficiary</span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h3>Personal Information</h3></div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-sm-6"><div class="text-soft small">Birthdate</div><div><?= format_date($resident['birthdate']) ?></div></div>
                    <div class="col-sm-6"><div class="text-soft small">Occupation</div><div><?= clean($resident['occupation'] ?: '—') ?></div></div>
                    <div class="col-sm-6"><div class="text-soft small">Contact Number</div><div><?= clean($resident['contact_number'] ?: '—') ?></div></div>
                    <div class="col-sm-6"><div class="text-soft small">Purok/Zone</div><div><?= clean($resident['purok_name'] ?? '—') ?></div></div>
                    <div class="col-sm-6"><div class="text-soft small">Household</div><div><?= clean($resident['household_number'] ?? 'Not linked') ?></div></div>
                    <div class="col-12"><div class="text-soft small">Complete Address</div><div><?= clean($resident['address'] ?: '—') ?></div></div>
                </div>
            </div>
        </div>

        <?php if ($householdMembers): ?>
        <div class="panel">
            <div class="panel-header"><h3>Other Household Members</h3></div>
            <div class="panel-body p-0">
                <table class="table table-clean mb-0">
                    <thead><tr><th>Name</th><th>Age</th><th>Sex</th></tr></thead>
                    <tbody>
                    <?php foreach ($householdMembers as $m): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/modules/residents/view.php?id=<?= (int)$m['id'] ?>"><?= clean(full_name_of($m)) ?></a></td>
                            <td><?= calculate_age($m['birthdate']) ?></td>
                            <td><?= clean($m['sex']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header"><h3>Document Issuance History</h3></div>
            <div class="panel-body p-0">
                <?php if (empty($documents)): ?>
                    <div class="empty-state py-4"><i class="bi bi-file-earmark-text"></i>No documents issued yet.</div>
                <?php else: ?>
                <table class="table table-clean mb-0">
                    <thead><tr><th>Document</th><th>Purpose</th><th>Fee</th><th>Date Issued</th></tr></thead>
                    <tbody>
                    <?php foreach ($documents as $d): ?>
                        <tr>
                            <td><?= clean($d['document_type']) ?></td>
                            <td><?= clean($d['purpose'] ?: '—') ?></td>
                            <td><?= format_currency($d['amount']) ?></td>
                            <td><?= format_date($d['issued_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
