<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$userLat = $_POST['latitude'];
$userLon = $_POST['longitude'];
$siteId = $_POST['site_id'];

// 1. Handle Photo Upload
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = 'capture_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
        $photoPath = 'uploads/' . $fileName;
    }
}

// 2. Fetch site data
$stmt = $pdo->prepare("SELECT latitude, longitude FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$sData = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Haversine calculation
$dist = 6371000 * 2 * atan2(sqrt(sin(deg2rad($sData['latitude'] - $userLat)/2)**2 + cos(deg2rad($userLat)) * cos(deg2rad($sData['latitude'])) * sin(deg2rad($sData['longitude'] - $userLon)/2)**2), sqrt(1 - (sin(deg2rad($sData['latitude'] - $userLat)/2)**2 + cos(deg2rad($userLat)) * cos(deg2rad($sData['latitude'])) * sin(deg2rad($sData['longitude'] - $userLon)/2)**2)));

// 4. Status enforcement
$status = ($dist > 50) ? 'Flagged' : 'Valid'; // Changed to 'Valid' to match your Audit Trail logic

// 5. Save record with photo_url
$stmt = $pdo->prepare("
    INSERT INTO attendance_logs (user_id, site_id, status, check_in_time, latitude, longitude, distance_from_site, photo_url) 
    VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
");
$stmt->execute([$_SESSION['user_id'], $siteId, $status, $userLat, $userLon, $dist, $photoPath]);

echo json_encode(['status' => 'success', 'msg' => ($status === 'Flagged' ? 'Warning: Checked in outside geofence (Flagged).' : 'Checked in successfully.')]);