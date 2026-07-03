<?php
/**
 * config.php
 * Central configuration: database connection + base URL.
 * Target environment: XAMPP 8.2.12-0-VS16 (Apache + MySQL, PHP 8.2)
 *
 * Default XAMPP MySQL credentials are root / (no password). If you set a
 * MySQL root password or created a dedicated DB user, update DB_USER /
 * DB_PASS below.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------
// Database settings
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_mis');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// BASE_URL is auto-detected from where this file lives on disk, so the
// app works no matter what you name the project folder inside htdocs
// (e.g. C:\xampp\htdocs\barangay-mis or C:\xampp\htdocs\bmis).
// ---------------------------------------------------------------------
$__projectRoot = str_replace('\\', '/', __DIR__);
$__docRoot     = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) : '';
$__baseUrl     = ($__docRoot !== '' && stripos($__projectRoot, $__docRoot) === 0)
    ? substr($__projectRoot, strlen($__docRoot))
    : '';
define('BASE_URL', $__baseUrl); // e.g. "/barangay-mis" (empty string if run at web root)
define('APP_NAME', 'Barangay Management Information System');

// ---------------------------------------------------------------------
// Timezone (Philippines)
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Manila');

// ---------------------------------------------------------------------
// PDO connection
// ---------------------------------------------------------------------
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(
        '<div style="font-family:Arial,sans-serif;max-width:640px;margin:80px auto;padding:24px;' .
        'border:1px solid #e0c9a6;background:#fff8ec;border-radius:8px;">' .
        '<h2 style="margin-top:0;color:#8a3b12;">Database connection failed</h2>' .
        '<p>Please check the following in your XAMPP setup:</p>' .
        '<ol>' .
        '<li>The <strong>Apache</strong> and <strong>MySQL</strong> services are running in the XAMPP Control Panel.</li>' .
        '<li>The <code>barangay_mis</code> database has been imported via phpMyAdmin (see <code>database.sql</code>).</li>' .
        '<li>The credentials in <code>config.php</code> (DB_USER / DB_PASS) match your MySQL setup.</li>' .
        '</ol>' .
        '<p style="color:#8a3b12;"><small>' . htmlspecialchars($e->getMessage()) . '</small></p>' .
        '</div>'
    );
}
