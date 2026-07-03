<?php
/**
 * modules/events/form.php
 * Includes a non-blocking venue/schedule conflict warning and an
 * optional one-click "Post to Announcements" on creation.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$event = [
    'title' => '', 'description' => '', 'event_type' => '', 'venue' => '',
    'start_datetime' => date('Y-m-d\TH:i'), 'end_datetime' => '', 'organizer' => '', 'budget' => 0, 'status' => 'Upcoming',
];
$errors = [];
$conflicts = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Event not found.');
        header('Location: ' . BASE_URL . '/modules/events/list.php');
        exit;
    }
    $event = $found;
    $event['start_datetime'] = str_replace(' ', 'T', substr($event['start_datetime'], 0, 16));
    $event['end_datetime']   = $event['end_datetime'] ? str_replace(' ', 'T', substr($event['end_datetime'], 0, 16)) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $event['title']          = post_str('title');
    $event['description']    = post_str('description');
    $event['event_type']     = post_str('event_type');
    $event['venue']          = post_str('venue');
    $event['start_datetime'] = post_str('start_datetime');
    $event['end_datetime']   = post_str('end_datetime');
    $event['organizer']      = post_str('organizer');
    $event['budget']         = post_float('budget');
    $event['status']         = post_str('status', 'Upcoming');
    $postToAnnouncements     = isset($_POST['post_to_announcements']);
    $confirmConflict         = isset($_POST['confirm_conflict']);

    if ($event['title'] === '') $errors[] = 'Event title is required.';
    if ($event['start_datetime'] === '') $errors[] = 'Start date/time is required.';

    $startSql = $event['start_datetime'] ? str_replace('T', ' ', $event['start_datetime']) . ':00' : null;
    $endSql   = $event['end_datetime'] ? str_replace('T', ' ', $event['end_datetime']) . ':00' : $startSql;

    if (empty($errors) && $event['venue'] !== '' && !$confirmConflict) {
        $sql = "SELECT id, title, start_datetime, end_datetime FROM events
                WHERE venue = ? AND status != 'Cancelled' AND id != ?
                AND start_datetime < ? AND COALESCE(end_datetime, start_datetime) > ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$event['venue'], $id ?: 0, $endSql, $startSql]);
        $conflicts = $stmt->fetchAll();
    }

    if (empty($errors) && empty($conflicts)) {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE events SET title=?, description=?, event_type=?, venue=?, start_datetime=?, end_datetime=?, organizer=?, budget=?, status=? WHERE id=?'
            );
            $stmt->execute([$event['title'], $event['description'], $event['event_type'], $event['venue'], $startSql, $endSql, $event['organizer'], $event['budget'], $event['status'], $id]);
            audit_log($pdo, 'update', 'events', $id, $event['title']);
            flash_success('Event updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO events (title, description, event_type, venue, start_datetime, end_datetime, organizer, budget, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$event['title'], $event['description'], $event['event_type'], $event['venue'], $startSql, $endSql, $event['organizer'], $event['budget'], $event['status'], current_user_id()]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'events', $newId, $event['title']);

            if ($postToAnnouncements) {
                $stmt = $pdo->prepare('INSERT INTO announcements (title, content, category, posted_by) VALUES (?,?,\'Event\',?)');
                $stmt->execute([$event['title'], $event['description'] ?: ('Join us for ' . $event['title'] . '.'), current_user_id()]);
            }
            flash_success('Event added.' . ($postToAnnouncements ? ' Also posted to the bulletin board.' : ''));
        }
        header('Location: ' . BASE_URL . '/modules/events/calendar.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Event' : 'Add Event';
$activeMenu = 'events';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:760px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Event' : 'Add New Event' ?></h2>
        <a href="<?= BASE_URL ?>/modules/events/calendar.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to calendar</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if ($conflicts): ?>
            <div class="alert alert-warning">
                <strong><i class="bi bi-exclamation-triangle me-1"></i>Schedule conflict at this venue:</strong>
                <ul class="mb-2">
                    <?php foreach ($conflicts as $c): ?>
                        <li><?= clean($c['title']) ?> — <?= format_datetime($c['start_datetime'], 'M d, g:i A') ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="post">
                    <?= csrf_field() ?>
                    <?php foreach ($event as $k => $v): ?>
                        <input type="hidden" name="<?= $k ?>" value="<?= clean((string)$v) ?>">
                    <?php endforeach; ?>
                    <?php if ($postToAnnouncements): ?>
                        <input type="hidden" name="post_to_announcements" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="confirm_conflict" value="1">
                    <button type="submit" class="btn btn-sm btn-warning">Save Anyway</button>
                    <span class="small text-soft">or change the venue/time below and re-submit.</span>
                </form>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Event Title <span class="required-mark">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= clean($event['title']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Event Type</label>
                    <input type="text" name="event_type" class="form-control" list="eventTypes" value="<?= clean($event['event_type']) ?>">
                    <datalist id="eventTypes">
                        <?php foreach (['Fiesta','Feeding Program','Vaccination/Medical Mission','General Assembly','Sports Fest','Clean-up Drive','Seminar/Training','Other'] as $t): ?>
                            <option value="<?= $t ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Venue</label>
                    <input type="text" name="venue" class="form-control" value="<?= clean($event['venue']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Organizer</label>
                    <input type="text" name="organizer" class="form-control" value="<?= clean($event['organizer']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start Date/Time <span class="required-mark">*</span></label>
                    <input type="datetime-local" name="start_datetime" class="form-control" required value="<?= clean($event['start_datetime']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date/Time</label>
                    <input type="datetime-local" name="end_datetime" class="form-control" value="<?= clean($event['end_datetime']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Upcoming','Ongoing','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $event['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Budget (₱)</label>
                    <input type="number" step="0.01" min="0" name="budget" class="form-control" value="<?= clean((string)$event['budget']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control"><?= clean($event['description']) ?></textarea>
                </div>
                <?php if (!$id): ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="post_to_announcements" id="postAnn" checked>
                        <label class="form-check-label" for="postAnn">Also post this event to the Announcements bulletin board</label>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Event</button>
                <a href="<?= BASE_URL ?>/modules/events/calendar.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
