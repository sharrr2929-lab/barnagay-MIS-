<?php
/**
 * modules/dispatch/roster_form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$person = ['full_name' => '', 'contact_number' => '', 'role' => 'Tanod', 'shift_schedule' => '', 'status' => 'Available'];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM tanod_roster WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Roster entry not found.');
        header('Location: ' . BASE_URL . '/modules/dispatch/roster.php');
        exit;
    }
    $person = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $person['full_name']      = post_str('full_name');
    $person['contact_number'] = post_str('contact_number');
    $person['role']           = post_str('role', 'Tanod');
    $person['shift_schedule'] = post_str('shift_schedule');
    $person['status']         = post_str('status', 'Available');

    if ($person['full_name'] === '') $errors[] = 'Full name is required.';

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE tanod_roster SET full_name=?, contact_number=?, role=?, shift_schedule=?, status=? WHERE id=?');
            $stmt->execute([$person['full_name'], $person['contact_number'], $person['role'], $person['shift_schedule'], $person['status'], $id]);
            audit_log($pdo, 'update', 'tanod_roster', $id, $person['full_name']);
            flash_success('Roster entry updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO tanod_roster (full_name, contact_number, role, shift_schedule, status) VALUES (?,?,?,?,?)');
            $stmt->execute([$person['full_name'], $person['contact_number'], $person['role'], $person['shift_schedule'], $person['status']]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'tanod_roster', $newId, $person['full_name']);
            flash_success('Personnel added to the roster.');
        }
        header('Location: ' . BASE_URL . '/modules/dispatch/roster.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Personnel' : 'Add Personnel';
$activeMenu = 'roster';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:560px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Personnel' : 'Add Personnel to Roster' ?></h2>
        <a href="<?= BASE_URL ?>/modules/dispatch/roster.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Full Name <span class="required-mark">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= clean($person['full_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= clean($person['contact_number']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" name="role" class="form-control" value="<?= clean($person['role']) ?>" placeholder="Tanod, Tanod Chief, BHW...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Shift Schedule</label>
                    <input type="text" name="shift_schedule" class="form-control" value="<?= clean($person['shift_schedule']) ?>" placeholder="Mon-Fri 6AM-2PM">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Available','On Call','Off Duty'] as $s): ?>
                            <option value="<?= $s ?>" <?= $person['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                <a href="<?= BASE_URL ?>/modules/dispatch/roster.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
