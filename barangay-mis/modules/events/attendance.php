<?php
/**
 * modules/events/attendance.php
 * Handles adding attendees (resident or walk-in), toggling present/absent,
 * removing an attendee, and printing an attendance sheet — all in one
 * page since these actions are tightly scoped to a single event.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$eventId = (int)($_GET['id'] ?? $_POST['event_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    flash_error('Event not found.');
    header('Location: ' . BASE_URL . '/modules/events/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    verify_csrf();
    $action = post_str('action');

    if ($action === 'add') {
        $residentId = post_int('resident_id');
        $walkinName = post_str('walkin_name');
        if ($residentId || $walkinName !== '') {
            $stmt = $pdo->prepare('INSERT INTO event_attendees (event_id, resident_id, walkin_name, is_present) VALUES (?,?,?,1)');
            $stmt->execute([$eventId, $residentId ?: null, $residentId ? null : $walkinName]);
            flash_success('Attendee added.');
        } else {
            flash_error('Select a resident or type a walk-in name.');
        }
    } elseif ($action === 'toggle') {
        $attendeeId = post_int('attendee_id');
        $stmt = $pdo->prepare('UPDATE event_attendees SET is_present = NOT is_present WHERE id = ? AND event_id = ?');
        $stmt->execute([$attendeeId, $eventId]);
    } elseif ($action === 'remove') {
        $attendeeId = post_int('attendee_id');
        $stmt = $pdo->prepare('DELETE FROM event_attendees WHERE id = ? AND event_id = ?');
        $stmt->execute([$attendeeId, $eventId]);
        flash_success('Attendee removed.');
    }
    header('Location: ' . BASE_URL . '/modules/events/attendance.php?id=' . $eventId);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT a.*, r.first_name, r.middle_name, r.last_name, r.suffix
     FROM event_attendees a LEFT JOIN residents r ON r.id = a.resident_id
     WHERE a.event_id = ? ORDER BY a.created_at"
);
$stmt->execute([$eventId]);
$attendees = $stmt->fetchAll();
$presentCount = count(array_filter($attendees, fn($a) => (int)$a['is_present'] === 1));

$residents = get_residents_for_select($pdo);

$pageTitle = 'Attendance — ' . $event['title'];
$activeMenu = 'events';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= BASE_URL ?>/modules/events/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to events</a>
    <button onclick="printSection()" class="btn btn-sm btn-light border"><i class="bi bi-printer me-1"></i>Print Attendance Sheet</button>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="mb-0"><?= clean($event['title']) ?></h2>
            <div class="text-soft small"><?= format_datetime($event['start_datetime'], 'F d, Y g:i A') ?> · <?= clean($event['venue'] ?: 'Venue TBA') ?></div>
        </div>
        <span class="badge bg-light text-dark border"><?= $presentCount ?> / <?= count($attendees) ?> present</span>
    </div>

    <?php if ($canManage): ?>
    <div class="panel-body pb-0 no-print">
        <form method="post" class="row g-2 align-items-end mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="hidden" name="action" value="add">
            <div class="col-md-5">
                <label class="form-label small mb-1">Add Registered Resident</label>
                <select name="resident_id" class="form-select form-select-sm">
                    <option value="">— Select resident —</option>
                    <?php foreach ($residents as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= clean(full_name_of($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 text-center text-soft small">or</div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Add Walk-in Name</label>
                <input type="text" name="walkin_name" class="form-control form-control-sm" placeholder="Full name">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Add</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="panel-body p-0">
        <?php if (empty($attendees)): ?>
            <div class="empty-state"><i class="bi bi-people"></i>No attendees logged yet.</div>
        <?php else: ?>
        <table class="table table-clean mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Present</th><th class="no-print"></th></tr></thead>
            <tbody>
            <?php foreach ($attendees as $i => $a): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= clean($a['resident_id'] ? full_name_of($a) : $a['walkin_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= $a['resident_id'] ? 'Resident' : 'Walk-in' ?></span></td>
                    <td>
                        <?php if ($a['is_present']): ?>
                            <span class="status-pill active"><span class="dot"></span>Present</span>
                        <?php else: ?>
                            <span class="status-pill off-duty"><span class="dot"></span>Absent</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end no-print">
                        <?php if ($canManage): ?>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= $eventId ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="attendee_id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border" title="Toggle present/absent"><i class="bi bi-arrow-repeat"></i></button>
                        </form>
                        <form method="post" class="d-inline" data-confirm="Remove this attendee?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= $eventId ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="attendee_id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
