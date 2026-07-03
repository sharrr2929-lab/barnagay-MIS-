<?php
/**
 * modules/documents/print.php
 * Printable certificate. Uses the browser's native "Print > Save as PDF"
 * instead of a server-side PDF library, so it works on a fresh XAMPP
 * install with zero extra dependencies (no Composer / TCPDF required).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT d.*, r.first_name, r.middle_name, r.last_name, r.suffix, r.address, r.civil_status, r.birthdate, r.sex, p.name AS purok_name
     FROM documents_issued d
     JOIN residents r ON r.id = d.resident_id
     LEFT JOIN puroks p ON p.id = r.purok_id
     WHERE d.id = ?'
);
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    flash_error('Document record not found.');
    header('Location: ' . BASE_URL . '/modules/documents/list.php');
    exit;
}

$barangayName    = setting($pdo, 'barangay_name', 'Barangay Malaya');
$barangayAddress = setting($pdo, 'barangay_address', '');
$captainName     = setting($pdo, 'captain_name', 'Hon. Barangay Captain');
$secretaryName   = setting($pdo, 'secretary_name', 'Barangay Secretary');

$fullName = trim($doc['first_name'] . ' ' . (!empty($doc['middle_name']) ? $doc['middle_name'] . ' ' : '') . $doc['last_name'] . ' ' . $doc['suffix']);
$age = calculate_age($doc['birthdate']);
$pronoun = $doc['sex'] === 'Female' ? 'she' : 'he';
$purpose = $doc['purpose'] ?: 'whatever legal purpose it may serve';

$bodyParagraphs = match ($doc['document_type']) {
    'Barangay Clearance' => [
        "This is to certify that <strong>{$fullName}</strong>, of legal age, {$doc['civil_status']}, and a resident of {$doc['address']}, " .
        "Purok " . ($doc['purok_name'] ?: '—') . ", {$barangayName}, is known to this office to be of good standing in the community.",
        "This further certifies that, based on available records of this barangay, {$pronoun} has no pending derogatory case or complaint on file as of the date of this issuance.",
        "This Barangay Clearance is issued upon the request of the above-named person for {$purpose}.",
    ],
    'Certificate of Residency' => [
        "This is to certify that <strong>{$fullName}</strong>, {$age} years old, {$doc['civil_status']}, is a bona fide resident of {$doc['address']}, " .
        "Purok " . ($doc['purok_name'] ?: '—') . ", {$barangayName}.",
        "This certification is issued upon the request of the above-named resident for {$purpose}.",
    ],
    'Certificate of Indigency' => [
        "This is to certify that <strong>{$fullName}</strong>, {$age} years old, {$doc['civil_status']}, and a resident of {$doc['address']}, " .
        "Purok " . ($doc['purok_name'] ?: '—') . ", {$barangayName}, belongs to an indigent family in this barangay based on the records and assessment of this office.",
        "This certification is issued upon the request of the above-named person for {$purpose}.",
    ],
    'Business Clearance' => [
        "This is to certify that <strong>{$fullName}</strong>, of legal age, {$doc['civil_status']}, and a resident of {$doc['address']}, " .
        "Purok " . ($doc['purok_name'] ?: '—') . ", {$barangayName}, is hereby granted clearance to operate a business within the jurisdiction of this barangay, " .
        "subject to compliance with existing barangay ordinances and applicable national laws.",
        "This clearance is issued upon the request of the above-named person for {$purpose}.",
    ],
    'Good Moral Certificate' => [
        "This is to certify that <strong>{$fullName}</strong>, {$age} years old, {$doc['civil_status']}, and a resident of {$doc['address']}, " .
        "Purok " . ($doc['purok_name'] ?: '—') . ", {$barangayName}, is personally known to this office to be a person of good moral character and reputation, " .
        "and has not been involved in any activity contrary to law or public morals as far as the records of this barangay show.",
        "This certification is issued upon the request of the above-named person for {$purpose}.",
    ],
    default => [
        "This is to certify that <strong>{$fullName}</strong>, a resident of {$doc['address']}, {$barangayName}, is the subject of this document, issued for {$purpose}.",
    ],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print — <?= clean($doc['document_type']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background:#EFECE2;">

<div class="no-print d-flex justify-content-between align-items-center p-3" style="max-width:800px;margin:0 auto;">
    <a href="<?= BASE_URL ?>/modules/documents/list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to log</a>
    <button onclick="printSection()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
</div>

<div class="certificate my-4">
    <div class="cert-header">
        <div class="text-soft small">Republic of the Philippines</div>
        <div class="text-soft small">Office of the Punong Barangay</div>
        <div class="brgy-name"><?= clean(strtoupper($barangayName)) ?></div>
        <?php if ($barangayAddress): ?><div class="text-soft small"><?= clean($barangayAddress) ?></div><?php endif; ?>
    </div>

    <div class="cert-title"><?= clean(strtoupper($doc['document_type'])) ?></div>

    <div class="cert-body">
        <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
        <?php foreach ($bodyParagraphs as $p): ?>
            <p><?= $p ?></p>
        <?php endforeach; ?>
        <p>Issued this <?= format_date($doc['issued_at'], 'jS \d\a\y \o\f F Y') ?> at <?= clean($barangayName) ?>.</p>
    </div>

    <div class="cert-sign">
        <div class="cert-sign-block">
            <div class="line"></div>
            <strong><?= clean(strtoupper($captainName)) ?></strong><br>
            <span class="text-soft small">Punong Barangay</span>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-5 pt-3" style="border-top:1px solid var(--hairline);font-size:0.82rem;">
        <div>
            OR No.: <strong><?= clean($doc['or_number'] ?: 'N/A') ?></strong><br>
            Amount Paid: <strong><?= format_currency($doc['amount']) ?></strong>
        </div>
        <div class="text-end">
            Prepared by: <?= clean($secretaryName) ?><br>
            <span class="text-soft">Barangay Secretary</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
