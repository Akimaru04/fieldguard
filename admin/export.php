<?php
// admin/export.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure only authorized users can export data
checkRole(['Admin']);

// Security: Prevent browser from sniffing content type
header('X-Content-Type-Options: nosniff');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fieldguard_attendance_' . date('Y-m-d') . '.csv"');

// Open the output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel character compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header
fputcsv($output, ['Log ID', 'User Email', 'Site Name', 'Latitude', 'Longitude', 'Status', 'Check-in Time']);

// Query the database
$sql = "SELECT al.id, u.email, s.name as site_name, al.latitude, al.longitude, al.status, al.check_in_time 
        FROM attendance_logs al 
        JOIN users u ON al.user_id = u.id 
        JOIN sites s ON al.site_id = s.id 
        ORDER BY al.check_in_time DESC";

$stmt = $pdo->query($sql);

// Export data
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['email'],
        $row['site_name'],
        $row['latitude'] ?? 'N/A',
        $row['longitude'] ?? 'N/A',
        $row['status'],
        $row['check_in_time']
    ]);
}

fclose($output);
exit();