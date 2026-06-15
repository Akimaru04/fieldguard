<?php
// /admin/team.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

checkRole(['Admin']);

// Fetch team members with their current site assignment
$team = $pdo->query("
    SELECT u.*, s.name as assigned_site 
    FROM users u 
    LEFT JOIN user_assignments ua ON u.id = ua.user_id 
    LEFT JOIN sites s ON ua.site_id = s.id 
    ORDER BY u.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$sites = $pdo->query("SELECT id, name FROM sites ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold mb-6">Team Management</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($team as $member): ?>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-lg"><?= e($member['name']) ?></h3>
                <p class="text-slate-500 text-sm mb-4"><?= e($member['email']) ?></p>
                
                <form action="/logic/update-role.php" method="POST" class="flex gap-2 mb-4">
                    <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                    <select name="role" class="bg-slate-50 border p-1 rounded text-xs flex-grow">
                        <option value="Field Worker" <?= $member['role'] == 'Field Worker' ? 'selected' : '' ?>>Field Worker</option>
                        <option value="Manager" <?= $member['role'] == 'Manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="Admin" <?= $member['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold">Save</button>
                </form>

                <?php if ($member['role'] === 'Field Worker'): ?>
                    <form action="/logic/process-assignment.php" method="POST" class="border-t pt-4">
                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                        <p class="text-[10px] uppercase font-bold text-slate-400 mb-2">
                            Current: <?= e($member['assigned_site'] ?? 'Unassigned') ?>
                        </p>
                        <div class="flex gap-2">
                            <select name="site_id" class="bg-slate-50 border p-1 rounded text-xs flex-grow">
                                <option value="">Reassign Site...</option>
                                <?php foreach ($sites as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-indigo-600 text-white px-3 py-1 rounded text-xs font-bold">Assign</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>