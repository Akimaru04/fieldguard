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

<div class="min-h-screen bg-slate-50 flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="w-80 flex-shrink-0"></div>

    <main class="flex-1 p-16 overflow-y-auto">
        <div class="max-w-2xl mx-auto bg-white p-12 rounded-3xl border border-slate-100 shadow-sm">
            <h1 class="text-3xl font-bold text-slate-900 mb-8">Edit Site</h1>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Site Name</label>
                    <input type="text" name="name" value="<?= e($data['name']) ?>" class="w-full border-2 border-slate-100 p-4 rounded-xl focus:border-blue-500 outline-none transition-colors" required>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Address</label>
                    <input type="text" name="address" value="<?= e($data['address']) ?>" class="w-full border-2 border-slate-100 p-4 rounded-xl focus:border-blue-500 outline-none transition-colors">
                </div>
                
                <div class="flex gap-6">
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Latitude</label>
                        <input type="text" name="lat" value="<?= e($data['latitude']) ?>" class="w-full border-2 border-slate-100 p-4 rounded-xl focus:border-blue-500 outline-none transition-colors" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Longitude</label>
                        <input type="text" name="lng" value="<?= e($data['longitude']) ?>" class="w-full border-2 border-slate-100 p-4 rounded-xl focus:border-blue-500 outline-none transition-colors" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Radius</label>
                    <select name="radius" class="w-full border-2 border-slate-100 p-4 rounded-xl bg-white focus:border-blue-500 outline-none transition-colors">
                        <?php 
                        // Use ?? to default to 0 if the key is missing from the database
                        $currentRadius = $data['radius_meters'] ?? 0; 
                        
                        foreach([50, 100, 150, 200, 500] as $r): ?>
                            <option value="<?=$r?>" <?= $currentRadius == $r ? 'selected' : '' ?>>
                                <?=$r?> meters
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-center justify-end gap-6 mt-10 pt-6 border-t border-slate-50">
                    <a href="/shared/sites.php" class="text-slate-500 font-semibold hover:text-slate-900 transition-colors">Cancel</a>
                    <button type="submit" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Save Changes</button>
                </div>
            </form>
        </div>
    </main>        
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>