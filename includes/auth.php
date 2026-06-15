<?php
// /includes/auth.php

// 1. Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Immediate enforcement for pages that include this directly
if (empty($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

/**
 * Checks if the logged-in user has the required roles.
 * @param array $allowedRoles Array of permitted roles (e.g., ['Admin', 'Manager'])
 */
function checkRole(array $allowedRoles) {
    // Re-verify session status
    if (empty($_SESSION['user_id'])) {
        header("Location: /index.php");
        exit();
    }

    $userRole = strtolower(trim($_SESSION['role'] ?? ''));
    $normalizedAllowed = array_map(fn($r) => strtolower(trim($r)), $allowedRoles);

    if (!in_array($userRole, $normalizedAllowed)) {
        header("Location: /index.php?error=unauthorized");
        exit();
    }
}
?>