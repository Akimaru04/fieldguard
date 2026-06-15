<?php
// /shared/edit-site.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Security: Admins only
checkRole(['Admin']);

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: /shared/sites.php");
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE sites SET name = ?, address = ?, latitude = ?, longitude = ?, radius_meters = ? WHERE id = ?");
    $stmt->execute([$_POST['name'], $_POST['address'], $_POST['lat'], $_POST['lng'], $_POST['radius'], $id]);
    header("Location: /shared/sites.php");
    exit();
}

// Fetch Current Data
$stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    header("Location: /shared/sites.php");
    exit();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
            <h1 class="text-2xl font-bold mb-6 text-slate-900">Edit Site</h1>
            
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Site Name</label>
                    <input type="text" name="name" value="<?= e($data['name']) ?>" class="w-full border border-slate-200 p-3 rounded-xl" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Address</label>
                    <input type="text" name="address" value="<?= e($data['address']) ?>" class="w-full border border-slate-200 p-3 rounded-xl">
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1 text-slate-700">Latitude</label>
                        <input type="text" name="lat" value="<?= e($data['latitude']) ?>" class="w-full border border-slate-200 p-3 rounded-xl" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1 text-slate-700">Longitude</label>
                        <input type="text" name="lng" value="<?= e($data['longitude']) ?>" class="w-full border border-slate-200 p-3 rounded-xl" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1 text-slate-700">Radius</label>
                    <select name="radius" class="w-full border border-slate-200 p-3 rounded-xl bg-white">
                        <?php foreach([50, 100, 150, 200, 500] as $r): ?>
                            <option value="<?=$r?>" <?= $data['radius_meters'] == $r ? 'selected' : '' ?>><?=$r?> meters</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="/shared/sites.php" class="px-6 py-3 text-slate-600 font-medium hover:text-slate-900">Cancel</a>
                    <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all">Save Changes</button>
                </div>
            </form>
        </div>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </main>        
</div>
