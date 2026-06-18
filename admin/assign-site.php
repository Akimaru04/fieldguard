<?php
// admin/assign-site.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

// Ensure only admins can assign sites
checkRole(['Admin']);

// Initialize CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch active users and sites
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'Field Worker' AND is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sites = $pdo->query("SELECT id, name FROM sites WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-lg mx-auto p-4 md:p-8">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Assign Worker to Site</h2>
        
        <?php if (empty($users) || empty($sites)): ?>
            <p class="text-sm text-amber-600 bg-amber-50 p-4 rounded-xl">No active workers or sites available for assignment.</p>
        <?php else: ?>
            <form action="/logic/process-assignment.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Worker</label>
                    <select name="user_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Site</label>
                    <select name="site_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach($sites as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-all shadow-md">
                    Assign Worker
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>