<?php
/**
 * modules/documents/issue.php
 * Logs a new issued document, then redirects straight to the printable
 * certificate so front-desk staff can hand it over immediately.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$errors = [];
$preselectResident = (int)($_GET['resident_id'] ?? 0);

$documentFees = [
    'Barangay Clearance'          => (float)setting($pdo, 'clearance_fee', '50'),
    'Certificate of Residency'    => (float)setting($pdo, 'residency_fee', '30'),
    'Certificate of Indigency'    => (float)setting($pdo, 'indigency_fee', '0'),
    'Business Clearance'          => (float)setting($pdo, 'business_clearance_fee', '200'),
    'Good Moral Certificate'      => (float)setting($pdo, 'good_moral_fee', '30'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $residentId = post_int('resident_id');
    $docType    = post_str('document_type');
    $purpose    = post_str('purpose');
    $orNumber   = post_str('or_number');
    $amount     = post_float('amount');

    if (!$residentId) $errors[] = 'Please select a resident.';
    if ($docType === '') $errors[] = 'Please select a document type.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO documents_issued (resident_id, document_type, purpose, or_number, amount, issued_by) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$residentId, $docType, $purpose, $orNumber ?: null, $amount, current_user_id()]);
        $newId = (int)$pdo->lastInsertId();
        audit_log($pdo, 'create', 'documents_issued', $newId, $docType);
        flash_success('Document issued and logged.');
        header('Location: ' . BASE_URL . '/modules/documents/print.php?id=' . $newId);
        exit;
    }
}

$residents = get_residents_for_select($pdo);

$pageTitle = 'Issue Document';
$activeMenu = 'documents';
$breadcrumbEyebrow = 'Records';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:720px;">
    <div class="panel-header">
        <h2>Issue a Document</h2>
        <a href="<?= BASE_URL ?>/modules/documents/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to log</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" id="issueForm" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Resident <span class="required-mark">*</span></label>
                    <select name="resident_id" class="form-select" required>
                        <option value="">— Select resident —</option>
                        <?php foreach ($residents as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $preselectResident === (int)$r['id'] ? 'selected' : '' ?>><?= clean(full_name_of($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Document Type <span class="required-mark">*</span></label>
                    <select name="document_type" id="documentType" class="form-select" required onchange="updateFee()">
                        <option value="">— Select document —</option>
                        <?php foreach ($documentFees as $type => $fee): ?>
                            <option value="<?= clean($type) ?>" data-fee="<?= $fee ?>"><?= clean($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Fee</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" step="0.01" min="0" name="amount" id="amountField" class="form-control" value="0.00">
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose" class="form-control" placeholder="e.g. Job application requirement">
                </div>
                <div class="col-md-4">
                    <label class="form-label">OR Number <span class="text-soft">(optional)</span></label>
                    <input type="text" name="or_number" class="form-control" placeholder="e.g. 00123">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-check me-1"></i>Issue &amp; Print</button>
                <a href="<?= BASE_URL ?>/modules/documents/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
    function updateFee() {
        var sel = document.getElementById('documentType');
        var opt = sel.options[sel.selectedIndex];
        var fee = opt ? (opt.getAttribute('data-fee') || 0) : 0;
        document.getElementById('amountField').value = parseFloat(fee).toFixed(2);
    }
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
