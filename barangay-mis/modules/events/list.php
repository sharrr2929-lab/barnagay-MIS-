<?php
/**
 * modules/events/list.php
 * Table alternative to the calendar view.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$events = $pdo->query(
    "SELECT e.*, (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
     FROM events e ORDER BY e.start_datetime DESC"
)->fetchAll();

$pageTitle = 'Events';
$activeMenu = 'events';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>All Events</h2>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/modules/events/calendar.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-calendar3 me-1"></i>Calendar View</a>
            <?php if ($canManage): ?>
            <a href="<?= BASE_URL ?>/modules/events/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Event</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control mb-3" style="max-width:320px;" placeholder="Quick search..." onkeyup="quickFilter(this, 'eventsTable')">

        <?php if (empty($events)): ?>
            <div class="empty-state"><i class="bi bi-calendar-event"></i>No events recorded yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean" id="eventsTable">
            <thead><tr><th>Event</th><th>Type</th><th>Venue</th><th>Date</th><th>Attendees</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($events as $e): ?>
                <tr>
                    <td class="fw-semibold"><?= clean($e['title']) ?></td>
                    <td><?= clean($e['event_type'] ?: '—') ?></td>
                    <td><?= clean($e['venue'] ?: '—') ?></td>
                    <td><?= format_datetime($e['start_datetime'], 'M d, Y g:i A') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= (int)$e['attendee_count'] ?></span></td>
                    <td><span class="status-pill <?= strtolower($e['status']) ?>"><span class="dot"></span><?= clean($e['status']) ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/events/attendance.php?id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-light border" title="Attendance"><i class="bi bi-people"></i></a>
                        <?php if ($canManage): ?>
                        <a href="<?= BASE_URL ?>/modules/events/form.php?id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= BASE_URL ?>/modules/events/delete.php" class="d-inline" data-confirm="Delete event '<?= clean($e['title']) ?>'?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="eventsTableNoMatch" style="display:none;"><td colspan="7" class="text-center text-soft py-3">No matches.</td></tr>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
