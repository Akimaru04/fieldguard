<?php
// /admin/invite.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Security: Admin only
checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'Field Worker';
    
    // In a real app, generate a password or invite token here
    $stmt = $pdo->prepare("INSERT INTO users (email, role, name, created_at) VALUES (?, ?, 'New Member', NOW())");
    $stmt->execute([$email, $role]);
    
    // Redirect back to the team management page
    header("Location: /admin/team.php");
    exit();
}

// If someone hits this page directly without POST, bounce them back
header("Location: /admin/team.php");
exit();
?>