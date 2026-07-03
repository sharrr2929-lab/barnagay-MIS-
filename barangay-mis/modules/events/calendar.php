<?php
/**
 * modules/events/calendar.php
 * Monthly calendar grid. Viewable by everyone; managed by staff roles.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$month = isset($_GET['m']) ? max(1, min(12, (int)$_GET['m'])) : (int)date('n');
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

$firstOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth  = (int)date('t', $firstOfMonth);
$startWeekday = (int)date('w', $firstOfMonth); // 0 = Sunday
$monthLabel   = date('F Y', $firstOfMonth);

$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear  = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;

$stmt = $pdo->prepare(
    "SELECT id, title, event_type, status, start_datetime FROM events
     WHERE YEAR(start_datetime) = ? AND MONTH(start_datetime) = ?
     ORDER BY start_datetime"
);
$stmt->execute([$year, $month]);
$eventsByDay = [];
foreach ($stmt as $e) {
    $day = (int)date('j', strtotime($e['start_datetime']));
    $eventsByDay[$day][] = $e;
}

function event_type_class(string $type): string
{
    $t = strtolower($type);
    if (str_contains($t, 'health') || str_contains($t, 'medical') || str_contains($t, 'vaccination')) return 'type-health';
    if (str_contains($t, 'emergency')) return 'type-emergency';
    if (str_contains($t, 'assembly') || str_contains($t, 'general')) return 'type-general';
    return '';
}

$pageTitle = 'Events Calendar';
$activeMenu = 'events';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group">
        <a href="?m=<?= $prevMonth ?>&y=<?= $prevYear ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i></a>
        <span class="btn btn-sm disabled border" style="min-width:150px;"><?= clean($monthLabel) ?></span>
        <a href="?m=<?= $nextMonth ?>&y=<?= $nextYear ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-right"></i></a>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/events/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-list-ul me-1"></i>List View</a>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/events/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Event</a>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="cal-grid mb-1">
            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow): ?>
                <div class="cal-dow"><?= $dow ?></div>
            <?php endforeach; ?>
        </div>
        <div class="cal-grid">
            <?php
            // Leading blanks from previous month
            for ($i = 0; $i < $startWeekday; $i++) {
                echo '<div class="cal-cell outside"></div>';
            }
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($day === (int)date('j') && $month === (int)date('n') && $year === (int)date('Y'));
                echo '<div class="cal-cell' . ($isToday ? ' today' : '') . '">';
                echo '<div class="cal-date">' . $day . '</div>';
                if (!empty($eventsByDay[$day])) {
                    foreach ($eventsByDay[$day] as $e) {
                        $cls = event_type_class($e['event_type'] ?? '');
                        echo '<a href="' . BASE_URL . '/modules/events/form.php?id=' . (int)$e['id'] . '" class="cal-event ' . $cls . '" title="' . clean($e['title']) . '">' . clean($e['title']) . '</a>';
                    }
                }
                echo '</div>';
            }
            // Trailing blanks to complete the grid
            $totalCells = $startWeekday + $daysInMonth;
            $trailing = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $trailing; $i++) {
                echo '<div class="cal-cell outside"></div>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
