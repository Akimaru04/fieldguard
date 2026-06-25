<?php
// /logic/complete-setup.php
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];

    if ($password === $_POST['confirm_password']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE setup_token = ? AND is_active = 0");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare("UPDATE users SET password = ?, is_active = 1, setup_token = NULL WHERE id = ?");
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            header("Location: /index.php?status=activated");
            exit();
        }
    }
    die("Setup failed.");
}