<?php
// /logic/checkin.php
session_start();
date_default_timezone_set('Asia/Manila'); 
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// 1. Validation: Security & Data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || ($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$long = filter_input(INPUT_POST, 'long', FILTER_VALIDATE_FLOAT);
$accuracy = filter_input(INPUT_POST, 'accuracy', FILTER_VALIDATE_FLOAT);
$site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);

// Enforce minimum GPS quality to stop "jumping" coordinates
if (!$lat || !$long || !$site_id || ($accuracy && $accuracy > 50)) {
    exit(json_encode(['status' => 'error', 'message' => 'GPS signal too weak or data missing.']));
}

// 2. Fetch specific assigned site only
$stmt = $pdo->prepare("SELECT s.id, s.latitude, s.longitude, s.geofence_radius_meters 
                       FROM sites s 
                       JOIN user_assignments ua ON s.id = ua.site_id 
                       WHERE s.id = ? AND ua.user_id = ? AND s.is_active = 1");
$stmt->execute([$site_id, $_SESSION['user_id']]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) exit(json_encode(['status' => 'error', 'message' => 'Site not assigned or inactive.']));

// 3. Haversine Distance Calculation
$dLat = deg2rad($site['latitude'] - $lat);
$dLon = deg2rad($site['longitude'] - $long);
$a = sin($dLat/2)**2 + cos(deg2rad($lat)) * cos(deg2rad($site['latitude'])) * sin($dLon/2)**2;
$dist = 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));

$status = ($dist <= ($site['geofence_radius_meters'] ?? 150)) ? 'Valid' : 'Flagged';

// 4. Handle Photo
$photo_url = '';
if (!empty($_FILES['photo']['name'])) {
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = uniqid('checkin_') . '.jpg';
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
        $photo_url = '/uploads/' . $filename;
    }
}

// 5. Database Transaction
try {
    $pdo->beginTransaction();
    $now = date('Y-m-d H:i:s');
    $shift = (date('H') >= 6 && date('H') < 14) ? 'Day' : ((date('H') >= 14 && date('H') < 22) ? 'Night' : 'Graveyard');

    $stmt = $pdo->prepare("INSERT INTO attendance_logs 
        (user_id, site_id, latitude, longitude, distance_from_site, status, photo_url, shift_type, check_in_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $site['id'], $lat, $long, $dist, $status, $photo_url, $shift, $now]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Check-in successful.', 'attendance_status' => $status, 'distance' => round($dist, 2)]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}