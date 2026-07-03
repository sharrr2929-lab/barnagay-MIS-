<?php
/**
 * includes/functions.php
 * Shared helpers: CSRF tokens, flash messages, sanitizing, formatting,
 * file uploads, and small data lookups used across modules.
 * Requires config.php + auth.php (session) to already be loaded.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Echo this inside every <form> that POSTs. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/** Call at the top of every POST handler before touching the database. */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('<div style="font-family:Arial,sans-serif;max-width:480px;margin:100px auto;padding:24px;text-align:center;">' .
            '<h3>Invalid or expired form submission.</h3>' .
            '<p><a href="javascript:history.back()">&larr; Go back and try again</a></p></div>');
    }
}

// ---------------------------------------------------------------------
// Flash messages (one-time session notices shown after redirects)
// ---------------------------------------------------------------------
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_success(string $message): void { flash_set('success', $message); }
function flash_error(string $message): void { flash_set('danger', $message); }
function flash_info(string $message): void { flash_set('info', $message); }

/** Renders and clears any queued flash messages. Called from header.php. */
function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $type = htmlspecialchars($f['type']);
        $msg  = htmlspecialchars($f['message']);
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . $msg
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// ---------------------------------------------------------------------
// Sanitizing / formatting
// ---------------------------------------------------------------------
function clean(?string $value): string
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function post_str(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function post_int(string $key): ?int
{
    $v = $_POST[$key] ?? '';
    return ($v === '' || $v === null) ? null : (int)$v;
}

function post_float(string $key): float
{
    $v = $_POST[$key] ?? 0;
    return (float)$v;
}

function calculate_age(string $birthdate): int
{
    try {
        $bd  = new DateTime($birthdate);
        $now = new DateTime('today');
        return (int)$bd->diff($now)->y;
    } catch (Exception) {
        return 0;
    }
}

function format_date(?string $date, string $format = 'F d, Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return '—';
    }
}

function format_datetime(?string $datetime, string $format = 'F d, Y g:i A'): string
{
    return format_date($datetime, $format);
}

function format_currency(float|string|null $amount): string
{
    return '₱' . number_format((float)($amount ?? 0), 2);
}

function full_name_of(array $row): string
{
    $parts = array_filter([
        $row['first_name'] ?? '',
        !empty($row['middle_name']) ? mb_substr($row['middle_name'], 0, 1) . '.' : '',
        $row['last_name'] ?? '',
        $row['suffix'] ?? '',
    ]);
    return implode(' ', $parts);
}

// ---------------------------------------------------------------------
// Case / reference number generators
// ---------------------------------------------------------------------
function generate_reference_number(PDO $pdo, string $table, string $column, string $prefix): string
{
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} LIKE ?");
    $stmt->execute([$prefix . '-' . $year . '-%']);
    $count = (int)$stmt->fetchColumn() + 1;
    return sprintf('%s-%s-%03d', $prefix, $year, $count);
}

// ---------------------------------------------------------------------
// File uploads (photos / attachments)
// ---------------------------------------------------------------------
/**
 * Validates and moves an uploaded image. Returns the stored filename
 * (relative to /uploads/{subdir}/) on success, or null if no file was
 * uploaded. Throws RuntimeException on validation failure.
 */
function handle_photo_upload(string $fieldName, string $subdir = 'photos'): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (error code ' . $file['error'] . ').');
    }
    $maxBytes = 3 * 1024 * 1024; // 3MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File is too large. Maximum size is 3MB.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
    }
    $ext      = $allowed[$mime];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $destDir  = __DIR__ . '/../uploads/' . $subdir . '/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return $filename;
}

// ---------------------------------------------------------------------
// Small shared lookups (used in multiple modules' dropdowns)
// ---------------------------------------------------------------------
function get_puroks(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM puroks ORDER BY name')->fetchAll();
}

function get_residents_for_select(PDO $pdo): array
{
    return $pdo->query(
        "SELECT id, first_name, middle_name, last_name, suffix FROM residents WHERE status = 'active' ORDER BY last_name, first_name"
    )->fetchAll();
}

function setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}
