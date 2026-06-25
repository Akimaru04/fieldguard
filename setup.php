<?php
// /setup.php
require_once __DIR__ . '/config/db.php';

$token = $_GET['token'] ?? '';

// Check if token exists and is valid
$stmt = $pdo->prepare("SELECT id FROM users WHERE setup_token LIKE ? AND is_active = 0");
$stmt->execute([$token . '%']); // The % wildcard handles the remaining characters

if (!$user = $stmt->fetch()) {
    die("Invalid or expired link.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - FieldGuard</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f4f4; margin: 0; }
        form { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 90%; max-width: 400px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <form action="/logic/complete-setup.php" method="POST">
        <h2>Set Password</h2>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="password" name="password" required placeholder="New Password">
        <input type="password" name="confirm_password" required placeholder="Confirm Password">
        <button type="submit">Activate Account</button>
    </form>
</body>
</html>