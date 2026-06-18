<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

date_default_timezone_set('Asia/Manila');

error_reporting(0);
ini_set('display_errors', 0);

checkRole(['Admin', 'Manager']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fieldguard_attendance_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

fputcsv($output, ['Log ID', 'Email', 'Site', 'Lat', 'Long', 'Status', 'Check-in', 'Check-out', 'Duty', 'Reason']);

// Corrected Query: Fetching 'shift_type' as 'duty' and adding 'override_reason'
$sql = "SELECT al.id, u.email, s.name as site_name, al.latitude, al.longitude, al.status, 
               al.check_in_time, al.check_out_time, al.shift_type, al.override_reason 
        FROM attendance_logs al 
        JOIN users u ON al.user_id = u.id 
        JOIN sites s ON al.site_id = s.id 
        ORDER BY al.check_in_time DESC";

$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['email'],
        $row['site_name'], 
        $row['latitude'],
        $row['longitude'],
        $row['status'],
        $row['check_in_time'],
        $row['check_out_time'],
        $row['shift_type'],      // Maps to 'Duty' column
        $row['override_reason'],
        $row['overridden_by'] ?? 'N/A' // Assuming you have a column for who overrode the log; if not, you can remove this line
    ]);
}
fclose($output);
exit();