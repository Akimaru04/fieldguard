<?php
// /worker/attendance-history.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
checkRole(['Field Worker']);

// Fetch history for the logged-in worker
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
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Attendance History</h1>
            
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="px-6 py-4">Site</th>
                            <th class="px-6 py-4">Date/Time</th>
                            <th class="px-6 py-4">Distance</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900"><?= e($log['site_name']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= e($log['check_in_time']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= number_format($log['distance_from_site'] ?? 0, 0) ?> m</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-lg text-xs font-bold <?= $log['status'] === 'Valid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= e($log['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>