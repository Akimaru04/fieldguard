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

$days = (int)($_GET['range'] ?? 30);
$days = in_array($days, [14, 30]) ? $days : 30;
$dateFilter = date('Y-m-d H:i:s', strtotime("-$days days"));

$m = filter_input(INPUT_GET, 'm', FILTER_VALIDATE_INT) ?: (int)date('m');
$y = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT) ?: (int)date('Y');

$stmt1 = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE check_in_time >= ?");
$stmt1->execute([$dateFilter]);
$totalCheckins = $stmt1->fetchColumn();

$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE status = 'Flagged' AND check_in_time >= ?");
$stmt2->execute([$dateFilter]);
$flaggedCount = $stmt2->fetchColumn();

$activeSites = $pdo->query("SELECT COUNT(*) FROM sites WHERE is_active = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html class="h-full">
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="h-full bg-slate-50">
<div class="flex h-screen w-screen overflow-hidden">
    <aside class="w-64 border-r border-slate-200 bg-white"><?php include __DIR__ . '/../includes/sidebar.php'; ?></aside>
    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                    <p class="text-slate-500 text-sm">Attendance overview for the last <?= $days ?> days</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <a href="?range=14&m=<?= $m ?>&y=<?= $y ?>" class="px-4 py-2 text-xs font-bold rounded-lg <?= $days == 14 ? 'bg-white shadow-sm' : '' ?>">14 Days</a>
                        <a href="?range=30&m=<?= $m ?>&y=<?= $y ?>" class="px-4 py-2 text-xs font-bold rounded-lg <?= $days == 30 ? 'bg-white shadow-sm' : '' ?>">30 Days</a>
                    </div>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="/admin/export.php" class="bg-slate-800 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-black transition-all shadow-sm">Export Data</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-sm font-bold uppercase">Total Check-ins</h3>
                    <p class="text-4xl font-bold mt-2"><?= $totalCheckins ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-sm font-bold uppercase text-red-600">Flagged</h3>
                    <p class="text-4xl font-bold mt-2 text-red-600"><?= $flaggedCount ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-400 text-sm font-bold uppercase">Active Sites</h3>
                    <p class="text-4xl font-bold mt-2"><?= $activeSites ?></p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8">
                <h3 class="font-bold text-lg mb-4">Recent Flagged Activity</h3>
                <table class="w-full text-sm text-left">
                    <thead><tr class="text-slate-400 border-b"><th>Worker</th><th>Site</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php
                        $stmtLogs = $pdo->query("SELECT u.name, s.name as site_name, l.status FROM attendance_logs l JOIN users u ON l.user_id = u.id JOIN sites s ON l.site_id = s.id WHERE l.status = 'Flagged' ORDER BY l.check_in_time DESC LIMIT 5");
                        while($log = $stmtLogs->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="border-b last:border-0"><td class="py-3"><?= e($log['name']) ?></td><td class="py-3"><?= e($log['site_name']) ?></td><td class="py-3 text-red-600 font-bold"><?= e($log['status']) ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Calendar -->
            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-lg">Attendance Calendar - <?= date('F Y', strtotime("$y-$m-01")) ?></h3>
                    <div class="flex items-center gap-2">
                        <?php
                        $prevMonth = $m == 1 ? 12 : $m - 1; $prevYear = $m == 1 ? $y - 1 : $y;
                        $nextMonth = $m == 12 ? 1 : $m + 1; $nextYear = $m == 12 ? $y + 1 : $y;
                        ?>
                        <a href="?m=<?= $prevMonth ?>&y=<?= $prevYear ?>&range=<?= $days ?>" class="px-3 py-1 bg-slate-100 rounded-lg font-bold">&lt;</a>
                        <a href="?m=<?= (int)date('m') ?>&y=<?= (int)date('Y') ?>&range=<?= $days ?>" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg font-bold">Today</a>
                        <a href="?m=<?= $nextMonth ?>&y=<?= $nextYear ?>&range=<?= $days ?>" class="px-3 py-1 bg-slate-100 rounded-lg font-bold">&gt;</a>
                    </div>
                </div>
                <?php renderCalendar($m, $y); ?>
            </div>
            <footer class="mt-12 py-6 text-center text-slate-400 text-sm">&copy; <?= date('Y') ?> FieldGuard. All rights reserved.</footer>
        </div>
    </main>
</div>
</body>
</html>
<?php ob_end_flush(); ?>