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

            <!-- Active Shifts Section -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8">
                <h3 class="font-bold text-lg mb-4">Active Shifts (Need Checkout)</h3>
                <table class="w-full text-sm text-left">
                    <thead><tr class="text-slate-400 border-b"><th>Worker</th><th>Check-in Time</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $stmtActive = $pdo->query("SELECT u.id as user_id, u.name, l.check_in_time FROM attendance_logs l JOIN users u ON l.user_id = u.id WHERE l.check_out_time IS NULL ORDER BY l.check_in_time ASC");
                        while($log = $stmtActive->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-3"><?= e($log['name']) ?></td>
                                <td class="py-3 text-slate-500"><?= $log['check_in_time'] ?></td>
                                <td class="py-3">
                                    <button onclick="authorizeEarlyCheckout(<?= $log['user_id'] ?>)" class="text-xs bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold hover:bg-amber-200">Force Checkout</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Flagged Activity -->
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
                <?php renderCalendar($m, $y); ?>
            </div>
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
    </main>
</div>

<<script>
    const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";
    
    function authorizeEarlyCheckout(workerId) {
        if (!confirm("Authorize early checkout? This will bypass the time restriction.")) return;

        const params = new URLSearchParams();
        params.append('worker_id', workerId);
        params.append('csrf_token', CSRF_TOKEN); // Sending the token

        fetch('/logic/manager-override.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + data.msg);
            }
        });
    }
</script>
</body>
</html>
<?php ob_end_flush(); ?>