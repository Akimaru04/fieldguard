<?php
// /admin/export.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Security: Admin only (since you moved it to the admin folder)
checkRole(['Admin']);

// Set headers to force download as a CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fieldguard_attendance_' . date('Y-m-d') . '.csv"');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the column headers
fputcsv($output, ['Log ID', 'User ID', 'Site ID', 'Latitude', 'Longitude', 'Status', 'Timestamp']);

// Fetch the logs from the database
$stmt = $pdo->query("SELECT id, user_id, site_id, latitude, longitude, status, created_at FROM attendance_logs ORDER BY created_at DESC");

// Loop through the rows and write to the CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>