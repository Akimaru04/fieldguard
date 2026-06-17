<?php
// /logic/checkin.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// 1. Auth and Security
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$long = filter_input(INPUT_POST, 'long', FILTER_VALIDATE_FLOAT);
$csrf = $_POST['csrf_token'] ?? '';

if ($csrf !== $_SESSION['csrf_token'] || $lat === false || $long === false) {
    exit(json_encode(['status' => 'error', 'message' => 'Invalid data or token']));
}

try {
    // 2. Spatial Query: Find nearest site within geofence
    // Using Haversine formula to calculate distance in meters
    $stmt = $pdo->prepare("
        SELECT id, 
               (6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_meters
        FROM sites 
        WHERE is_active = 1
        HAVING distance_meters <= COALESCE(geofence_radius_meters, 100)
        ORDER BY distance_meters ASC
        LIMIT 1
    ");
    $stmt->execute([$lat, $long, $lat]);
    $targetSite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetSite) {
        exit(json_encode(['status' => 'error', 'message' => 'You are outside the geofence of any active site.']));
    }

    // 3. Database Transaction: Atomic update and log
    $pdo->beginTransaction();
    
    // Close current assignment
    $pdo->prepare("UPDATE user_assignments SET ended_at = UTC_TIMESTAMP() WHERE user_id = ? AND ended_at IS NULL")
        ->execute([$_SESSION['user_id']]);
    
    // Create new assignment
    $pdo->prepare("INSERT INTO user_assignments (user_id, site_id, started_at) VALUES (?, ?, UTC_TIMESTAMP())")
        ->execute([$_SESSION['user_id'], $targetSite['id']]);
    
    // Log attendance
    $pdo->prepare("INSERT INTO attendance_logs (user_id, site_id, latitude, longitude, distance_from_site, status, check_in_time) VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())")
        ->execute([$_SESSION['user_id'], $targetSite['id'], $lat, $long, $targetSite['distance_meters'], 'Valid']);
    
    $pdo->commit();
    
    echo json_encode(['status' => 'success', 'site_id' => $targetSite['id']]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log error internally for debugging
    error_log("Check-in Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to process check-in. Please try again.']);
}