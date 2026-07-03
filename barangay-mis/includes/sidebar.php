<?php
/**
 * includes/sidebar.php
 * Included by header.php. Expects $activeMenu to be set by the page,
 * and current_role() / current_full_name() to be available (auth.php).
 */
$activeMenu = $activeMenu ?? '';
$role = current_role();
$isAdmin = in_array($role, ADMIN_ROLES, true);
$isStaff = in_array($role, STAFF_ROLES, true); // includes admin roles
function nav_link(string $key, string $active, string $url, string $icon, string $label): string
{
    $cls = ($key === $active) ? 'active' : '';
    $aria = ($key === $active) ? ' aria-current="page"' : '';
    return '<a href="' . BASE_URL . $url . '" class="' . $cls . '"' . $aria . '><i class="bi ' . $icon . '"></i><span>' . $label . '</span></a>';
}
?>
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-seal">BM</div>
        <div class="sidebar-brand-text">
            <div class="name">Barangay MIS</div>
            <div class="sub">Community Records</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label">Overview</div>
        <?= nav_link('dashboard', $activeMenu, '/dashboard.php', 'bi-speedometer2', 'Dashboard') ?>

        <?php if ($isStaff): ?>
        <div class="nav-group-label">Records</div>
        <?= nav_link('residents', $activeMenu, '/modules/residents/list.php', 'bi-people', 'Residents') ?>
        <?= nav_link('households', $activeMenu, '/modules/households/list.php', 'bi-houses', 'Households') ?>
        <?= nav_link('documents', $activeMenu, '/modules/documents/list.php', 'bi-file-earmark-text', 'Certificates & Docs') ?>
        <?php endif; ?>

        <div class="nav-group-label">Community Safety</div>
        <?= nav_link('blotter', $activeMenu, '/modules/blotter/list.php', 'bi-journal-text', 'Blotter') ?>
        <?= nav_link('dispatch', $activeMenu, '/modules/dispatch/board.php', 'bi-broadcast', 'Dispatch Board') ?>
        <?= nav_link('roster', $activeMenu, '/modules/dispatch/roster.php', 'bi-shield-check', 'Tanod Roster') ?>

        <div class="nav-group-label">Community Life</div>
        <?= nav_link('announcements', $activeMenu, '/modules/announcements/list.php', 'bi-megaphone', 'Announcements') ?>
        <?= nav_link('events', $activeMenu, '/modules/events/calendar.php', 'bi-calendar-event', 'Events') ?>
        <?php if ($isStaff): ?>
        <?= nav_link('business', $activeMenu, '/modules/business/list.php', 'bi-shop', 'Business Permits') ?>
        <?= nav_link('requests', $activeMenu, '/modules/complaints/list.php', 'bi-inbox', 'Requests') ?>
        <?php endif; ?>
        <?= nav_link('officials', $activeMenu, '/modules/officials/list.php', 'bi-person-badge', 'Officials') ?>

        <?php if ($isStaff): ?>
        <div class="nav-group-label">Administration</div>
        <?= nav_link('reports', $activeMenu, '/modules/reports/index.php', 'bi-bar-chart', 'Reports') ?>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <?= nav_link('users', $activeMenu, '/modules/users/list.php', 'bi-person-gear', 'User Accounts') ?>
        <?= nav_link('settings', $activeMenu, '/modules/settings/index.php', 'bi-gear', 'Settings') ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
        Signed in as<br>
        <strong style="color:#fff;"><?= clean(current_full_name()) ?></strong> &middot; <?= clean(role_label($role)) ?>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
