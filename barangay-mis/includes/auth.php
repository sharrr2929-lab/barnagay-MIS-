<?php
/**
 * includes/auth.php
 * Session bootstrap, login checks, and role-based access control.
 * Requires config.php to already be loaded (needs BASE_URL).
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

/** All valid roles in the system, from broadest to narrowest access. */
const ALL_ROLES = ['super_admin', 'captain', 'secretary', 'staff', 'tanod'];

/** Roles allowed into "back office" administration (Users, Settings). */
const ADMIN_ROLES = ['super_admin', 'captain'];

/** Roles allowed into general data-entry modules. */
const STAFF_ROLES = ['super_admin', 'captain', 'secretary', 'staff'];

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

function current_full_name(): string
{
    return $_SESSION['full_name'] ?? '';
}

/** Redirect to login if not authenticated. Call at the top of every protected page. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Redirect to login (if not authenticated) OR show a 403 page
 * if the current user's role isn't in the allowed list.
 *
 * @param string[] $roles allowed roles, e.g. ADMIN_ROLES or ['super_admin']
 */
function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        $back = BASE_URL . '/dashboard.php';
        die(
            '<div style="font-family:Arial,sans-serif;max-width:520px;margin:100px auto;padding:32px;' .
            'text-align:center;border:1px solid #e2c9a0;background:#fff8ec;border-radius:10px;">' .
            '<h2 style="color:#8a3b12;margin-top:0;">403 &mdash; Access Denied</h2>' .
            '<p>Your account role (<strong>' . htmlspecialchars(current_role()) . '</strong>) does not have permission to view this page.</p>' .
            '<a href="' . $back . '" style="color:#123c5e;font-weight:600;">&larr; Return to Dashboard</a>' .
            '</div>'
        );
    }
}

/** Human-readable label for a role slug. */
function role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'captain'     => 'Barangay Captain',
        'secretary'   => 'Secretary',
        'staff'       => 'Staff/Clerk',
        'tanod'       => 'Tanod',
        default       => ucfirst($role),
    };
}

/** Log an action to audit_logs. Call after any create/update/delete. */
function audit_log(PDO $pdo, string $action, string $table, ?int $recordId = null, string $details = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, table_affected, record_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([current_user_id(), $action, $table, $recordId, $details]);
}
