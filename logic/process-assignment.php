<?php
// /logic/process-assignment.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Authorization & CSRF Check
checkRole(['Admin']);

$inputToken = $_POST['csrf_token'] ?? '';
if (empty($inputToken) || $inputToken !== ($_SESSION['csrf_token'] ?? '')) {
    die("Security token invalid.");
}

// 2. Data Validation
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);

if ($user_id && $site_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_assignments (user_id, site_id, assigned_by) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE site_id = VALUES(site_id), assigned_by = VALUES(assigned_by)");
        $stmt->execute([$user_id, $site_id, $_SESSION['user_id']]);
        
        header("Location: ../admin/team.php?status=success");
    } catch (PDOException $e) {
        header("Location: ../admin/team.php?status=error");
    }
} else {
    header("Location: ../admin/team.php?status=invalid");
}
exit();