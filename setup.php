<?php
// /setup.php
require_once __DIR__ . '/config/db.php';
$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM users WHERE setup_token = ? AND is_active = 0");
$stmt->execute([$token]);
if (!$stmt->fetch()) die("Invalid or expired link.");
?>

<form action="/logic/complete-setup.php" method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <input type="password" name="password" required placeholder="New Password">
    <input type="password" name="confirm_password" required placeholder="Confirm Password">
    <button type="submit">Activate Account</button>
</form>