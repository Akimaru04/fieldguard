<?php
// /includes/sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? 'Field Worker';
$linkClass = "block px-4 py-3 rounded-xl text-sm font-semibold hover:bg-blue-50 hover:text-blue-700 transition-all";
?>

<aside class="w-64 h-screen bg-white border-r border-slate-200 flex flex-col flex-shrink-0">
    <div class="p-6">
        <h1 class="text-xl font-bold text-blue-600">FieldGuard</h1>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Attendance Validator</p>
    </div>

    <nav class="flex-1 px-4 space-y-1">
        <?php if ($role === 'Admin' || $role === 'Manager'): ?>
            <a href="/shared/dashboard.php" class="<?= $linkClass . ' ' . isActive('/shared/dashboard.php') ?>">Dashboard</a>
            <a href="/shared/audit-trail.php" class="<?= $linkClass . ' ' . isActive('/shared/audit-trail.php') ?>">Audit Trail</a>
            <a href="/shared/sites.php" class="<?= $linkClass . ' ' . isActive('/shared/sites.php') ?>">Sites</a>
        <?php endif; ?>

        <?php if ($role === 'Admin'): ?>
            <div class="pt-6 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Administration</div>
            <a href="/admin/team.php" class="<?= $linkClass . ' ' . isActive('/admin/team.php') ?>">Team Management</a>
        <?php endif; ?>

        <?php if ($role === 'Field Worker'): ?>
            <a href="/worker/worker-dashboard.php" class="<?= $linkClass . ' ' . isActive('/worker/worker-dashboard.php') ?>">Dashboard</a>
            <a href="/worker/attendance-history.php" class="<?= $linkClass . ' ' . isActive('/worker/attendance-history.php') ?>">Attendance History</a>
        <?php endif; ?>
    </nav>

    <div class="p-6 mt-auto border-t border-slate-100">
        <a href="/logic/logout.php" class="block text-red-600 font-medium p-3 rounded-lg hover:bg-red-50 text-sm">
            Sign Out
        </a>
    </div>
</aside>