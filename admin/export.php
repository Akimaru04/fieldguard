<?php
// /admin/export.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

checkRole(['Admin']);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fieldguard_attendance_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Explicitly provide all parameters (delimiter, enclosure, escape) to satisfy PHP 8.1+
fputcsv($output, ['Log ID', 'User Email', 'Site Name', 'Latitude', 'Longitude', 'Status', 'Check-in Time'], ',', '"', '\\');

$sql = "SELECT al.id, u.email, s.name, al.latitude, al.longitude, al.status, al.check_in_time 
        FROM attendance_logs al 
        JOIN users u ON al.user_id = u.id 
        JOIN sites s ON al.site_id = s.id 
        ORDER BY al.check_in_time DESC";

$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Pass the row data and the explicit parameters here as well
    fputcsv($output, [
        $row['id'],
        $row['email'],
        $row['name'],
        $row['latitude'],
        $row['longitude'],
        $row['status'],
        $row['check_in_time']
    ], ',', '"', '\\');
}

fclose($output);
exit();
?>