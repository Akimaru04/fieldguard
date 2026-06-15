<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
checkRole(['Admin', 'Manager']);

// 1. Handle Delete Action
if (isset($_GET['delete']) && $_SESSION['role'] === 'Admin') {
    $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: /shared/sites.php");
    exit();
}

// 2. Handle Create Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_site' && $_SESSION['role'] === 'Admin') {
    $stmt = $pdo->prepare("INSERT INTO sites (name, address, latitude, longitude, radius_meters, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$_POST['name'], $_POST['address'], $_POST['lat'], $_POST['lng'], $_POST['radius']]);
    header("Location: /shared/sites.php");
    exit();
}

include __DIR__ . '/../includes/header.php';
$sites = $pdo->query("SELECT * FROM sites ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <div class="p-8 flex-1 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Sites</h1>
                    <p class="text-slate-500 text-sm">Manage geofenced work locations</p>
                </div>
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <button onclick="document.getElementById('siteModal').classList.remove('hidden')" 
                            class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition-all">
                        + Add Site
                    </button>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($sites as $site): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                    <h3 class="font-bold text-lg text-slate-900"><?= e($site['name']) ?></h3>
                    <p class="text-slate-500 text-sm mb-4"><?= e($site['address']) ?></p>
                    
                    <div class="mt-auto mb-6 text-xs text-slate-400 space-y-1">
                        <p>📍 <?= e($site['latitude']) ?>, <?= e($site['longitude']) ?></p>
                        <p>📏 Radius: <?= e($site['radius_meters']) ?> meters</p>
                    </div>
                    
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <div class="flex gap-3">
                        <a href="/shared/edit-site.php?id=<?= $site['id'] ?>" class="flex-1 text-center border border-slate-200 py-2 rounded-lg text-sm font-medium hover:bg-slate-50">Edit</a>
                        <a href="?delete=<?= $site['id'] ?>" onclick="return confirm('Delete this site?');" class="flex-1 text-center border border-red-100 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50">Delete</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </main>
</div>

<div id="siteModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-8 w-full max-w-lg shadow-2xl">
        <h2 class="text-xl font-bold mb-6">Create New Site</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_site">
            <input type="text" name="name" placeholder="Site Name" class="w-full border p-3 rounded-xl" required>
            <input type="text" name="address" placeholder="Address" class="w-full border p-3 rounded-xl">
            <div class="flex gap-4">
                <input type="text" name="lat" placeholder="Latitude" class="w-full border p-3 rounded-xl" required>
                <input type="text" name="lng" placeholder="Longitude" class="w-full border p-3 rounded-xl" required>
            </div>
            <select name="radius" class="w-full border p-3 rounded-xl">
                <option value="50">50 meters</option>
                <option value="100" selected>100 meters</option>
                <option value="100" selected>150 meters</option>
                <option value="200">200 meters</option>
            </select>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('siteModal').classList.add('hidden')" class="flex-1 py-3 text-slate-600">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl">Add Site</button>
            </div>
        </form>
    </div>
</div>