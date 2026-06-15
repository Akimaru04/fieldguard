<?php
// /logic/delete-user.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Security: Enforce Admin-only access
checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
}

// Redirect back to the Team management page
header("Location: /admin/team.php");
exit();
?>