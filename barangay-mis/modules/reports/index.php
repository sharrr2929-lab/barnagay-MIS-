<?php
/**
 * modules/reports/index.php
 * Report summaries + CSV export links (CSV opens natively in Excel,
 * and needs no extra PHP libraries — see export.php).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$residentCount   = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='active'")->fetchColumn();
$blotterCount    = (int)$pdo->query('SELECT COUNT(*) FROM blotter_reports')->fetchColumn();
$dispatchCount   = (int)$pdo->query('SELECT COUNT(*) FROM dispatch_calls')->fetchColumn();
$eventCount      = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$docCount        = (int)$pdo->query('SELECT COUNT(*) FROM documents_issued')->fetchColumn();
$totalRevenue    = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM documents_issued')->fetchColumn();

$revenueByType = $pdo->query(
    'SELECT document_type, COUNT(*) AS total_count, SUM(amount) AS total_amount FROM documents_issued GROUP BY document_type ORDER BY total_amount DESC'
)->fetchAll();

$reports = [
    ['key' => 'residents', 'title' => 'Resident Master List', 'desc' => 'All registered residents with household, purok, and contact info.', 'icon' => 'bi-people', 'count' => $residentCount],
    ['key' => 'blotter', 'title' => 'Blotter Summary', 'desc' => 'All blotter/incident reports with status and dates.', 'icon' => 'bi-journal-text', 'count' => $blotterCount],
    ['key' => 'dispatch', 'title' => 'Dispatch Response Log', 'desc' => 'All dispatch calls with computed response times.', 'icon' => 'bi-broadcast', 'count' => $dispatchCount],
    ['key' => 'events', 'title' => 'Event Attendance Summary', 'desc' => 'Events with attendee counts and present/absent tallies.', 'icon' => 'bi-calendar-event', 'count' => $eventCount],
    ['key' => 'documents', 'title' => 'Document Fees & Revenue', 'desc' => 'All issued documents with fees collected.', 'icon' => 'bi-cash-coin', 'count' => $docCount],
];

$pageTitle = 'Reports';
$activeMenu = 'reports';
$breadcrumbEyebrow = 'Administration';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-cash-coin fs-5"></i></div><div class="stat-number"><?= format_currency($totalRevenue) ?></div><div class="stat-label">Total Fees Collected (all time)</div></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3>Revenue by Document Type</h3></div>
    <div class="panel-body p-0">
        <?php if (empty($revenueByType)): ?>
            <div class="empty-state py-4"><i class="bi bi-cash-coin"></i>No documents issued yet.</div>
        <?php else: ?>
        <table class="table table-clean mb-0">
            <thead><tr><th>Document Type</th><th>Count</th><th>Total Collected</th></tr></thead>
            <tbody>
            <?php foreach ($revenueByType as $r): ?>
                <tr><td><?= clean($r['document_type']) ?></td><td><?= (int)$r['total_count'] ?></td><td><?= format_currency($r['total_amount']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h2>Exportable Reports</h2></div>
    <div class="panel-body">
        <div class="row g-3">
            <?php foreach ($reports as $r): ?>
            <div class="col-md-6">
                <div class="panel h-100 mb-0 card-hover">
                    <div class="panel-body d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon"><i class="bi <?= $r['icon'] ?> fs-5"></i></div>
                            <div>
                                <div class="fw-semibold"><?= clean($r['title']) ?></div>
                                <div class="text-soft small"><?= clean($r['desc']) ?></div>
                                <div class="text-soft small"><?= (int)$r['count'] ?> record(s)</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/modules/reports/export.php?type=<?= $r['key'] ?>" class="btn btn-sm btn-gold text-nowrap"><i class="bi bi-download me-1"></i>CSV</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="form-text mt-3">CSV files open directly in Excel/Google Sheets — no extra software required.</div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
