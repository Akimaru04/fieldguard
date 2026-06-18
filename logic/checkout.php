<?php
// /logic/checkout.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

try {
    // 1. Fetch exact elapsed time in seconds for precision
    $stmt = $pdo->prepare("SELECT id, check_in_time, 
                           TIMESTAMPDIFF(SECOND, check_in_time, NOW()) as total_seconds 
                           FROM attendance_logs 
                           WHERE user_id = ? 
                           AND check_out_time IS NULL 
                           LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$log) {
        exit(json_encode(['status' => 'error', 'message' => 'No active shift found.']));
    }

    // Convert total seconds to full minutes
    $mins_elapsed = floor($log['total_seconds'] / 60);

    // 2. Strict 30-minute Gatekeeper
    if ($mins_elapsed < 30) {
        $unit = ($mins_elapsed == 1) ? 'minute' : 'minutes';
        exit(json_encode([
            'status' => 'error', 
            'message' => "Minimum 30 minutes required. Elapsed: {$mins_elapsed} {$unit}."
        ]));
    }

    // 3. Status logic: Flag if shift is 8 hours (480 minutes) or longer
    $status = ($mins_elapsed >= 480) ? 'Flagged' : 'Valid';

    // 4. Update attendance and user assignment
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE attendance_logs 
                           SET check_out_time = NOW(), 
                               status = ? 
                           WHERE id = ?");
    $stmt->execute([$status, $log['id']]);
    
    $pdo->prepare("UPDATE user_assignments 
                   SET ended_at = NOW() 
                   WHERE user_id = ? AND ended_at IS NULL")
        ->execute([$_SESSION['user_id']]);
        
    $pdo->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Shift completed successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'System error occurred.']);
}