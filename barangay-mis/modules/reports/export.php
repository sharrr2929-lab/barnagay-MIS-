<?php
/**
 * modules/reports/export.php
 * Streams a CSV file for the requested report type. CSV (not a bundled
 * PDF/Excel library) is used deliberately so reports work on a stock
 * XAMPP install with no Composer packages required.
 *
 * IMPORTANT: nothing may be echoed before the header() calls below.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(STAFF_ROLES);

$type = $_GET['type'] ?? '';
$allowed = ['residents', 'blotter', 'dispatch', 'events', 'documents'];
if (!in_array($type, $allowed, true)) {
    flash_error('Unknown report type.');
    header('Location: ' . BASE_URL . '/modules/reports/index.php');
    exit;
}

$filename = 'barangay-' . $type . '-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders special characters (e.g. ₱, ñ) correctly

if ($type === 'residents') {
    fputcsv($out, ['Last Name','First Name','Middle Name','Suffix','Birthdate','Age','Sex','Civil Status','Purok','Household #','Address','Contact','Occupation','Voter','PWD','Senior','4Ps','Status']);
    $rows = $pdo->query(
        "SELECT r.*, p.name AS purok_name, h.household_number FROM residents r
         LEFT JOIN puroks p ON p.id = r.purok_id LEFT JOIN households h ON h.id = r.household_id
         ORDER BY r.last_name, r.first_name"
    );
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['last_name'], $r['first_name'], $r['middle_name'], $r['suffix'], $r['birthdate'], calculate_age($r['birthdate']),
            $r['sex'], $r['civil_status'], $r['purok_name'], $r['household_number'], $r['address'], $r['contact_number'],
            $r['occupation'], $r['is_voter'] ? 'Yes' : 'No', $r['is_pwd'] ? 'Yes' : 'No', $r['is_senior'] ? 'Yes' : 'No',
            $r['is_4ps'] ? 'Yes' : 'No', $r['status'],
        ]);
    }
} elseif ($type === 'blotter') {
    fputcsv($out, ['Case #','Complainant','Respondent','Incident Type','Incident Date','Location','Status','Filed By','Date Filed']);
    $rows = $pdo->query('SELECT b.*, u.full_name AS filed_by_name FROM blotter_reports b LEFT JOIN users u ON u.id = b.filed_by ORDER BY b.incident_date DESC');
    foreach ($rows as $r) {
        fputcsv($out, [$r['case_number'], $r['complainant'], $r['respondent'], $r['incident_type'], $r['incident_date'], $r['location'], $r['status'], $r['filed_by_name'], $r['created_at']]);
    }
} elseif ($type === 'dispatch') {
    fputcsv($out, ['ID','Caller Name','Incident Type','Location','Priority','Status','Responder','Called At','Dispatched At','Arrived At','Resolved At','Response Time (min)']);
    $rows = $pdo->query('SELECT d.*, t.full_name AS responder_name FROM dispatch_calls d LEFT JOIN tanod_roster t ON t.id = d.responder_id ORDER BY d.called_at DESC');
    foreach ($rows as $r) {
        $responseMin = (!empty($r['dispatched_at']) && !empty($r['called_at']))
            ? round((strtotime($r['dispatched_at']) - strtotime($r['called_at'])) / 60)
            : '';
        fputcsv($out, [$r['id'], $r['caller_name'], $r['incident_type'], $r['location'], $r['priority'], $r['status'], $r['responder_name'], $r['called_at'], $r['dispatched_at'], $r['arrived_at'], $r['resolved_at'], $responseMin]);
    }
} elseif ($type === 'events') {
    fputcsv($out, ['Title','Type','Venue','Start','End','Organizer','Budget','Status','Attendee Count','Present Count']);
    $rows = $pdo->query(
        "SELECT e.*, (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id=e.id) AS attendee_count,
                (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id=e.id AND a.is_present=1) AS present_count
         FROM events e ORDER BY e.start_datetime DESC"
    );
    foreach ($rows as $r) {
        fputcsv($out, [$r['title'], $r['event_type'], $r['venue'], $r['start_datetime'], $r['end_datetime'], $r['organizer'], $r['budget'], $r['status'], $r['attendee_count'], $r['present_count']]);
    }
} elseif ($type === 'documents') {
    fputcsv($out, ['Resident','Document Type','Purpose','OR Number','Amount','Issued By','Issued At']);
    $rows = $pdo->query(
        "SELECT d.*, r.first_name, r.middle_name, r.last_name, r.suffix, u.full_name AS issued_by_name
         FROM documents_issued d JOIN residents r ON r.id = d.resident_id LEFT JOIN users u ON u.id = d.issued_by
         ORDER BY d.issued_at DESC"
    );
    foreach ($rows as $r) {
        fputcsv($out, [full_name_of($r), $r['document_type'], $r['purpose'], $r['or_number'], $r['amount'], $r['issued_by_name'], $r['issued_at']]);
    }
}

audit_log($pdo, 'export', $type, null, 'CSV export');
fclose($out);
exit;
