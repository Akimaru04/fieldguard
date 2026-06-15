<?php
// /includes/sidebar.php

// Ensure we have the role
$role = $_SESSION['role'] ?? 'Field Worker';

// Helper to determine active state
$currentPath = $_SERVER['REQUEST_URI'];
function isActive($path) {
    global $currentPath;
    return (strpos($currentPath, $path) !== false) ? 'bg-blue-50 text-blue-700' : 'text-slate-600';
}

$linkClass = "block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-blue-50 hover:text-blue-700 transition-all";
?>

<aside class="flex-none w-64 h-screen bg-white border-r border-slate-200 p-6 flex flex-col shadow-sm sticky top-0">
    <div class="mb-10 px-4">
        <h1 class="text-xl font-bold text-blue-600">FieldGuard</h1>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Attendance Validator</p>
    </div>

    <nav class="space-y-1 flex-1">
        <?php if ($role === 'Admin' || $role === 'Manager'): ?>
            <a href="/shared/dashboard.php" class="<?= $linkClass . ' ' . isActive('/shared/dashboard.php') ?>">Dashboard</a>
            <a href="/shared/audit-trail.php" class="<?= $linkClass . ' ' . isActive('/shared/audit-trail.php') ?>">Audit Trail</a>
            <a href="/shared/sites.php" class="<?= $linkClass . ' ' . isActive('/shared/sites.php') ?>">Sites</a>
        <?php endif; ?>

        <?php if ($role === 'Admin'): ?>
            <div class="pt-6 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Administration</div>
            <a href="/admin/team.php" class="<?= $linkClass . ' ' . isActive('/admin/team.php') ?>">Team Management</a>
        <?php endif; ?>

        <?php if ($role === 'Field Worker'): ?>
            <a href="/worker/checkin.php" class="<?= $linkClass . ' ' . isActive('/worker/checkin.php') ?>">Check-in</a>
        <?php endif; ?>
    </nav>

    <a href="/logic/logout.php" class="text-red-600 font-medium p-3 rounded-lg hover:bg-red-50">
    Sign Out
</a>
</aside>