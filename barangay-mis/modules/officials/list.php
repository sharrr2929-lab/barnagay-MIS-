<?php
/**
 * modules/officials/list.php
 * Public-facing style directory; viewable by all logged-in roles.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$officials = $pdo->query('SELECT * FROM officials ORDER BY term_start DESC, id ASC')->fetchAll();

$pageTitle = 'Officials';
$activeMenu = 'officials';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Barangay Officials</h2>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/officials/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Official</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if (empty($officials)): ?>
            <div class="empty-state"><i class="bi bi-person-badge"></i>No officials on file yet.</div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($officials as $o): ?>
            <div class="col-md-6 col-lg-4">
                <div class="panel h-100 mb-0 card-hover">
                    <div class="panel-body text-center">
                        <?php if (!empty($o['photo'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/photos/<?= clean($o['photo']) ?>" class="rounded-circle mb-2" style="width:76px;height:76px;object-fit:cover;">
                        <?php else: ?>
                            <div class="avatar-circle mx-auto mb-2" style="width:76px;height:76px;font-size:1.6rem;"><?= clean(mb_strtoupper(mb_substr($o['full_name'], 0, 1))) ?></div>
                        <?php endif; ?>
                        <div class="fw-semibold"><?= clean($o['full_name']) ?></div>
                        <div class="text-soft small mb-2"><?= clean($o['position']) ?></div>
                        <div class="small text-soft">
                            <?= format_date($o['term_start'], 'Y') ?> – <?= format_date($o['term_end'], 'Y') ?><br>
                            <?php if ($o['contact']): ?><i class="bi bi-telephone me-1"></i><?= clean($o['contact']) ?><?php endif; ?>
                        </div>
                        <?php if ($canManage): ?>
                        <div class="mt-3 d-flex gap-2">
                            <a href="<?= BASE_URL ?>/modules/officials/form.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-light border flex-fill"><i class="bi bi-pencil me-1"></i>Edit</a>
                            <form method="post" action="<?= BASE_URL ?>/modules/officials/delete.php" class="flex-fill" data-confirm="Remove <?= clean($o['full_name']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
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
