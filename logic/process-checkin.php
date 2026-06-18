<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Security Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    exit(json_encode(['status' => 'error', 'msg' => 'Security token invalid.']));
}

// 2. Input Sanitization
$userLat = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$userLon = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
$siteId = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);

if (!$userLat || !$userLon || !$siteId) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid check-in data.']));
}

// 3. Photo Upload (Added size/type checks)
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] < 5000000) {
        $uploadDir = __DIR__ . '/../uploads/';
        $fileName = 'checkin_' . bin2hex(random_bytes(8)) . '.jpg';
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $photoPath = 'uploads/' . $fileName;
        }
    }
}

// 4. Geofencing (Haversine)
$stmt = $pdo->prepare("SELECT latitude, longitude FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$sData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sData) exit(json_encode(['status' => 'error', 'msg' => 'Invalid site.']));

$dLat = deg2rad($sData['latitude'] - $userLat);
$dLon = deg2rad($sData['longitude'] - $userLon);
$a = sin($dLat/2)**2 + cos(deg2rad($userLat)) * cos(deg2rad($sData['latitude'])) * sin($dLon/2)**2;
$dist = 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));

$status = ($dist > 50) ? 'Flagged' : 'Valid';

// 5. Database Transaction
$stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, site_id, status, check_in_time, latitude, longitude, distance_from_site, photo_url) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $siteId, $status, $userLat, $userLon, $dist, $photoPath]);

echo json_encode(['status' => 'success', 'msg' => ($status === 'Flagged' ? 'Checked in (Flagged).' : 'Checked in successfully.')]);