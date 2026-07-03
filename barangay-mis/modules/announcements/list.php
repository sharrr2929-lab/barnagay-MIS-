<?php
/**
 * modules/announcements/list.php
 * Bulletin-board style list; viewable by everyone, managed by staff.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(ALL_ROLES);
$canManage = in_array(current_role(), STAFF_ROLES, true);

$announcements = $pdo->query(
    "SELECT a.*, u.full_name AS posted_by_name FROM announcements a LEFT JOIN users u ON u.id = a.posted_by ORDER BY a.created_at DESC"
)->fetchAll();

$categoryIcon = [
    'Event' => 'bi-calendar-event', 'Advisory' => 'bi-info-circle', 'Health' => 'bi-heart-pulse',
    'Emergency' => 'bi-exclamation-triangle', 'General' => 'bi-megaphone',
];

$pageTitle = 'Announcements';
$activeMenu = 'announcements';
$breadcrumbEyebrow = 'Community Life';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Bulletin Board</h2>
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/modules/announcements/form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Post Announcement</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if (empty($announcements)): ?>
            <div class="empty-state"><i class="bi bi-megaphone"></i>No announcements posted yet.</div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($announcements as $a): ?>
            <div class="col-md-6">
                <div class="panel h-100 mb-0">
                    <div class="panel-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border"><i class="bi <?= $categoryIcon[$a['category']] ?? 'bi-megaphone' ?> me-1"></i><?= clean($a['category']) ?></span>
                            <span class="text-soft small"><?= format_date($a['created_at']) ?></span>
                        </div>
                        <h3 class="h5"><?= clean($a['title']) ?></h3>
                        <p class="text-soft" style="white-space:pre-wrap;"><?= clean($a['content']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-soft small">Posted by <?= clean($a['posted_by_name'] ?? 'Barangay Office') ?></span>
                            <?php if ($canManage): ?>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>/modules/announcements/form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= BASE_URL ?>/modules/announcements/delete.php" data-confirm="Delete this announcement?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
