<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

checkRole(['Admin', 'Manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    
    // 1. Prevent self-updates
    if ((int)$_POST['user_id'] === (int)$_SESSION['user_id']) {
        header("Location: /admin/team.php?error=self_update");
        exit();
    }

    // 2. Prevent Managers from promoting others to Admin
    if ($_POST['role'] === 'Admin' && $_SESSION['role'] !== 'Admin') {
        header("Location: /admin/team.php?error=unauthorized");
        exit();
    }

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$_POST['role'], $_POST['user_id']]);
    
    header("Location: /admin/team.php?success=1");
    exit();
}
header("Location: /admin/team.php");
exit();