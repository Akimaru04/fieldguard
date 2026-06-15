<?php
// /shared/audit-trail.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Security: Both Admin and Manager can review
checkRole(['Admin', 'Manager']);

// Fetch logs with role-based visibility
if ($_SESSION['role'] === 'Admin') {
    // Admins see everything
    $logs = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Managers only see 'Field Worker' logs
    $logs = $pdo->query("
        SELECT al.* FROM audit_logs al 
        JOIN users u ON al.user_email = u.email 
        WHERE u.role = 'Field Worker' 
        ORDER BY al.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <div class="p-8 flex-1 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Audit Trail</h1>
                        <p class="text-slate-500 text-sm">Review attendance logs</p>
                    </div>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="/admin/export.php" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-black transition-all">
                            Export CSV
                        </a>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">User</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Site</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Check-in Time</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= e($log['user_email']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= e($log['site_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="<?= $log['status'] === 'Valid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> font-bold text-[10px] px-3 py-1 rounded-full uppercase">
                                        <?= e($log['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </main>
</div>