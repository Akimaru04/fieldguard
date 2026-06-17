<?php
// /logic/check-out.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. CSRF Verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header("Location: /worker/worker-dashboard.php");
    exit();
}

if (isset($_SESSION['user_id'])) {
    try {
        $pdo->beginTransaction();

        // 1. Update attendance log: Close the last open session
        $stmt = $pdo->prepare("UPDATE attendance_logs 
                               SET check_out_time = UTC_TIMESTAMP() 
                               WHERE user_id = ? AND check_out_time IS NULL 
                               ORDER BY check_in_time DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);

        // 2. End the current active site assignment
        $stmt = $pdo->prepare("UPDATE user_assignments 
                               SET ended_at = UTC_TIMESTAMP() 
                               WHERE user_id = ? AND ended_at IS NULL");
        $stmt->execute([$_SESSION['user_id']]);

        $pdo->commit();
        $_SESSION['success'] = 'Check-out successful. You are now logged off.';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Checkout failed. Please contact your manager.';
    }
}

header("Location: /worker/worker-dashboard.php");
exit();