<?php
// /includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global Auth Check
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['status' => 'error', 'msg' => 'Session expired.']));
}

function checkRole(array $allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        exit(json_encode(['status' => 'error', 'msg' => 'Unauthorized.']));
    }
}