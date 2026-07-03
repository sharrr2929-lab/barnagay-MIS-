<?php
/**
 * modules/residents/form.php
 * Handles both "Add Resident" and "Edit Resident" (pass ?id=).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$resident = [
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => '',
    'birthdate' => '', 'sex' => 'Male', 'civil_status' => 'Single', 'household_id' => '',
    'purok_id' => '', 'address' => '', 'contact_number' => '', 'occupation' => '',
    'is_voter' => 0, 'is_pwd' => 0, 'is_senior' => 0, 'is_4ps' => 0, 'status' => 'active', 'photo' => null,
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Resident not found.');
        header('Location: ' . BASE_URL . '/modules/residents/list.php');
        exit;
    }
    $resident = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $resident['first_name']     = post_str('first_name');
    $resident['middle_name']    = post_str('middle_name');
    $resident['last_name']      = post_str('last_name');
    $resident['suffix']         = post_str('suffix');
    $resident['birthdate']      = post_str('birthdate');
    $resident['sex']            = post_str('sex');
    $resident['civil_status']   = post_str('civil_status');
    $resident['household_id']  = post_int('household_id');
    $resident['purok_id']      = post_int('purok_id');
    $resident['address']        = post_str('address');
    $resident['contact_number'] = post_str('contact_number');
    $resident['occupation']     = post_str('occupation');
    $resident['is_voter']       = isset($_POST['is_voter']) ? 1 : 0;
    $resident['is_pwd']         = isset($_POST['is_pwd']) ? 1 : 0;
    $resident['is_senior']      = isset($_POST['is_senior']) ? 1 : 0;
    $resident['is_4ps']         = isset($_POST['is_4ps']) ? 1 : 0;
    $resident['status']         = post_str('status', 'active');

    if ($resident['first_name'] === '' || $resident['last_name'] === '') {
        $errors[] = 'First name and last name are required.';
    }
    if ($resident['birthdate'] === '') {
        $errors[] = 'Birthdate is required.';
    }

    $newPhoto = null;
    if (empty($errors)) {
        try {
            $newPhoto = handle_photo_upload('photo', 'photos');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($newPhoto) {
            $resident['photo'] = $newPhoto;
        }
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE residents SET household_id=?, first_name=?, middle_name=?, last_name=?, suffix=?, birthdate=?, sex=?,
                 civil_status=?, purok_id=?, address=?, contact_number=?, occupation=?, is_voter=?, is_pwd=?, is_senior=?, is_4ps=?, photo=?, status=?
                 WHERE id=?'
            );
            $stmt->execute([
                $resident['household_id'], $resident['first_name'], $resident['middle_name'], $resident['last_name'], $resident['suffix'],
                $resident['birthdate'], $resident['sex'], $resident['civil_status'], $resident['purok_id'], $resident['address'],
                $resident['contact_number'], $resident['occupation'], $resident['is_voter'], $resident['is_pwd'], $resident['is_senior'],
                $resident['is_4ps'], $resident['photo'], $resident['status'], $id,
            ]);
            audit_log($pdo, 'update', 'residents', $id, $resident['first_name'] . ' ' . $resident['last_name']);
            flash_success('Resident record updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO residents (household_id, first_name, middle_name, last_name, suffix, birthdate, sex, civil_status,
                 purok_id, address, contact_number, occupation, is_voter, is_pwd, is_senior, is_4ps, photo, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $resident['household_id'], $resident['first_name'], $resident['middle_name'], $resident['last_name'], $resident['suffix'],
                $resident['birthdate'], $resident['sex'], $resident['civil_status'], $resident['purok_id'], $resident['address'],
                $resident['contact_number'], $resident['occupation'], $resident['is_voter'], $resident['is_pwd'], $resident['is_senior'],
                $resident['is_4ps'], $resident['photo'], $resident['status'],
            ]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'residents', $newId, $resident['first_name'] . ' ' . $resident['last_name']);
            flash_success('Resident registered successfully.');
        }
        header('Location: ' . BASE_URL . '/modules/residents/list.php');
        exit;
    }
}

$households = $pdo->query('SELECT id, household_number FROM households ORDER BY household_number')->fetchAll();
$puroks = get_puroks($pdo);

$pageTitle = $id ? 'Edit Resident' : 'Add Resident';
$activeMenu = 'residents';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Resident' : 'Add New Resident' ?></h2>
        <a href="<?= BASE_URL ?>/modules/residents/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="required-mark">*</span></label>
                    <input type="text" name="first_name" class="form-control" required value="<?= clean($resident['first_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= clean($resident['middle_name']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Last Name <span class="required-mark">*</span></label>
                    <input type="text" name="last_name" class="form-control" required value="<?= clean($resident['last_name']) ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control" placeholder="Jr." value="<?= clean($resident['suffix']) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Birthdate <span class="required-mark">*</span></label>
                    <input type="date" name="birthdate" class="form-control" required value="<?= clean($resident['birthdate']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sex</label>
                    <select name="sex" class="form-select">
                        <option value="Male" <?= $resident['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $resident['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Civil Status</label>
                    <select name="civil_status" class="form-select">
                        <?php foreach (['Single','Married','Widowed','Separated','Divorced'] as $cs): ?>
                            <option value="<?= $cs ?>" <?= $resident['civil_status'] === $cs ? 'selected' : '' ?>><?= $cs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['active' => 'Active', 'deceased' => 'Deceased', 'moved_out' => 'Moved Out'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $resident['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Household</label>
                    <select name="household_id" class="form-select">
                        <option value="">— Not linked —</option>
                        <?php foreach ($households as $h): ?>
                            <option value="<?= (int)$h['id'] ?>" <?= (string)$resident['household_id'] === (string)$h['id'] ? 'selected' : '' ?>><?= clean($h['household_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purok/Zone</label>
                    <select name="purok_id" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($puroks as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (string)$resident['purok_id'] === (string)$p['id'] ? 'selected' : '' ?>><?= clean($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= clean($resident['contact_number']) ?>">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Complete Address</label>
                    <input type="text" name="address" class="form-control" value="<?= clean($resident['address']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="occupation" class="form-control" value="<?= clean($resident['occupation']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Photo <span class="text-soft">(optional, JPG/PNG/WEBP, max 3MB)</span></label>
                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($resident['photo'])): ?>
                        <div class="form-text">Current file: <?= clean($resident['photo']) ?> (uploading a new one will replace it)</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block">Flags</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="is_voter" id="is_voter" <?= $resident['is_voter'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_voter">Registered Voter</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="is_senior" id="is_senior" <?= $resident['is_senior'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_senior">Senior Citizen</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="is_pwd" id="is_pwd" <?= $resident['is_pwd'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_pwd">PWD</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="is_4ps" id="is_4ps" <?= $resident['is_4ps'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_4ps">4Ps Beneficiary</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $id ? 'Save Changes' : 'Register Resident' ?></button>
                <a href="<?= BASE_URL ?>/modules/residents/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
