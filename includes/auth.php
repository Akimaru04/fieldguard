<?php
// /includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the public pages
$public_pages = ['/setup.php', '/logic/complete-setup.php'];

// Get the current request path
$current_page = $_SERVER['PHP_SELF'];

// Check if current page is in the public list OR if it's a sub-path
$is_public = false;
foreach ($public_pages as $page) {
    if (strpos($current_page, $page) !== false) {
        $is_public = true;
        break;
    }
}

// Only enforce login if NOT public
if (!$is_public) {
    if (empty($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }
}

function checkRole(array $allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        exit(json_encode(['status' => 'error', 'msg' => 'Unauthorized.']));
    }
}