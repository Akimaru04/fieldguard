<?php
// Ensure session is started once
if (session_status() === PHP_SESSION_NONE) session_start();

function checkRole($expectWorker) {
    // If not logged in, boot to index
    if (!isset($_SESSION['user_id'])) {
        header("Location: /index.php");
        exit();
    }

    // $expectWorker is true for worker dashboard, false for shared
    $isWorker = (bool)($_SESSION['is_worker'] ?? false);

    // If the role doesn't match the expectation, redirect
    if ($isWorker !== $expectWorker) {
        $target = $expectWorker ? '/worker/worker-dashboard.php' : '/shared/dashboard.php';
        
        // Only redirect if NOT already there
        if ($_SERVER['PHP_SELF'] !== $target) {
            header("Location: $target");
            exit();
        }
    }
}
?>