<?php
// logic/manager-override.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// 1. Authorization check
if ($_SESSION['role'] !== 'Manager') {
    exit(json_encode(['status' => 'error', 'msg' => 'Unauthorized']));
}

// 2. CSRF Validation
$inputToken = $_POST['csrf_token'] ?? '';
if (empty($inputToken) || !hash_equals($_SESSION['csrf_token'], $inputToken)) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid token']));
}

// 3. Input Validation
$log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$log_id || empty($reason)) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid input data']));
}

try {
    // 4. Atomic Update
    $stmt = $pdo->prepare("
        UPDATE attendance_logs 
        SET check_out_time = COALESCE(check_out_time, NOW()), 
            manual_override = 1, 
            status = 'Overridden', 
            override_reason = ?,
            overridden_by_id = ?
        WHERE id = ?
    ");

    $stmt->execute([$reason, $_SESSION['user_id'], $log_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'msg' => 'Override successful']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'No changes made or log not found']);
    }
} catch (PDOException $e) {
    error_log("Override Error: " . $e->getMessage()); // Log for server-side debugging
    echo json_encode(['status' => 'error', 'msg' => 'Database operation failed']);
}