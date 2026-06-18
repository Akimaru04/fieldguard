<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (!in_array($_SESSION['role'], ['Manager', 'Admin'])) {
    exit(json_encode(['status' => 'error', 'msg' => 'Unauthorized']));
}

$inputToken = $_POST['csrf_token'] ?? '';
if (empty($inputToken) || $inputToken !== ($_SESSION['csrf_token'] ?? '')) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid token']));
}

$log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);

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
echo json_encode(['status' => ($stmt->rowCount() > 0 ? 'success' : 'error')]);