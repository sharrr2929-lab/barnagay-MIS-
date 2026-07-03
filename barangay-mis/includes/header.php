<?php
/**
 * includes/header.php
 * Opens the HTML document + topbar. Include AFTER config.php, auth.php,
 * functions.php, and any require_role() check.
 *
 * Optional variables a page can set before requiring this file:
 *   $pageTitle          (string) shown in <title> and as the H1
 *   $activeMenu         (string) key matching a sidebar link, for highlighting
 *   $breadcrumbEyebrow  (string) small label above the page title
 *   $useCharts          (bool)   loads Chart.js before </body> (see footer.php)
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$breadcrumbEyebrow = $breadcrumbEyebrow ?? 'Barangay Management Information System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?> · <?= clean(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" type="button" aria-label="Toggle menu"><i class="bi bi-list fs-4"></i></button>
                <div>
                    <div class="breadcrumb-eyebrow"><?= clean($breadcrumbEyebrow) ?></div>
                    <h1><?= clean($pageTitle) ?></h1>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar-circle"><?= clean(mb_strtoupper(mb_substr(current_full_name() ?: '?', 0, 1))) ?></span>
                    <span class="d-none d-sm-block text-start">
                        <span class="d-block fw-semibold" style="font-size:0.85rem;line-height:1.15;"><?= clean(current_full_name()) ?></span>
                        <span class="d-block text-soft" style="font-size:0.72rem;"><?= clean(role_label(current_role())) ?></span>
                    </span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (in_array(current_role(), ADMIN_ROLES, true)): ?>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/settings/index.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                </ul>
            </div>
        </div>

        <div class="content-wrap">
            <?= flash_render() ?>
