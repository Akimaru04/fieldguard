<?php
// /logic/process-checkin.php
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

$photo_name = uniqid('checkin_', true) . '.jpg'; 
if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
    echo json_encode(['status' => 'error', 'msg' => 'Upload failed.']);
    exit;
}

// 2. Geofence Verification
$site_id = $_POST['site_id'];
$user_lat = (float)($_POST['latitude'] ?? 0);
$user_lng = (float)($_POST['longitude'] ?? 0);

$stmt = $pdo->prepare("SELECT latitude, longitude, geofence_radius_meters FROM sites WHERE id = ?");
$stmt->execute([$site_id]);
$s = $stmt->fetch();

if (!$s) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid site.']);
    exit;
}

// Calculate distance
$dist = getDistanceBetweenPoints($user_lat, $user_lng, (float)$s['latitude'], (float)$s['longitude']);
$status = ($dist <= $s['geofence_radius_meters']) ? 'Valid' : 'Flagged';

// 3. Immutable Logging
try {
    // Ensure these 8 placeholders match the 8 values in the execute array below
    $sql = "INSERT INTO attendance_logs 
            (user_id, site_id, check_in_time, status, photo_url, distance_from_site, latitude, longitude) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    // Pass exactly 8 values in the correct order
    $stmt->execute([
        $_SESSION['user_id'], 
        $site_id, 
        $status, 
        'uploads/' . $photo_name, 
        $dist, 
        $user_lat, 
        $user_lng
    ]);

    echo json_encode(['status' => 'success', 'msg' => 'Check-in recorded.']);
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'msg' => 'Database error.']);
}
?>