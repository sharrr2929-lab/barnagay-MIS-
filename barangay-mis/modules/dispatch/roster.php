<?php
/**
 * modules/dispatch/roster.php
 * On-duty personnel roster. Viewable by everyone; managed by staff roles.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$roster = $pdo->query('SELECT * FROM tanod_roster ORDER BY FIELD(status,\'Available\',\'On Call\',\'Off Duty\'), full_name')->fetchAll();

$pageTitle = 'Tanod Roster';
$activeMenu = 'roster';
$breadcrumbEyebrow = 'Community Safety';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>On-Duty Personnel Roster</h2>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/dispatch/roster_form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Personnel</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if (empty($roster)): ?>
            <div class="empty-state"><i class="bi bi-shield-check"></i>No personnel on the roster yet.</div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($roster as $t): ?>
            <div class="col-md-6 col-lg-4">
                <div class="panel h-100 mb-0">
                    <div class="panel-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold"><?= clean($t['full_name']) ?></div>
                                <div class="text-soft small"><?= clean($t['role']) ?></div>
                            </div>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $t['status'])) ?>"><span class="dot"></span><?= clean($t['status']) ?></span>
                        </div>
                        <div class="small text-soft mb-1"><i class="bi bi-telephone me-1"></i><?= clean($t['contact_number'] ?: '—') ?></div>
                        <div class="small text-soft"><i class="bi bi-clock me-1"></i><?= clean($t['shift_schedule'] ?: 'No fixed schedule') ?></div>
                        <?php if ($canManage): ?>
                        <div class="mt-3 d-flex gap-2">
                            <a href="<?= BASE_URL ?>/modules/dispatch/roster_form.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-light border flex-fill"><i class="bi bi-pencil me-1"></i>Edit</a>
                            <form method="post" action="<?= BASE_URL ?>/modules/dispatch/roster_delete.php" class="flex-fill" data-confirm="Remove <?= clean($t['full_name']) ?> from the roster?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-light border text-danger w-100"><i class="bi bi-trash me-1"></i>Remove</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
