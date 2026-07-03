<?php
/**
 * modules/announcements/form.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = ['title' => '', 'content' => '', 'category' => 'General', 'image' => null];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM announcements WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_error('Announcement not found.');
        header('Location: ' . BASE_URL . '/modules/announcements/list.php');
        exit;
    }
    $item = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item['title']    = post_str('title');
    $item['content']  = post_str('content');
    $item['category'] = post_str('category', 'General');

    if ($item['title'] === '') $errors[] = 'Title is required.';

    $newImage = null;
    if (empty($errors)) {
        try {
            $newImage = handle_photo_upload('image', 'photos');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($newImage) $item['image'] = $newImage;
        if ($id) {
            $stmt = $pdo->prepare('UPDATE announcements SET title=?, content=?, category=?, image=? WHERE id=?');
            $stmt->execute([$item['title'], $item['content'], $item['category'], $item['image'], $id]);
            audit_log($pdo, 'update', 'announcements', $id, $item['title']);
            flash_success('Announcement updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO announcements (title, content, category, image, posted_by) VALUES (?,?,?,?,?)');
            $stmt->execute([$item['title'], $item['content'], $item['category'], $item['image'], current_user_id()]);
            $newId = (int)$pdo->lastInsertId();
            audit_log($pdo, 'create', 'announcements', $newId, $item['title']);
            flash_success('Announcement posted.');
        }
        header('Location: ' . BASE_URL . '/modules/announcements/list.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit Announcement' : 'Post Announcement';
$activeMenu = 'announcements';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel" style="max-width:680px;">
    <div class="panel-header">
        <h2><?= $id ? 'Edit Announcement' : 'Post a New Announcement' ?></h2>
        <a href="<?= BASE_URL ?>/modules/announcements/list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title <span class="required-mark">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= clean($item['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach (['Event','Advisory','Health','Emergency','General'] as $c): ?>
                            <option value="<?= $c ?>" <?= $item['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Content</label>
                    <textarea name="content" rows="6" class="form-control"><?= clean($item['content']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Image <span class="text-soft">(optional)</span></label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Publish</button>
                <a href="<?= BASE_URL ?>/modules/announcements/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
