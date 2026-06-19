<?php
// /shared/dashboard.php
ob_start();
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/calendar.php';
require_once __DIR__ . '/../includes/header.php';

checkRole(['Admin', 'Manager']);

// Filters
$days = (int)($_GET['range'] ?? 30);
$dateFilter = date('Y-m-d H:i:s', strtotime("-$days days"));
$m = filter_input(INPUT_GET, 'm', FILTER_VALIDATE_INT) ?: (int)date('m');
$y = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT) ?: (int)date('Y');

// Metrics
$totalCheckins = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE check_in_time >= ?");
$totalCheckins->execute([$dateFilter]);
$totalCheckins = $totalCheckins->fetchColumn();

$flaggedCount = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE status = 'Flagged' AND check_in_time >= ?");
$flaggedCount->execute([$dateFilter]);
$flaggedCount = $flaggedCount->fetchColumn();
?>
<!DOCTYPE html>
<html class="h-full">
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="h-full bg-slate-50">
<div class="flex h-screen w-full overflow-hidden">
    <aside class="w-64 border-r border-slate-200 bg-white hidden md:block flex-shrink-0"><?php include __DIR__ . '/../includes/sidebar.php'; ?></aside>
    
    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto w-full">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-slate-900">Command Center</h1>
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <a href="/admin/export.php" class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 transition">Export CSV</a>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Total Logs</h3>
                    <p class="text-3xl font-bold mt-2"><?= $totalCheckins ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest text-red-600">Flagged</h3>
                    <p class="text-3xl font-bold mt-2 text-red-600"><?= $flaggedCount ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Active Shifts</h3>
                    <p class="text-3xl font-bold mt-2"><?= $pdo->query("SELECT COUNT(*) FROM attendance_logs WHERE check_out_time IS NULL")->fetchColumn() ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
                <div class="p-6 border-b border-slate-100"><h3 class="font-bold text-slate-900">Active Shifts (Need Checkout)</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[500px]">
                        <thead class="bg-slate-50 text-slate-400">
                            <tr><th class="px-6 py-3">Worker</th><th class="px-6 py-3">Shift</th><th class="px-6 py-3">Check-in</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $stmtActive = $pdo->query("SELECT u.name, l.shift_type, l.check_in_time FROM attendance_logs l JOIN users u ON l.user_id = u.id WHERE l.check_out_time IS NULL AND l.check_in_time = (SELECT MAX(check_in_time) FROM attendance_logs WHERE user_id = u.id AND check_out_time IS NULL)");
                            while($log = $stmtActive->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr><td class="px-6 py-4"><?= e($log['name']) ?></td><td class="px-6 py-4"><?= e($log['shift_type']) ?></td><td class="px-6 py-4 text-slate-500"><?= $log['check_in_time'] ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
                <div class="p-6 border-b border-slate-100"><h3 class="font-bold text-slate-900">Recent Flagged Activity</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[500px]">
                        <thead class="bg-slate-50 text-slate-400">
                            <tr><th class="px-6 py-3">Worker</th><th class="px-6 py-3">Site</th><th class="px-6 py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $stmtLogs = $pdo->query("SELECT u.name, s.name as site_name, l.status FROM attendance_logs l JOIN users u ON l.user_id = u.id JOIN sites s ON l.site_id = s.id WHERE l.status = 'Flagged' ORDER BY l.check_in_time DESC LIMIT 5");
                            while($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr><td class="px-6 py-4"><?= e($log['name']) ?></td><td class="px-6 py-4"><?= e($log['site_name']) ?></td><td class="px-6 py-4 text-red-600 font-bold"><?= e($log['status']) ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-x-auto">
                <div class="flex justify-between items-center mb-6 min-w-[300px]">
                    <h3 class="font-bold text-lg"><?= date('F Y', mktime(0, 0, 0, $m, 1, $y)) ?></h3>
                    <div class="flex gap-2">
                        <a href="?m=<?= $m==1?12:$m-1 ?>&y=<?= $m==1?$y-1:$y ?>" class="px-4 py-2 bg-slate-100 rounded-lg text-sm font-bold">&larr;</a>
                        <a href="?" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-bold">Today</a>
                        <a href="?m=<?= $m==12?1:$m+1 ?>&y=<?= $m==12?$y+1:$y ?>" class="px-4 py-2 bg-slate-100 rounded-lg text-sm font-bold">&rarr;</a>
                    </div>
                </div>
                <div class="min-w-[500px]">
                    <?php renderCalendar($m, $y); ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
<?php ob_end_flush(); ?>