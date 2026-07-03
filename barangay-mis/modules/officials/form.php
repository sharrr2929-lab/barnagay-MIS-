<?php
/**
 * modules/officials/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$official = ['full_name' => '', 'position' => '', 'term_start' => '', 'term_end' => '', 'contact' => '', 'photo' => null];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM officials WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Official not found.');
        header('Location: ' . BASE_URL . '/modules/officials/list.php');
        exit;
    }
    $official = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $official['full_name']  = post_str('full_name');
    $official['position']   = post_str('position');
    $official['term_start'] = post_str('term_start');
    $official['term_end']   = post_str('term_end');
    $official['contact']    = post_str('contact');

    if ($official['full_name'] === '') $errors[] = 'Full name is required.';
    if ($official['position'] === '') $errors[] = 'Position is required.';

    $newPhoto = null;
    if (empty($errors)) {
        try {
            $newPhoto = handle_photo_upload('photo', 'photos');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($newPhoto) $official['photo'] = $newPhoto;
        if ($id) {
            $stmt = $pdo->prepare('UPDATE officials SET full_name=?, position=?, term_start=?, term_end=?, contact=?, photo=? WHERE id=?');
            $stmt->execute([$official['full_name'], $official['position'], $official['term_start'] ?: null, $official['term_end'] ?: null, $official['contact'], $official['photo'], $id]);
            audit_log($pdo, 'update', 'officials', $id, $official['full_name']);
            flash_success('Official record updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO officials (full_name, position, term_start, term_end, contact, photo) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$official['full_name'], $official['position'], $official['term_start'] ?: null, $official['term_end'] ?: null, $official['contact'], $official['photo']]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'officials', $newId, $official['full_name']);
            flash_success('Official added.');
        }
        header('Location: ' . BASE_URL . '/modules/officials/list.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Official' : 'Add Official';
$activeMenu = 'officials';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:640px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Official' : 'Add Official' ?></h2>
        <a href="<?= BASE_URL ?>/modules/officials/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Full Name <span class="required-mark">*</span></label>
                    <input type="text" name="full_name" class="form-control" required value="<?= clean($official['full_name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Position <span class="required-mark">*</span></label>
                    <input type="text" name="position" class="form-control" required placeholder="Kagawad, SK Chairman..." value="<?= clean($official['position']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Term Start</label>
                    <input type="date" name="term_start" class="form-control" value="<?= clean($official['term_start']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Term End</label>
                    <input type="date" name="term_end" class="form-control" value="<?= clean($official['term_end']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" class="form-control" value="<?= clean($official['contact']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Photo <span class="text-soft">(optional)</span></label>
                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                <a href="<?= BASE_URL ?>/modules/officials/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
