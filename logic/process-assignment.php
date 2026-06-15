<?php
// /logic/process-assignment.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$user_id = $_POST['user_id'] ?? null;
$site_id = $_POST['site_id'] ?? null;

if ($user_id && $site_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_assignments (user_id, site_id, assigned_by) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE site_id = VALUES(site_id), assigned_by = VALUES(assigned_by)");
        $stmt->execute([$user_id, $site_id, $_SESSION['user_id']]);
        
        header("Location: ../admin/team.php?status=success");
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header("Location: ../admin/team.php?status=error");
    }
} else {
    header("Location: ../admin/team.php?status=invalid");
}