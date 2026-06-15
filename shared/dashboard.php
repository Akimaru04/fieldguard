<?php
// /shared/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/calendar.php';
require_once __DIR__ . '/../includes/functions.php';

checkRole(['Admin', 'Manager']);

// 1. Time Range Logic
$days = (int)($_GET['range'] ?? 30);
$dateFilter = date('Y-m-d H:i:s', strtotime("-$days days"));

// 2. Data Fetching
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE check_in_time >= ?");
$stmt->execute([$dateFilter]);
$totalCheckins = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE status = 'Flagged' AND check_in_time >= ?");
$stmt->execute([$dateFilter]);
$flaggedCount = $stmt->fetchColumn();

$activeSites = $pdo->query("SELECT COUNT(*) FROM sites WHERE is_active = TRUE")->fetchColumn();

// 3. Calendar Navigation
$m = (int)($_GET['m'] ?? date('m'));
$y = (int)($_GET['y'] ?? date('Y'));

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                    <p class="text-slate-500 text-sm">Attendance overview for the last <?= $days ?> days</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <a href="?range=14" class="px-4 py-2 text-xs font-bold rounded-lg <?= $days == 14 ? 'bg-white shadow-sm' : '' ?>">14 Days</a>
                        <a href="?range=30" class="px-4 py-2 text-xs font-bold rounded-lg <?= $days == 30 ? 'bg-white shadow-sm' : '' ?>">30 Days</a>
                    </div>
                    
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="/admin/export.php" class="bg-slate-800 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-black transition-all shadow-sm">
                            Export Data
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-500 text-sm font-medium">Total Check-ins</h3>
                    <p class="text-4xl font-bold mt-2"><?= $totalCheckins ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-500 text-sm font-medium">Flagged</h3>
                    <p class="text-4xl font-bold mt-2 text-red-600"><?= $flaggedCount ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-slate-500 text-sm font-medium">Active Sites</h3>
                    <p class="text-4xl font-bold mt-2"><?= $activeSites ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8">
                <h3 class="font-bold text-lg mb-4">Recent Flagged Activity</h3>
                <table class="w-full text-sm text-left">
                    <thead><tr class="text-slate-400 border-b"><th>Worker</th><th>Site</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php
                        $logs = $pdo->query("SELECT u.name, s.name as site_name, l.status 
                                             FROM attendance_logs l 
                                             JOIN users u ON l.user_id = u.id 
                                             JOIN sites s ON l.site_id = s.id 
                                             WHERE l.status = 'Flagged' ORDER BY l.check_in_time DESC LIMIT 5")->fetchAll();
                        foreach($logs as $log): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-3"><?= e($log['name']) ?></td>
                                <td class="py-3"><?= e($log['site_name']) ?></td>
                                <td class="py-3 text-red-600 font-bold"><?= e($log['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-lg mb-6">Attendance Calendar</h3>
                <?php renderCalendar($m, $y); ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>