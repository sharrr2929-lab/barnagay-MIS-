<?php
/**
 * modules/dispatch/board.php
 * The front-desk "live" dispatch board. Viewable by all roles; status
 * updates and escalation are allowed for all roles too (a tanod in the
 * field is often the one updating their own call status).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);

$activeCalls = $pdo->query(
    "SELECT d.*, t.full_name AS responder_name
     FROM dispatch_calls d
     LEFT JOIN tanod_roster t ON t.id = d.responder_id
     WHERE d.status NOT IN ('Resolved','Closed')
     ORDER BY FIELD(d.priority,'Emergency','High','Medium','Low'), d.called_at ASC"
)->fetchAll();

$recentClosed = $pdo->query(
    "SELECT d.*, t.full_name AS responder_name
     FROM dispatch_calls d
     LEFT JOIN tanod_roster t ON t.id = d.responder_id
     WHERE d.status IN ('Resolved','Closed')
     ORDER BY d.called_at DESC LIMIT 10"
)->fetchAll();

$responders = $pdo->query("SELECT id, full_name, status FROM tanod_roster ORDER BY full_name")->fetchAll();

$pageTitle = 'Dispatch Board';
$activeMenu = 'dispatch';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="autoRefreshToggle">
        <label class="form-check-label small text-soft" for="autoRefreshToggle">Auto-refresh every 30s</label>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/dispatch/roster.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-shield-check me-1"></i>Tanod Roster</a>
        <a href="<?= BASE_URL ?>/modules/dispatch/form.php" class="btn btn-primary btn-sm"><i class="bi bi-telephone-plus me-1"></i>Log New Call</a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Active Calls</h2>
        <span class="badge bg-light text-dark border"><?= count($activeCalls) ?> active</span>
    </div>
    <div class="panel-body p-0">
        <?php if (empty($activeCalls)): ?>
            <div class="empty-state"><i class="bi bi-broadcast"></i>No active dispatch calls right now.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-clean mb-0">
            <thead><tr><th>Priority</th><th>Caller</th><th>Incident</th><th>Location</th><th>Responder</th><th>Called At</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($activeCalls as $c): ?>
                <tr>
                    <td><span class="status-pill <?= strtolower($c['priority']) ?>"><span class="dot"></span><?= clean($c['priority']) ?></span></td>
                    <td><?= clean($c['caller_name']) ?><br><span class="text-soft small"><?= clean($c['caller_contact'] ?: '—') ?></span></td>
                    <td><?= clean($c['incident_type']) ?><div class="text-soft small"><?= clean($c['description'] ?: '') ?></div></td>
                    <td><?= clean($c['location']) ?></td>
                    <td><?= clean($c['responder_name'] ?? '— Unassigned —') ?></td>
                    <td><?= format_datetime($c['called_at'], 'M d, g:i A') ?></td>
                    <td><span class="status-pill <?= strtolower(str_replace(' ', '-', $c['status'])) ?>"><span class="dot"></span><?= clean($c['status']) ?></span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal"
                            data-id="<?= (int)$c['id'] ?>" data-status="<?= clean($c['status']) ?>" data-responder="<?= (int)($c['responder_id'] ?? 0) ?>"
                            data-caller="<?= clean($c['caller_name']) ?>">Update</button>
                        <?php if (empty($c['linked_blotter_id'])): ?>
                        <form method="post" action="<?= BASE_URL ?>/modules/dispatch/escalate.php" class="d-inline" data-confirm="Escalate this call into a formal Blotter Report?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border" title="Escalate to Blotter"><i class="bi bi-journal-arrow-up"></i></button>
                        </form>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/modules/blotter/view.php?id=<?= (int)$c['linked_blotter_id'] ?>" class="btn btn-sm btn-light border" title="View linked blotter report"><i class="bi bi-journal-check"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3>Recently Resolved / Closed</h3></div>
    <div class="panel-body p-0">
        <?php if (empty($recentClosed)): ?>
            <div class="empty-state py-4"><i class="bi bi-check-circle"></i>No resolved calls yet.</div>
        <?php else: ?>
        <table class="table table-clean mb-0">
            <thead><tr><th>Caller</th><th>Incident</th><th>Responder</th><th>Status</th><th>Response Time</th></tr></thead>
            <tbody>
            <?php foreach ($recentClosed as $c):
                $responseMinutes = null;
                if (!empty($c['dispatched_at']) && !empty($c['called_at'])) {
                    $responseMinutes = round((strtotime($c['dispatched_at']) - strtotime($c['called_at'])) / 60);
                }
            ?>
                <tr>
                    <td><?= clean($c['caller_name']) ?></td>
                    <td><?= clean($c['incident_type']) ?></td>
                    <td><?= clean($c['responder_name'] ?? '—') ?></td>
                    <td><span class="status-pill <?= strtolower($c['status']) ?>"><span class="dot"></span><?= clean($c['status']) ?></span></td>
                    <td><?= $responseMinutes !== null ? $responseMinutes . ' min' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Shared "update status" modal, populated via JS from the clicked row's data-* attributes -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= BASE_URL ?>/modules/dispatch/update_status.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Call — <span id="modalCallerName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="modalCallId">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="modalStatus" class="form-select">
                            <?php foreach (['Pending','Dispatched','En Route','On Scene','Resolved','Closed'] as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Assign Responder</label>
                        <select name="responder_id" id="modalResponder" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($responders as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"><?= clean($r['full_name']) ?> (<?= clean($r['status']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-text">Timestamps for each stage are recorded automatically.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
    var statusModal = document.getElementById('statusModal');
    statusModal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        document.getElementById('modalCallId').value = btn.getAttribute('data-id');
        document.getElementById('modalCallerName').textContent = btn.getAttribute('data-caller');
        document.getElementById('modalStatus').value = btn.getAttribute('data-status');
        document.getElementById('modalResponder').value = btn.getAttribute('data-responder') || '';
    });

    var autoRefresh = document.getElementById('autoRefreshToggle');
    var refreshTimer = null;
    autoRefresh.addEventListener('change', function () {
        if (this.checked) {
            refreshTimer = setInterval(function () { location.reload(); }, 30000);
        } else if (refreshTimer) {
            clearInterval(refreshTimer);
        }
    });
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
