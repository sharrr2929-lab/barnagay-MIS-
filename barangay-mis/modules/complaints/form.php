<?php
/**
 * modules/complaints/form.php
 * Logs a new request/complaint. Can optionally link to a registered resident.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$errors = [];
$req = ['resident_id' => '', 'requestor_name' => '', 'request_type' => '', 'details' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $req['resident_id']    = post_int('resident_id');
    $req['requestor_name'] = post_str('requestor_name');
    $req['request_type']   = post_str('request_type');
    $req['details']        = post_str('details');

    if ($req['requestor_name'] === '' && !$req['resident_id']) {
        $errors[] = 'Select a resident or type a requestor name.';
    }
    if ($req['request_type'] === '') {
        $errors[] = 'Request type is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO requests (resident_id, requestor_name, request_type, details, status) VALUES (?,?,?,?,\'Pending\')');
        $stmt->execute([$req['resident_id'] ?: null, $req['requestor_name'], $req['request_type'], $req['details']]);
        $newId = (int)$pdo->lastInsertId();
        audit_log($pdo, 'create', 'requests', $newId, $req['request_type']);
        flash_success('Request logged.');
        header('Location: ' . BASE_URL . '/modules/complaints/list.php');
        exit;
    }
}

$residents = get_residents_for_select($pdo);

$pageTitle = 'Log Request';
$activeMenu = 'requests';
$breadcrumbEyebrow = 'Front Desk';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:640px;">
    <div class="panel-header">
        <h2>Log a Request / Complaint</h2>
        <a href="<?= BASE_URL ?>/modules/complaints/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Registered Resident <span class="text-soft">(optional)</span></label>
                    <select name="resident_id" class="form-select">
                        <option value="">— Not a registered resident —</option>
                        <?php foreach ($residents as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= clean(full_name_of($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Requestor Name <span class="text-soft">(if not on the list)</span></label>
                    <input type="text" name="requestor_name" class="form-control" value="<?= clean($req['requestor_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Request Type <span class="required-mark">*</span></label>
                    <input type="text" name="request_type" class="form-control" required list="reqTypes" value="<?= clean($req['request_type']) ?>">
                    <datalist id="reqTypes">
                        <option value="Barangay Clearance">
                        <option value="Certificate of Indigency">
                        <option value="Noise Complaint">
                        <option value="Neighbor Dispute">
                        <option value="Other">
                    </datalist>
                </div>
                <div class="col-12">
                    <label class="form-label">Details</label>
                    <textarea name="details" rows="4" class="form-control"><?= clean($req['details']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                <a href="<?= BASE_URL ?>/modules/complaints/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
