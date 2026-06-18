<?php
// /logic/process-checkout.php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// 1. Security & CSRF Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    exit(json_encode(['status' => 'error', 'msg' => 'Security token invalid.']));
}

// 2. Secure File Upload
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    exit(json_encode(['status' => 'error', 'msg' => 'Photo required.']));
}

$upload_dir = __DIR__ . '/../uploads/';
$file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, ['jpg', 'jpeg'])) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid file type.']));
}

$photo_name = 'checkout_' . $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '.jpg';
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
    exit(json_encode(['status' => 'error', 'msg' => 'Upload failed.']));
}

try {
    // 3. Logic Guard: Check duration
    $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, check_in_time, NOW()) as elapsed FROM attendance_logs WHERE user_id = ? AND check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $log = $stmt->fetch();

    $min_required = 480; // Default 8 hours

    if (!$log || $log['elapsed'] < $min_required) {
        // Cleanup the uploaded file if checkout is rejected
        unlink($upload_dir . $photo_name);
        exit(json_encode(['status' => 'error', 'msg' => 'Checkout not permitted. Shift duration incomplete.']));
    }

    // 4. Update Database
    $stmt = $pdo->prepare("UPDATE attendance_logs SET check_out_time = NOW(), photo_url = ? WHERE user_id = ? AND check_out_time IS NULL ORDER BY check_in_time DESC LIMIT 1");
    $stmt->execute(['uploads/' . $photo_name, $_SESSION['user_id']]);

    echo json_encode(['status' => 'success', 'msg' => 'Checkout recorded.']);

} catch (PDOException $e) {
    exit(json_encode(['status' => 'error', 'msg' => 'Database error.']));
}