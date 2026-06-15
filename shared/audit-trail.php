<?php
// /shared/audit-trail.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(['Admin', 'Manager']);

// 1. Ensure 'distance_from_site' is selected in the query
$query = "
    SELECT al.*, u.email as user_email, s.name as site_name, al.distance_from_site
    FROM attendance_logs al 
    JOIN users u ON al.user_id = u.id 
    JOIN sites s ON al.site_id = s.id
";

if ($_SESSION['role'] === 'Admin') {
    $stmt = $pdo->query($query . " ORDER BY al.check_in_time DESC");
} else {
    $stmt = $pdo->prepare($query . " WHERE u.role = 'Field Worker' ORDER BY al.check_in_time DESC");
    $stmt->execute();
}
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <p class="text-slate-500 text-sm">Review all field attendance logs</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">User</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Site</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Distance</th> <th class="px-6 py-4 text-sm font-semibold text-slate-700">Time</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Status</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-700">Photo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= e($log['user_email']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= e($log['site_name']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= !empty($log['distance_from_site']) ? e(number_format($log['distance_from_site'], 2)) . ' m' : 'N/A' ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= date('M d, H:i', strtotime($log['check_in_time'])) ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="<?= $log['status'] === 'Valid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> font-bold text-[10px] px-3 py-1 rounded-full uppercase">
                                        <?= e($log['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if ($log['photo_url']): ?>
                                        <button onclick="viewPhoto('/<?= e($log['photo_url']) ?>')" class="text-indigo-600 font-bold hover:text-indigo-900 underline">View</button>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs italic">None</span>
                                    <?php endif; ?>
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