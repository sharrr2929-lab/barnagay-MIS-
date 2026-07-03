<?php
/**
 * modules/settings/index.php
 * Barangay profile, document fee schedule, and purok/zone management.
 * Restricted to ADMIN_ROLES.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ADMIN_ROLES);

$settingFields = [
    'barangay_name'    => 'Barangay Name',
    'barangay_address' => 'Barangay Address',
    'barangay_contact' => 'Contact Number',
    'barangay_email'   => 'Email Address',
    'captain_name'     => 'Punong Barangay (Captain) Name',
    'secretary_name'   => 'Barangay Secretary Name',
];
$feeFields = [
    'clearance_fee'          => 'Barangay Clearance Fee',
    'residency_fee'          => 'Certificate of Residency Fee',
    'indigency_fee'          => 'Certificate of Indigency Fee',
    'business_clearance_fee' => 'Business Clearance Fee',
    'good_moral_fee'         => 'Good Moral Certificate Fee',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $formAction = post_str('form_action');

    if ($formAction === 'save_settings') {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach (array_merge(array_keys($settingFields), array_keys($feeFields)) as $key) {
            $value = post_str($key);
            $stmt->execute([$key, $value]);
        }
        audit_log($pdo, 'update', 'settings', null, 'Barangay profile / fees updated');
        flash_success('Settings saved.');
    } elseif ($formAction === 'add_purok') {
        $name = post_str('purok_name');
        if ($name !== '') {
            $pdo->prepare('INSERT INTO puroks (name) VALUES (?)')->execute([$name]);
            flash_success('Purok added.');
        }
    } elseif ($formAction === 'delete_purok') {
        $purokId = post_int('purok_id');
        if ($purokId) {
            $pdo->prepare('DELETE FROM puroks WHERE id = ?')->execute([$purokId]);
            flash_success('Purok removed.');
        }
    }
    header('Location: ' . BASE_URL . '/modules/settings/index.php');
    exit;
}

$puroks = get_puroks($pdo);

$pageTitle = 'Settings';
$activeMenu = 'settings';
$breadcrumbEyebrow = 'Administration';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="save_settings">

            <div class="panel">
                <div class="panel-header"><h3>Barangay Profile</h3></div>
                <div class="panel-body">
                    <div class="row g-3">
                        <?php foreach ($settingFields as $key => $label): ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= clean($label) ?></label>
                            <input type="text" name="<?= $key ?>" class="form-control" value="<?= clean(setting($pdo, $key)) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>Document Fee Schedule</h3></div>
                <div class="panel-body">
                    <div class="row g-3">
                        <?php foreach ($feeFields as $key => $label): ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= clean($label) ?></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" name="<?= $key ?>" class="form-control" value="<?= clean(setting($pdo, $key, '0')) ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h3>Puroks / Zones</h3></div>
            <div class="panel-body">
                <form method="post" class="d-flex gap-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="add_purok">
                    <input type="text" name="purok_name" class="form-control form-control-sm" placeholder="New purok name" required>
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">Add</button>
                </form>
                <ul class="list-group list-group-flush">
                    <?php foreach ($puroks as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <?= clean($p['name']) ?>
                        <form method="post" data-confirm="Remove '<?= clean($p['name']) ?>'? Residents linked to it will keep their record but lose this purok tag.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="delete_purok">
                            <input type="hidden" name="purok_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($puroks)): ?>
                        <li class="list-group-item px-0 text-soft small">No puroks defined yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h3>System Info</h3></div>
            <div class="panel-body small text-soft">
                <div class="mb-1"><strong>App:</strong> <?= clean(APP_NAME) ?></div>
                <div class="mb-1"><strong>PHP Version:</strong> <?= clean(PHP_VERSION) ?></div>
                <div><strong>Server Time:</strong> <?= date('F d, Y g:i A') ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
