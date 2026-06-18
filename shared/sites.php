<?php
// shared/sites.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
checkRole(['Admin', 'Manager']);

// 1. Handle Delete (Complete removal)
if (isset($_GET['delete']) && $_SESSION['role'] === 'Admin') {
    $site_id = $_GET['delete'];
    
    // Step A: Delete all attendance logs associated with this site
    $stmt1 = $pdo->prepare("DELETE FROM attendance_logs WHERE site_id = ?");
    $stmt1->execute([$site_id]);
    
    // Step B: Now delete the site itself
    $stmt2 = $pdo->prepare("DELETE FROM sites WHERE id = ?");
    $stmt2->execute([$site_id]);
    
    header("Location: /shared/sites.php");
    exit();
}

// 2. Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_site' && $_SESSION['role'] === 'Admin') {
    $stmt = $pdo->prepare("INSERT INTO sites (name, address, latitude, longitude, radius_meters, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$_POST['name'], $_POST['address'], $_POST['lat'], $_POST['lng'], $_POST['radius']]);
    header("Location: /shared/sites.php");
    exit();
}

include __DIR__ . '/../includes/header.php';
$sites = $pdo->query("SELECT * FROM sites ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen bg-slate-50">
    <div class="w-64 flex-shrink-0 border-r border-slate-200 bg-white hidden md:block">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 flex flex-col min-h-screen">
        <div class="flex-grow p-6 md:p-10">
            <div class="flex justify-between items-center mb-10 w-full">
                <div class="text-left">
                    <h1 class="text-3xl font-bold text-slate-900">Sites</h1>
                    <p class="text-slate-500 mt-1">Manage geofenced work locations</p>
                </div>
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <button onclick="document.getElementById('siteModal').classList.remove('hidden')" 
                            class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-md">
                        + Add Site
                    </button>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 w-full">
                <?php foreach ($sites as $site): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col w-full min-h-[280px]">
                    <h3 class="font-bold text-xl text-slate-900 leading-tight"><?= e($site['name']) ?></h3>
                    <p class="text-slate-500 text-sm mt-3 mb-6 leading-relaxed flex-grow"><?= e($site['address']) ?></p>
                    <div class="mt-auto mb-6 text-sm text-slate-400 space-y-2">
                        <p class="font-medium text-slate-500">📍 <?= e($site['latitude']) ?>, <?= e($site['longitude']) ?></p>
                        <p class="font-medium text-slate-500">📏 Radius: <?= e($site['radius_meters'] ?? '100') ?>m</p>
                    </div>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <div class="flex gap-4 pt-4 border-t border-slate-50">
                        <a href="/shared/edit-site.php?id=<?= $site['id'] ?>" class="flex-1 text-center bg-slate-50 py-3 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors">Edit</a>
                        <a href="?delete=<?= $site['id'] ?>" onclick="return confirm('Delete this site?');" class="flex-1 text-center text-red-600 bg-red-50 py-3 rounded-xl text-sm font-bold hover:bg-red-100 transition-colors">Delete</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="w-full text-center">
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
    </main>
</div>

<div id="siteModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-10 w-full max-w-lg shadow-2xl">
        <h2 class="text-xl font-bold mb-6">Create New Site</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_site">
            <input type="text" name="name" placeholder="Site Name" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200" required>
            <input type="text" name="address" placeholder="Address" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200">
            <div class="flex gap-4">
                <input type="text" name="lat" placeholder="Latitude" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200" required>
                <input type="text" name="lng" placeholder="Longitude" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200" required>
            </div>
            <select name="radius" class="w-full p-4 bg-slate-50 rounded-xl border border-slate-200">
                <option value="50">50 meters</option>
                <option value="100" selected>100 meters</option>
                <option value="150">150 meters</option>
                <option value="200">200 meters</option>
                <option value="500">500 meters</option>
            </select>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('siteModal').classList.add('hidden')" class="flex-1 py-4 bg-slate-100 font-bold rounded-xl text-slate-700">Cancel</button>
                <button type="submit" class="flex-1 py-4 bg-blue-600 text-white font-bold rounded-xl">Add Site</button>
            </div>
        </form>
    </div>
</div>