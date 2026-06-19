<?php
// /admin/export.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Prevent errors from appearing in the CSV output
error_reporting(0);
ini_set('display_errors', 0);

checkRole(['Admin', 'Manager']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fieldguard_attendance_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel compatibility

// 1. Define Headers
fputcsv($output, ['Log ID', 'Email', 'Site', 'Lat', 'Long', 'Status', 'Check-in', 'Check-out', 'Duty', 'Reason', 'Overridden By'], ',', '"', '\\');

// 2. Updated Query: LEFT JOIN on 'users' (aliased as 'm') to get the manager's name
$sql = "SELECT al.id, u.email, s.name as site_name, al.latitude, al.longitude, al.status, 
               al.check_in_time, al.check_out_time, al.shift_type, al.override_reason,
               m.name as manager_name
        FROM attendance_logs al 
        JOIN users u ON al.user_id = u.id 
        JOIN sites s ON al.site_id = s.id 
        LEFT JOIN users m ON al.overridden_by_id = m.id
        ORDER BY al.check_in_time DESC";

$stmt = $pdo->query($sql);

// 3. Process Rows
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
        $row['shift_type'],
        $row['override_reason'] ?? 'None',
        // If no manager name (NULL), default to 'N/A'
        $row['manager_name'] ?? 'N/A' 
    ], ',', '"', '\\');
}
fclose($output);
exit();