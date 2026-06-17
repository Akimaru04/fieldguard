<?php
// /logic/process-checkout.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

// 1. Upload Handling
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$photo_name = 'checkout_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
    echo json_encode(['status' => 'error', 'msg' => 'Upload failed.']);
    exit;
}

try {
    // 2. Logic Guard: Check duration before allowing checkout
    // Using TIMESTAMPDIFF to calculate elapsed time in minutes
    $stmt = $pdo->prepare("
        SELECT TIMESTAMPDIFF(MINUTE, check_in_time, NOW()) as elapsed 
        FROM attendance_logs 
        WHERE user_id = ? AND check_out_time IS NULL 
        ORDER BY check_in_time DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $log = $stmt->fetch();

    $is_part_time = false; // Placeholder for actual part-time logic
    $min_required = $is_part_time ? 60 :480; // Safety buffer (in minutes)

    if (!$log || $log['elapsed'] < $min_required) {
        echo json_encode(['status' => 'error', 'msg' => 'Checkout not permitted. Please contact your supervisor if you need to end your shift early.']);
        exit;
    }

    // 3. Process Checkout
    $sql = "UPDATE attendance_logs 
            SET check_out_time = NOW(), photo_url = ? 
            WHERE user_id = ? AND check_out_time IS NULL 
            ORDER BY check_in_time DESC LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uploads/' . $photo_name, $_SESSION['user_id']]);

    echo json_encode(['status' => 'success', 'msg' => 'Checkout recorded.']);

} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'msg' => 'Database error.']);
}
?>