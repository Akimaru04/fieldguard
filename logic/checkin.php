<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

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
    $stmt = $pdo->prepare("SELECT s.id, s.latitude, s.longitude, s.geofence_radius_meters 
                           FROM user_assignments ua 
                           JOIN sites s ON ua.site_id = s.id 
                           WHERE ua.user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$site) exit(json_encode(['status' => 'error', 'message' => 'No site assigned']));

    // Haversine Distance
    $earthRadius = 6371000;
    $dLat = deg2rad($lat - $site['latitude']);
    $dLon = deg2rad($long - $site['longitude']);
    $a = sin($dLat/2)**2 + cos(deg2rad($site['latitude'])) * cos(deg2rad($lat)) * sin($dLon/2)**2;
    $dist = $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));

    $status = ($dist <= $site['geofence_radius_meters']) ? 'Valid' : 'Flagged';

    $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, site_id, latitude, longitude, distance_from_site, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $site['id'], $lat, $long, $dist, $status]);

    echo json_encode(['status' => 'success', 'check_in_status' => $status]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}