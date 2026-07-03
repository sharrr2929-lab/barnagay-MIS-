<?php
/**
 * modules/dispatch/form.php
 * Logs a new incoming dispatch call. Open to all roles.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);

$errors = [];
$call = ['caller_name' => '', 'caller_contact' => '', 'incident_type' => '', 'location' => '', 'description' => '', 'priority' => 'Medium'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $call['caller_name']    = post_str('caller_name');
    $call['caller_contact'] = post_str('caller_contact');
    $call['incident_type']  = post_str('incident_type');
    $call['location']       = post_str('location');
    $call['description']    = post_str('description');
    $call['priority']       = post_str('priority', 'Medium');

    if ($call['caller_name'] === '') $errors[] = 'Caller name is required.';
    if ($call['incident_type'] === '') $errors[] = 'Incident type is required.';
    if ($call['location'] === '') $errors[] = 'Location is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO dispatch_calls (caller_name, caller_contact, incident_type, location, description, priority, status, created_by)
             VALUES (?,?,?,?,?,?,\'Pending\',?)'
        );
        $stmt->execute([
            $call['caller_name'], $call['caller_contact'], $call['incident_type'], $call['location'],
            $call['description'], $call['priority'], current_user_id(),
        ]);
        $newId = (int)$pdo->lastInsertId();
        audit_log($pdo, 'create', 'dispatch_calls', $newId, $call['incident_type']);
        flash_success('Call logged. It now appears on the Dispatch Board.');
        header('Location: ' . BASE_URL . '/modules/dispatch/board.php');
        exit;
    }
}

$pageTitle = 'Log New Call';
$activeMenu = 'dispatch';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:680px;">
    <div class="panel-header">
        <h2>Log an Incoming Call</h2>
        <a href="<?= BASE_URL ?>/modules/dispatch/board.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to board</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Caller Name <span class="required-mark">*</span></label>
                    <input type="text" name="caller_name" class="form-control" required value="<?= clean($call['caller_name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Caller Contact Number</label>
                    <input type="text" name="caller_contact" class="form-control" value="<?= clean($call['caller_contact']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Incident Type <span class="required-mark">*</span></label>
                    <select name="incident_type" class="form-select" required>
                        <option value="">— Select —</option>
                        <?php foreach (['Medical','Fire','Flood','Peace and Order','Animal Complaint','Noise','Other'] as $t): ?>
                            <option value="<?= $t ?>" <?= $call['incident_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <?php foreach (['Low','Medium','High','Emergency'] as $p): ?>
                            <option value="<?= $p ?>" <?= $call['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Location <span class="required-mark">*</span></label>
                    <input type="text" name="location" class="form-control" required value="<?= clean($call['location']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control"><?= clean($call['description']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-telephone-plus me-1"></i>Log Call</button>
                <a href="<?= BASE_URL ?>/modules/dispatch/board.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
