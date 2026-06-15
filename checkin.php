<?php
// 1. Enforce Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed.']);
    exit;
}

// 2. Load Database Connection
require 'config/db.php';

// 3. Get and Validate Input
$userId = $_POST['user_id'] ?? null;
$lat    = $_POST['lat'] ?? null;
$long   = $_POST['long'] ?? null;

if (!$userId || !$lat || !$long) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

try {
    // 4. Fetch assigned site coordinates
    $stmt = $pdo->prepare("SELECT s.id, s.latitude, s.longitude, s.geofence_radius_meters 
                           FROM user_assignments ua 
                           JOIN sites s ON ua.site_id = s.id 
                           WHERE ua.user_id = ?");
    $stmt->execute([$userId]);
    $site = $stmt->fetch();

    if (!$site) {
        echo json_encode(['status' => 'error', 'message' => 'No site assigned to this user.']);
        exit;
    }

    // 5. Haversine Calculation Function
    function getDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));
    }

    $dist = getDistance($lat, $long, $site['latitude'], $site['longitude']);
    $status = ($dist <= $site['geofence_radius_meters']) ? 'Valid' : 'Flagged';

    // 6. Record to Database
    $stmt = $pdo->prepare("INSERT INTO attendance_logs 
                           (user_id, site_id, latitude, longitude, distance_from_site, status) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $site['id'], $lat, $long, $dist, $status]);

    echo json_encode([
        'status' => 'success', 
        'check_in_status' => $status,
        'distance' => round($dist, 2)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>