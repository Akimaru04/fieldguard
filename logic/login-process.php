<?php
// /logic/login-process.php
require_once __DIR__ . '/../config/db.php';
session_start();

// 1. Method and CSRF Protection
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header("Location: /index.php");
    exit();
}

// 2. Database Lookup
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$stmt = $pdo->prepare("SELECT id, name, role, password, is_active FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// 3. Unified Authentication Logic
// Checks password AND account status in one pass
if ($user && password_verify($_POST['password'], $user['password']) && (int)$user['is_active'] === 1) {
    
    // Regenerate to prevent session fixation attacks
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['role']    = trim($user['role']);

    // Clear any previous errors
    unset($_SESSION['error']);

    // Route based on role
    $target = ($_SESSION['role'] === 'Field Worker') ? '/worker/worker-dashboard.php' : '/shared/dashboard.php';
    header("Location: " . $target);
    exit();
} 

// 4. Centralized Failure
$_SESSION['error'] = 'Invalid credentials or account is inactive.';
header("Location: /index.php");
exit();