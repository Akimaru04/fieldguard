<?php
// Fix: Ensure config is loaded first
require_once __DIR__ . '/../config/db.php'; 

// Fix: The session MUST start here, not just in db.php
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['is_worker'] = (trim($user['role']) === 'Field Worker');

        // Force redirect
$target = $_SESSION['is_worker'] ? '/worker/worker-dashboard.php' : '/shared/dashboard.php';        header("Location: $target");
        exit();
    }
    header("Location: /index.php?error=invalid");
    exit();
}
?>