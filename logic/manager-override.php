<?php
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Ensure this doesn't redirect AJAX
require_once __DIR__ . '/../config/db.php';

// Role check
if ($_SESSION['role'] !== 'Manager' && $_SESSION['role'] !== 'Admin') {
    exit(json_encode(['status' => 'error', 'msg' => 'Unauthorized.']));
}

// Token validation
$inputToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (empty($inputToken) || $inputToken !== $sessionToken) {
    exit(json_encode(['status' => 'error', 'msg' => 'Security token invalid.']));
}

// Perform DB update
$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("UPDATE attendance_logs SET check_out_time = UTC_TIMESTAMP(), manual_override = 1, status = 'Overridden' WHERE user_id = ? AND check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 1");
$stmt->execute([$worker_id]);

echo json_encode(['status' => ($stmt->rowCount() > 0 ? 'success' : 'error'), 'msg' => 'Action processed.']);