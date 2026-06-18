<?php
// /field-worker/attendance-history.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
checkRole(['Field Worker']);

$stmt = $pdo->prepare("
    SELECT al.*, s.name as site_name 
    FROM attendance_logs al 
    JOIN sites s ON al.site_id = s.id 
    WHERE al.user_id = ? 
    ORDER BY al.check_in_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-50">
    <aside class="w-64 hidden md:block bg-white border-r border-slate-200 flex-shrink-0">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <main class="flex-1 w-full overflow-hidden">
        <div class="p-8 w-full">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Attendance History</h1>
                <p class="text-slate-500 text-sm">View your past check-in logs.</p>
            </div>
            
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm w-full">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-400">
                            <tr>
                                <th class="px-6 py-4 text-[10px] uppercase tracking-widest font-bold">Site</th>
                                <th class="px-6 py-4 text-[10px] uppercase tracking-widest font-bold">Date/Time</th>
                                <th class="px-6 py-4 text-[10px] uppercase tracking-widest font-bold">Distance</th>
                                <th class="px-6 py-4 text-[10px] uppercase tracking-widest font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($logs)): foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= e($log['site_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= e($log['check_in_time']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= number_format($log['distance_from_site'] ?? 0, 0) ?> m</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase <?= ($log['status'] === 'Flagged') ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' ?>">
                                        <?= e($log['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-400 text-sm">No attendance history found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>