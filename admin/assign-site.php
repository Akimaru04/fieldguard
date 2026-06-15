<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['Admin']);

// Fetch lists for dropdowns
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'Field Worker'")->fetchAll();
$sites = $pdo->query("SELECT id, name FROM sites")->fetchAll();
?>

<form action="../logic/process-assignment.php" method="POST">
    <select name="user_id">
        <?php foreach($users as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?>
    </select>
    
    <select name="site_id">
        <?php foreach($sites as $s) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?>
    </select>
    
    <button type="submit">Assign Site</button>
</form>