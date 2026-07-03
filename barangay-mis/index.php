<?php
/**
 * index.php
 * Entry point — sends visitors to the dashboard (if logged in) or login page.
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . BASE_URL . (is_logged_in() ? '/dashboard.php' : '/login.php'));
exit;
