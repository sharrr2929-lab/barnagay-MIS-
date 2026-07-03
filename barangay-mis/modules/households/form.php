<?php
/**
 * modules/households/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$household = ['household_number' => '', 'head_resident_id' => '', 'purok_id' => '', 'address' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM households WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Household not found.');
        header('Location: ' . BASE_URL . '/modules/households/list.php');
        exit;
    }
    $household = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $household['household_number'] = post_str('household_number');
    $household['head_resident_id'] = post_int('head_resident_id');
    $household['purok_id']        = post_int('purok_id');
    $household['address']          = post_str('address');

    if ($household['household_number'] === '') {
        $errors[] = 'Household number is required.';
    }

    if (empty($errors)) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE households SET household_number=?, head_resident_id=?, purok_id=?, address=? WHERE id=?');
                $stmt->execute([$household['household_number'], $household['head_resident_id'], $household['purok_id'], $household['address'], $id]);
                audit_log($pdo, 'update', 'households', $id, $household['household_number']);
                flash_success('Household updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO households (household_number, head_resident_id, purok_id, address) VALUES (?,?,?,?)');
                $stmt->execute([$household['household_number'], $household['head_resident_id'], $household['purok_id'], $household['address']]);
                $newId = (int)$pdo->lastInsertId();
                audit_log($pdo, 'create', 'households', $newId, $household['household_number']);
                flash_success('Household added.');
            }
            header('Location: ' . BASE_URL . '/modules/households/list.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'Duplicate')
                ? 'That household number already exists.'
                : 'Could not save the household. Please try again.';
        }
    }
}

$residents = get_residents_for_select($pdo);
$puroks = get_puroks($pdo);

$pageTitle = $id ? 'Edit Household' : 'Add Household';
$activeMenu = 'households';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Household' : 'Add New Household' ?></h2>
        <a href="<?= BASE_URL ?>/modules/households/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Household Number <span class="required-mark">*</span></label>
                    <input type="text" name="household_number" class="form-control" required placeholder="HH-0006" value="<?= clean($household['household_number']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purok/Zone</label>
                    <select name="purok_id" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($puroks as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (string)$household['purok_id'] === (string)$p['id'] ? 'selected' : '' ?>><?= clean($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Head of Household</label>
                    <select name="head_resident_id" class="form-select">
                        <option value="">— Not set —</option>
                        <?php foreach ($residents as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (string)$household['head_resident_id'] === (string)$r['id'] ? 'selected' : '' ?>><?= clean(full_name_of($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Tip: register the household first, then link members to it from each resident's edit form.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= clean($household['address']) ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Household</button>
                <a href="<?= BASE_URL ?>/modules/households/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
