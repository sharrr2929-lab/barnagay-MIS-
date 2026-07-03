<?php
/**
 * modules/users/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ADMIN_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user = ['username' => '', 'full_name' => '', 'role' => 'staff', 'status' => 'active'];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('User not found.');
        header('Location: ' . BASE_URL . '/modules/users/list.php');
        exit;
    }
    $user = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user['username']  = post_str('username');
    $user['full_name'] = post_str('full_name');
    $user['role']      = post_str('role', 'staff');
    $user['status']    = post_str('status', 'active');
    $password          = (string)($_POST['password'] ?? '');

    if ($user['username'] === '') $errors[] = 'Username is required.';
    if ($user['full_name'] === '') $errors[] = 'Full name is required.';
    if (!in_array($user['role'], ALL_ROLES, true)) $errors[] = 'Invalid role selected.';
    if (!$id && $password === '') $errors[] = 'Password is required for a new account.';
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        try {
            if ($id) {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, status=?, password=? WHERE id=?');
                    $stmt->execute([$user['username'], $user['full_name'], $user['role'], $user['status'], password_hash($password, PASSWORD_BCRYPT), $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, status=? WHERE id=?');
                    $stmt->execute([$user['username'], $user['full_name'], $user['role'], $user['status'], $id]);
                }
                audit_log($pdo, 'update', 'users', $id, $user['username']);
                flash_success('User account updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role, status) VALUES (?,?,?,?,?)');
                $stmt->execute([$user['username'], password_hash($password, PASSWORD_BCRYPT), $user['full_name'], $user['role'], $user['status']]);
                $newId = (int)$pdo->lastInsertId();
                audit_log($pdo, 'create', 'users', $newId, $user['username']);
                flash_success('User account created.');
            }
            header('Location: ' . BASE_URL . '/modules/users/list.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'Duplicate')
                ? 'That username is already taken.'
                : 'Could not save this account. Please try again.';
        }
    }
}

$pageTitle = $id ? 'Edit User Account' : 'Add User Account';
$activeMenu = 'users';
$breadcrumbEyebrow = 'Administration';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:560px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit User Account' : 'Add User Account' ?></h2>
        <a href="<?= BASE_URL ?>/modules/users/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-mark">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= clean($user['full_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username <span class="required-mark">*</span></label>
                    <input type="text" name="username" class="form-control" required value="<?= clean($user['username']) ?>" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <?php foreach (ALL_ROLES as $r): ?>
                            <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= role_label($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Password <?= $id ? '<span class="text-soft">(leave blank to keep current password)</span>' : '<span class="required-mark">*</span>' ?></label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" minlength="6">
                    <div class="form-text">Minimum 6 characters.</div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Account</button>
                <a href="<?= BASE_URL ?>/modules/users/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
