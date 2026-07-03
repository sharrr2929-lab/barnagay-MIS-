<?php
/**
 * dashboard.php
 * Landing page after login: summary cards, charts, and recent activity.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role(ALL_ROLES);

// ---- Summary card figures ---------------------------------------------
$totalResidents   = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE status='active'")->fetchColumn();
$totalHouseholds  = (int)$pdo->query('SELECT COUNT(*) FROM households')->fetchColumn();
$pendingRequests  = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status='Pending'")->fetchColumn();
$activeBlotter    = (int)$pdo->query("SELECT COUNT(*) FROM blotter_reports WHERE status IN ('Open','Under Mediation')")->fetchColumn();
$activeDispatch   = (int)$pdo->query("SELECT COUNT(*) FROM dispatch_calls WHERE status NOT IN ('Resolved','Closed')")->fetchColumn();
$expiringPermits  = (int)$pdo->query("SELECT COUNT(*) FROM businesses WHERE status='Active' AND date_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchColumn();
$upcomingEvents   = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='Upcoming' AND start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ---- Chart 1: Residents per Purok -------------------------------------
$purokRows = $pdo->query(
    "SELECT p.name, COUNT(r.id) AS total FROM puroks p
     LEFT JOIN residents r ON r.purok_id = p.id AND r.status='active'
     GROUP BY p.id, p.name ORDER BY p.name"
)->fetchAll();
$purokLabels = array_column($purokRows, 'name');
$purokData   = array_map('intval', array_column($purokRows, 'total'));

// ---- Chart 2: Population by age group & sex ----------------------------
$ageBuckets = ['Child (0-14)' => ['Male' => 0, 'Female' => 0], 'Youth (15-24)' => ['Male' => 0, 'Female' => 0],
               'Adult (25-59)' => ['Male' => 0, 'Female' => 0], 'Senior (60+)' => ['Male' => 0, 'Female' => 0]];
foreach ($pdo->query("SELECT birthdate, sex FROM residents WHERE status='active'") as $r) {
    $age = calculate_age($r['birthdate']);
    $bucket = $age <= 14 ? 'Child (0-14)' : ($age <= 24 ? 'Youth (15-24)' : ($age <= 59 ? 'Adult (25-59)' : 'Senior (60+)'));
    $ageBuckets[$bucket][$r['sex']]++;
}
$ageLabels = array_keys($ageBuckets);
$maleData  = array_map(fn($b) => $b['Male'], $ageBuckets);
$femaleData = array_map(fn($b) => $b['Female'], $ageBuckets);

// ---- Chart 3: Documents issued per month (this year) -------------------
$docsByMonth = array_fill(1, 12, 0);
$year = date('Y');
$stmt = $pdo->prepare("SELECT MONTH(issued_at) AS m, COUNT(*) AS total FROM documents_issued WHERE YEAR(issued_at) = ? GROUP BY MONTH(issued_at)");
$stmt->execute([$year]);
foreach ($stmt as $row) {
    $docsByMonth[(int)$row['m']] = (int)$row['total'];
}
$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// ---- Chart 4: Dispatch calls by status ----------------------------------
$dispatchStatusRows = $pdo->query('SELECT status, COUNT(*) AS total FROM dispatch_calls GROUP BY status')->fetchAll();
$dispatchLabels = array_column($dispatchStatusRows, 'status');
$dispatchData   = array_map('intval', array_column($dispatchStatusRows, 'total'));

// ---- Recent activity panels ---------------------------------------------
$recentBlotter = $pdo->query('SELECT * FROM blotter_reports ORDER BY created_at DESC LIMIT 5')->fetchAll();
$upcomingList  = $pdo->query("SELECT * FROM events WHERE status IN ('Upcoming','Ongoing') ORDER BY start_datetime ASC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$breadcrumbEyebrow = 'Overview';
$useCharts = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-people fs-5"></i></div>
            <div class="stat-number"><?= number_format($totalResidents) ?></div>
            <div class="stat-label">Registered Residents</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-houses fs-5"></i></div>
            <div class="stat-number"><?= number_format($totalHouseholds) ?></div>
            <div class="stat-label">Households</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-inbox fs-5"></i></div>
            <div class="stat-number"><?= number_format($pendingRequests) ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover urgent">
            <div class="stat-icon"><i class="bi bi-journal-text fs-5"></i></div>
            <div class="stat-number"><?= number_format($activeBlotter) ?></div>
            <div class="stat-label">Active Blotter Cases</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover urgent">
            <div class="stat-icon"><i class="bi bi-broadcast fs-5"></i></div>
            <div class="stat-number"><?= number_format($activeDispatch) ?></div>
            <div class="stat-label">Active Dispatch Calls</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-shop fs-5"></i></div>
            <div class="stat-number"><?= number_format($expiringPermits) ?></div>
            <div class="stat-label">Permits Expiring (60d)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-calendar-event fs-5"></i></div>
            <div class="stat-number"><?= number_format($upcomingEvents) ?></div>
            <div class="stat-label">Events This Week</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card-hover">
            <div class="stat-icon"><i class="bi bi-calendar-check fs-5"></i></div>
            <div class="stat-number"><?= clean(date('M d')) ?></div>
            <div class="stat-label"><?= clean(date('l, Y')) ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header"><h3>Residents per Purok</h3></div>
            <div class="panel-body"><canvas id="chartPurok" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header"><h3>Population by Age Group &amp; Sex</h3></div>
            <div class="panel-body"><canvas id="chartAge" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="panel h-100">
            <div class="panel-header"><h3>Documents Issued — <?= clean($year) ?></h3></div>
            <div class="panel-body"><canvas id="chartDocs" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-header"><h3>Dispatch Calls by Status</h3></div>
            <div class="panel-body"><canvas id="chartDispatch" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header">
                <h3>Recent Blotter Reports</h3>
                <a href="<?= BASE_URL ?>/modules/blotter/list.php" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="panel-body p-0">
                <?php if (empty($recentBlotter)): ?>
                    <div class="empty-state"><i class="bi bi-journal-text"></i>No blotter reports yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead><tr><th>Case #</th><th>Complainant</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentBlotter as $b): ?>
                        <tr>
                            <td class="mono"><?= clean($b['case_number'] ?? '—') ?></td>
                            <td><?= clean($b['complainant']) ?></td>
                            <td><?= clean($b['incident_type'] ?? '—') ?></td>
                            <td><span class="status-pill <?= strtolower(str_replace(' ', '-', $b['status'])) ?>"><span class="dot"></span><?= clean($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header">
                <h3>Upcoming Events</h3>
                <a href="<?= BASE_URL ?>/modules/events/calendar.php" class="btn btn-sm btn-outline-primary">View calendar</a>
            </div>
            <div class="panel-body p-0">
                <?php if (empty($upcomingList)): ?>
                    <div class="empty-state"><i class="bi bi-calendar-event"></i>No upcoming events.</div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <thead><tr><th>Event</th><th>Date</th><th>Venue</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcomingList as $e): ?>
                        <tr>
                            <td><?= clean($e['title']) ?></td>
                            <td><?= format_date($e['start_datetime'], 'M d, Y') ?></td>
                            <td><?= clean($e['venue'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$chartJs = <<<'JS'
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#5B6660';

    new Chart(document.getElementById('chartPurok'), {
        type: 'bar',
        data: {
            labels: __PUROK_LABELS__,
            datasets: [{ label: 'Residents', data: __PUROK_DATA__, backgroundColor: '#123C5E', borderRadius: 4, maxBarThickness: 42 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartAge'), {
        type: 'bar',
        data: {
            labels: __AGE_LABELS__,
            datasets: [
                { label: 'Male', data: __MALE_DATA__, backgroundColor: '#123C5E', borderRadius: 4 },
                { label: 'Female', data: __FEMALE_DATA__, backgroundColor: '#D9A441', borderRadius: 4 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartDocs'), {
        type: 'line',
        data: {
            labels: __MONTH_LABELS__,
            datasets: [{ label: 'Documents Issued', data: __DOCS_BY_MONTH__, borderColor: '#123C5E', backgroundColor: 'rgba(18,60,94,0.08)', fill: true, tension: 0.35, pointBackgroundColor: '#D9A441' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartDispatch'), {
        type: 'doughnut',
        data: {
            labels: __DISPATCH_LABELS__,
            datasets: [{ data: __DISPATCH_DATA__, backgroundColor: ['#D9A441','#1E5583','#4C7A5E','#C1502E','#5B6660','#123C5E'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }
    });
JS;

// Substitute JSON payloads. Nowdoc (<<<'JS') above means none of the
// __TOKENS__ were touched by PHP string interpolation, so this is a
// plain literal find/replace.
$chartJs = strtr($chartJs, [
    '__PUROK_LABELS__'    => json_encode($purokLabels),
    '__PUROK_DATA__'      => json_encode($purokData),
    '__AGE_LABELS__'      => json_encode($ageLabels),
    '__MALE_DATA__'       => json_encode(array_values($maleData)),
    '__FEMALE_DATA__'     => json_encode(array_values($femaleData)),
    '__MONTH_LABELS__'    => json_encode($monthLabels),
    '__DOCS_BY_MONTH__'   => json_encode(array_values($docsByMonth)),
    '__DISPATCH_LABELS__' => json_encode($dispatchLabels),
    '__DISPATCH_DATA__'   => json_encode($dispatchData),
]);
$extraScript = $chartJs;
require_once __DIR__ . '/includes/footer.php';
?>
