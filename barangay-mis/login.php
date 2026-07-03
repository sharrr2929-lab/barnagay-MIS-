<?php
/**
 * login.php
 * Standalone login screen (does not use the sidebar shell in includes/header.php).
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = post_str('username');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            audit_log($pdo, 'login', 'users', (int)$user['id']);
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } elseif ($user && $user['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact your administrator.';
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$barangayName = setting($pdo, 'barangay_name', 'Barangay Malaya');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · <?= clean(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-shell">
    <div class="login-side">
        <div class="sidebar-seal mb-4" style="width:60px;height:60px;font-size:1.5rem;">BM</div>
        <h1>Barangay Management Information System</h1>
        <p>One place for resident records, certificates, blotter, dispatch, and community life for <?= clean($barangayName) ?>.</p>
        <div class="d-flex gap-4 mt-4 flex-wrap">
            <div>
                <div class="fs-4 fw-bold mono" style="color:#fff;">13</div>
                <div class="text-white-50" style="font-size:0.78rem;">Integrated modules</div>
            </div>
            <div>
                <div class="fs-4 fw-bold mono" style="color:#fff;">24/7</div>
                <div class="text-white-50" style="font-size:0.78rem;">Dispatch logging</div>
            </div>
        </div>
    </div>
    <div class="login-form-col">
        <div class="login-card">
            <div class="login-seal">BM</div>
            <h2 class="mb-1">Welcome back</h2>
            <p class="text-soft mb-4">Sign in with your barangay staff account.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= clean($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/login.php" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus
                           value="<?= clean($_POST['username'] ?? '') ?>" autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
            </form>

            <div class="mt-4 p-3 rounded" style="background:var(--paper-dim);font-size:0.82rem;">
                <strong>Demo accounts</strong> (password: <code>admin123</code>)<br>
                <span class="text-soft">admin</span> — Super Admin ·
                <span class="text-soft">secretary</span> — Secretary ·
                <span class="text-soft">tanod1</span> — Tanod
            </div>
        </div>
    </div>
</div>
</body>
</html>
