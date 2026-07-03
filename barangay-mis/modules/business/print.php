<?php
/**
 * modules/business/print.php
 * Printable business permit slip (browser print-to-PDF).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT b.*, CONCAT(r.first_name, ' ', r.last_name) AS owner_name
     FROM businesses b LEFT JOIN residents r ON r.id = b.owner_resident_id WHERE b.id = ?"
);
$stmt->execute([$id]);
$biz = $stmt->fetch();

if (!$biz) {
    flash_error('Business record not found.');
    header('Location: ' . BASE_URL . '/modules/business/list.php');
    exit;
}

$barangayName    = setting($pdo, 'barangay_name', 'Barangay Malaya');
$barangayAddress = setting($pdo, 'barangay_address', '');
$captainName     = setting($pdo, 'captain_name', 'Hon. Barangay Captain');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print — Business Permit</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background:#EFECE2;">

<div class="no-print d-flex justify-content-between align-items-center p-3" style="max-width:800px;margin:0 auto;">
    <a href="<?= BASE_URL ?>/modules/business/list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    <button onclick="printSection()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
</div>

<div class="certificate my-4">
    <div class="cert-header">
        <div class="text-soft small">Republic of the Philippines</div>
        <div class="text-soft small">Office of the Punong Barangay</div>
        <div class="brgy-name"><?= clean(strtoupper($barangayName)) ?></div>
        <?php if ($barangayAddress): ?><div class="text-soft small"><?= clean($barangayAddress) ?></div><?php endif; ?>
    </div>

    <div class="cert-title">BARANGAY BUSINESS PERMIT</div>

    <div class="cert-body">
        <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
        <p>This is to certify that the business named <strong><?= clean($biz['business_name']) ?></strong>,
        owned by <strong><?= clean($biz['owner_name'] ?? 'N/A') ?></strong>, engaged in
        <strong><?= clean($biz['business_type'] ?: 'general business') ?></strong> and located at
        <?= clean($biz['address'] ?: '—') ?>, is hereby granted a permit to operate within the jurisdiction of
        <?= clean($barangayName) ?>, subject to compliance with existing barangay ordinances, zoning regulations,
        and applicable national laws.</p>
        <p>Permit Number: <strong class="mono"><?= clean($biz['permit_number'] ?: 'N/A') ?></strong></p>
        <p>Valid from <strong><?= format_date($biz['date_issued']) ?></strong> to <strong><?= format_date($biz['date_expiry']) ?></strong>,
        unless sooner revoked for cause.</p>
    </div>

    <div class="cert-sign">
        <div class="cert-sign-block">
            <div class="line"></div>
            <strong><?= clean(strtoupper($captainName)) ?></strong><br>
            <span class="text-soft small">Punong Barangay</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
