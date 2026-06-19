<?php
// /logic/checkin.php
session_start();
// Set timezone immediately
date_default_timezone_set('Asia/Manila'); 
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || ($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$long = filter_input(INPUT_POST, 'long', FILTER_VALIDATE_FLOAT);
$photo_url = filter_input(INPUT_POST, 'photo_url', FILTER_SANITIZE_URL) ?? '';

if (!empty($_FILES['photo']['name'])) {
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = uniqid('checkin_') . '.jpg';
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
        $photo_url = '/uploads/' . $filename;
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, geofence_radius_meters, (6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_meters FROM sites WHERE is_active = 1 ORDER BY distance_meters ASC LIMIT 1");
    $stmt->execute([$lat, $long, $lat]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$site) exit(json_encode(['status' => 'error', 'message' => 'No active site found.']));

    $status = ($site['distance_meters'] <= ($site['geofence_radius_meters'] ?? 150)) ? 'Valid' : 'Flagged';

    // Time-based shift calculation using Manila time
    $currentHour = (int)date('H');
    if ($currentHour >= 6 && $currentHour < 14) {
        $shiftType = 'Day';
    } elseif ($currentHour >= 14 && $currentHour < 22) {
        $shiftType = 'Night';
    } else {
        $shiftType = 'Graveyard';
    }

    $pdo->beginTransaction();

    // Use current Manila time for database consistency
    $checkInTime = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT user_id FROM user_assignments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE user_assignments SET site_id = ?, assigned_at = ?, ended_at = NULL WHERE user_id = ?")
            ->execute([$site['id'], $checkInTime, $_SESSION['user_id']]);
    } else {
        $pdo->prepare("INSERT INTO user_assignments (user_id, site_id, assigned_at, assigned_by) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], $site['id'], $checkInTime, $_SESSION['user_id']]);
    }

    $stmt = $pdo->prepare("INSERT INTO attendance_logs 
        (user_id, site_id, latitude, longitude, distance_from_site, status, photo_url, shift_type, check_in_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $site['id'], $lat, $long, $site['distance_meters'], $status, $photo_url, $shiftType, $checkInTime]);

    $pdo->commit();
    echo json_encode([
    'status' => 'success', 
    'message' => ($status === 'Flagged') ? 'Check-in successful (Flagged: Outside geofence)' : 'Check-in successful.',
    'attendance_status' => $status,
    'distance' => round($site['distance_meters'], 2)
]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}