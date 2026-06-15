<?php
// /worker/worker-dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Gatekeeper: Must be called before any HTML or database queries
checkRole(['Field Worker']);

// 1. Fetch Assigned Site
$stmt = $pdo->prepare("
    SELECT s.* FROM sites s 
    JOIN user_assignments ua ON s.id = ua.site_id 
    WHERE ua.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$site = $stmt->fetch();
?>

<div class="max-w-md mx-auto p-6">
    <h2 class="text-2xl font-bold mb-6">Hello, <?= e($_SESSION['name'] ?? 'Worker') ?></h2>

    <?php if ($site): ?>
        <div class="bg-indigo-600 text-white p-6 rounded-2xl shadow-lg mb-6">
            <p class="text-indigo-200 text-xs font-bold uppercase tracking-widest">Assigned Site</p>
            <h1 class="text-2xl font-bold mt-1"><?= e($site['name']) ?></h1>
            <p class="text-indigo-100 text-sm opacity-90"><?= e($site['address']) ?></p>
        </div>
        <?php else: ?>
        <div class="p-8 border-2 border-dashed border-slate-300 rounded-2xl text-center text-slate-500">
            <p>No site assigned yet.</p>
        </div>
    <?php endif; ?>
</div>