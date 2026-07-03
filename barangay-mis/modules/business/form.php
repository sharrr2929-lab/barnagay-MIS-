<?php
/**
 * modules/business/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$biz = [
    'owner_resident_id' => '', 'business_name' => '', 'business_type' => '', 'address' => '',
    'permit_number' => '', 'date_issued' => date('Y-m-d'), 'date_expiry' => date('Y-m-d', strtotime('+1 year')), 'status' => 'Active',
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM businesses WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Business record not found.');
        header('Location: ' . BASE_URL . '/modules/business/list.php');
        exit;
    }
    $biz = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $biz['owner_resident_id'] = post_int('owner_resident_id');
    $biz['business_name']     = post_str('business_name');
    $biz['business_type']     = post_str('business_type');
    $biz['address']           = post_str('address');
    $biz['permit_number']     = post_str('permit_number');
    $biz['date_issued']       = post_str('date_issued');
    $biz['date_expiry']       = post_str('date_expiry');
    $biz['status']            = post_str('status', 'Active');

    if ($biz['business_name'] === '') $errors[] = 'Business name is required.';

    if (empty($errors)) {
        try {
            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE businesses SET owner_resident_id=?, business_name=?, business_type=?, address=?, permit_number=?, date_issued=?, date_expiry=?, status=? WHERE id=?'
                );
                $stmt->execute([
                    $biz['owner_resident_id'], $biz['business_name'], $biz['business_type'], $biz['address'],
                    $biz['permit_number'] ?: null, $biz['date_issued'] ?: null, $biz['date_expiry'] ?: null, $biz['status'], $id,
                ]);
                audit_log($pdo, 'update', 'businesses', $id, $biz['business_name']);
                flash_success('Business permit updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO businesses (owner_resident_id, business_name, business_type, address, permit_number, date_issued, date_expiry, status)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $biz['owner_resident_id'], $biz['business_name'], $biz['business_type'], $biz['address'],
                    $biz['permit_number'] ?: null, $biz['date_issued'] ?: null, $biz['date_expiry'] ?: null, $biz['status'],
                ]);
                $newId = (int)$pdo->lastInsertId();
                audit_log($pdo, 'create', 'businesses', $newId, $biz['business_name']);
                flash_success('Business permit added.');
            }
            header('Location: ' . BASE_URL . '/modules/business/list.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'Duplicate')
                ? 'That permit number is already in use.'
                : 'Could not save this record. Please try again.';
        }
    }
}

$residents = get_residents_for_select($pdo);

$pageTitle = $id ? 'Edit Business Permit' : 'Add Business';
$activeMenu = 'business';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:760px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Business Permit' : 'Register a Business' ?></h2>
        <a href="<?= BASE_URL ?>/modules/business/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Business Name <span class="required-mark">*</span></label>
                    <input type="text" name="business_name" class="form-control" required value="<?= clean($biz['business_name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Business Type</label>
                    <input type="text" name="business_type" class="form-control" placeholder="Retail, Food Service..." value="<?= clean($biz['business_type']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Owner (Resident)</label>
                    <select name="owner_resident_id" class="form-select">
                        <option value="">— Select owner —</option>
                        <?php foreach ($residents as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (string)$biz['owner_resident_id'] === (string)$r['id'] ? 'selected' : '' ?>><?= clean(full_name_of($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Permit Number</label>
                    <input type="text" name="permit_number" class="form-control" placeholder="BP-2026-0003" value="<?= clean($biz['permit_number']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Business Address</label>
                    <input type="text" name="address" class="form-control" value="<?= clean($biz['address']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Issued</label>
                    <input type="date" name="date_issued" class="form-control" value="<?= clean($biz['date_issued']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Expiry</label>
                    <input type="date" name="date_expiry" class="form-control" value="<?= clean($biz['date_expiry']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Active','Expired','Revoked'] as $s): ?>
                            <option value="<?= $s ?>" <?= $biz['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                <a href="<?= BASE_URL ?>/modules/business/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
