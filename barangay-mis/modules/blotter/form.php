<?php
/**
 * modules/blotter/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$report = [
    'case_number' => '', 'complainant' => '', 'respondent' => '', 'incident_type' => '',
    'incident_date' => date('Y-m-d\TH:i'), 'location' => '', 'narrative' => '', 'status' => 'Open',
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM blotter_reports WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Blotter report not found.');
        header('Location: ' . BASE_URL . '/modules/blotter/list.php');
        exit;
    }
    $report = $found;
    if (!empty($report['incident_date'])) {
        $report['incident_date'] = str_replace(' ', 'T', substr($report['incident_date'], 0, 16));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $report['complainant']   = post_str('complainant');
    $report['respondent']    = post_str('respondent');
    $report['incident_type'] = post_str('incident_type');
    $report['incident_date'] = post_str('incident_date');
    $report['location']      = post_str('location');
    $report['narrative']     = post_str('narrative');
    $report['status']        = post_str('status', 'Open');

    if ($report['complainant'] === '') $errors[] = 'Complainant name is required.';
    if ($report['incident_date'] === '') $errors[] = 'Incident date/time is required.';

    if (empty($errors)) {
        $incidentDateSql = str_replace('T', ' ', $report['incident_date']) . ':00';
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE blotter_reports SET complainant=?, respondent=?, incident_type=?, incident_date=?, location=?, narrative=?, status=? WHERE id=?'
            );
            $stmt->execute([
                $report['complainant'], $report['respondent'], $report['incident_type'], $incidentDateSql,
                $report['location'], $report['narrative'], $report['status'], $id,
            ]);
            audit_log($pdo, 'update', 'blotter_reports', $id, $report['complainant']);
            flash_success('Blotter report updated.');
        } else {
            $caseNumber = generate_reference_number($pdo, 'blotter_reports', 'case_number', 'BLT');
            $stmt = $pdo->prepare(
                'INSERT INTO blotter_reports (case_number, complainant, respondent, incident_type, incident_date, location, narrative, status, filed_by)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $caseNumber, $report['complainant'], $report['respondent'], $report['incident_type'], $incidentDateSql,
                $report['location'], $report['narrative'], $report['status'], current_user_id(),
            ]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'blotter_reports', $newId, $caseNumber);
            flash_success("Blotter report filed as {$caseNumber}.");
        }
        header('Location: ' . BASE_URL . '/modules/blotter/list.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Blotter Report' : 'File a Blotter Report';
$activeMenu = 'blotter';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:820px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Blotter Report' . ($report['case_number'] ? ' — ' . clean($report['case_number']) : '') : 'File a Blotter Report' ?></h2>
        <a href="<?= BASE_URL ?>/modules/blotter/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Complainant <span class="required-mark">*</span></label>
                    <input type="text" name="complainant" class="form-control" required value="<?= clean($report['complainant']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Respondent</label>
                    <input type="text" name="respondent" class="form-control" placeholder="Leave blank if unknown" value="<?= clean($report['respondent']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Incident Type</label>
                    <input type="text" name="incident_type" class="form-control" placeholder="e.g. Noise Complaint, Dispute" value="<?= clean($report['incident_type']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date &amp; Time of Incident <span class="required-mark">*</span></label>
                    <input type="datetime-local" name="incident_date" class="form-control" required value="<?= clean($report['incident_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Open','Under Mediation','Settled','Endorsed to Police/Court'] as $s): ?>
                            <option value="<?= $s ?>" <?= $report['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= clean($report['location']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Narrative</label>
                    <textarea name="narrative" rows="5" class="form-control" placeholder="Describe what happened..."><?= clean($report['narrative']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Report</button>
                <a href="<?= BASE_URL ?>/modules/blotter/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
