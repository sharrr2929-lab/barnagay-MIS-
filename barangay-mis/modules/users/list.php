<?php
/**
 * modules/users/list.php
 * Restricted to ADMIN_ROLES (super_admin, captain).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ADMIN_ROLES);

$users = $pdo->query('SELECT * FROM users ORDER BY full_name')->fetchAll();

$pageTitle = 'User Accounts';
$activeMenu = 'users';
$breadcrumbEyebrow = 'Administration';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>System User Accounts</h2>
        <a href="<?= BASE_URL ?>/modules/users/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add User</a>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
        <table class="table table-clean">
            <thead><tr><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><span class="avatar-circle me-2" style="width:30px;height:30px;font-size:0.75rem;"><?= clean(mb_strtoupper(mb_substr($u['full_name'], 0, 1))) ?></span><?= clean($u['full_name']) ?></td>
                    <td class="mono"><?= clean($u['username']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= clean(role_label($u['role'])) ?></span></td>
                    <td><span class="status-pill <?= $u['status'] ?>"><span class="dot"></span><?= clean(ucfirst($u['status'])) ?></span></td>
                    <td><?= format_date($u['created_at']) ?></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/modules/users/form.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <?php if ((int)$u['id'] !== current_user_id()): ?>
                        <form method="post" action="<?= BASE_URL ?>/modules/users/delete.php" class="d-inline" data-confirm="Delete user account '<?= clean($u['username']) ?>'?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php else: ?>
                        <span class="badge bg-light text-dark border" title="You cannot delete your own account">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
