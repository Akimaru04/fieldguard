<?php
// /logic/process-checkin.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Upload Handling
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$photo_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '', $_FILES['photo']['name']);
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
    echo json_encode(['status' => 'error', 'msg' => 'File upload failed.']);
    exit;
}

// Geofence Verification
$site_id = $_POST['site_id'];
$user_lat = $_POST['latitude'];
$user_lng = $_POST['longitude'];

$stmt = $pdo->prepare("SELECT latitude, longitude, radius_meters FROM sites WHERE id = ?");
$stmt->execute([$site_id]);
$s = $stmt->fetch();

if (!$s) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid site selected.']);
    exit;
}

// Calculate distance and validate
$is_valid = isWithinGeofence($user_lat, $user_lng, $s['latitude'], $s['longitude'], $s['radius_meters']);
$status = $is_valid ? 'Valid' : 'Flagged';

// Immutable Logging
try {
    // UPDATED: Used 'check_in_time' instead of 'created_at' to match your schema
    $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, site_id, check_in_time, status, photo_url) 
                           VALUES (?, ?, NOW(), ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $site_id, $status, 'uploads/' . $photo_name]);

    echo json_encode(['status' => 'success', 'msg' => 'Check-in recorded as ' . $status]);
} catch (PDOException $e) {
    error_log($e->getMessage()); // Useful for debugging
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'Database error.']);
}
?>