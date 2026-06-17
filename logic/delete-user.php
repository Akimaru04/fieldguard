<?php
// /logic/delete-user.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    // Soft delete: Keep the record, but mark it as inactive
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
}

header("Location: /admin/team.php");
exit();