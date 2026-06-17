<?php
// admin/team.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; 
require_once __DIR__ . '/../includes/header.php';

checkRole(['Admin', 'Manager']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sites = $pdo->query("SELECT id, name FROM sites WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.role, u.is_active, s.name as current_site, s.id as current_site_id
        FROM users u 
        LEFT JOIN user_assignments ua ON u.id = ua.user_id AND ua.ended_at IS NULL 
        LEFT JOIN sites s ON ua.site_id = s.id 
        ORDER BY u.is_active ASC, u.name ASC
    ");
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $team = [];
}
?>

<div class="flex min-h-screen bg-slate-50">
    <div class="w-64 flex-shrink-0 border-r border-slate-200 bg-white hidden md:block">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 w-full p-6 md:p-10">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Team Management</h1>
            <a href="/admin/invite.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-sm">+ Invite Member</a>
        </div>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm w-full">
            <table class="w-full table-fixed text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-5 w-3/12 font-bold text-slate-500 text-sm uppercase">Worker</th>
                        <th class="p-5 w-2/12 font-bold text-slate-500 text-sm uppercase">Role</th>
                        <th class="p-5 w-5/12 font-bold text-slate-500 text-sm uppercase">Site Assignment</th>
                        <th class="p-5 w-2/12 font-bold text-slate-500 text-sm uppercase text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($team as $member): ?>
                    <tr>
                        <td class="p-5 truncate">
                            <div class="font-bold text-slate-900"><?= e($member['name']) ?></div>
                            <div class="text-xs text-slate-400"><?= e($member['email']) ?></div>
                        </td>
                        <td class="p-3">
                            <form action="/logic/update-role.php" method="POST" class="flex items-center gap-1">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <select name="role" class="bg-slate-50 border border-slate-200 p-2 rounded-lg text-xs flex-grow">
                                    <option value="Field Worker" <?= $member['role'] == 'Field Worker' ? 'selected' : '' ?>>Worker</option>
                                    <option value="Manager" <?= $member['role'] == 'Manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="Admin" <?= $member['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" class="bg-slate-800 text-white px-3 py-2 rounded-lg text-[10px] font-bold">Save</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <?php if ($member['is_active'] == 1 && $member['role'] === 'Field Worker'): ?>
                                <form action="/logic/process-assignment.php" method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                    <select name="site_id" class="bg-slate-50 border border-slate-200 p-2 rounded-lg text-xs w-full">
                                        <option value="">Current: <?= e($member['current_site'] ?? 'Unassigned') ?></option>
                                        <?php foreach ($sites as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-[10px] font-bold whitespace-nowrap">Reassign</button>
                                </form>
                            <?php else: ?>
                                <span class="text-slate-400 italic text-xs pl-2"><?= $member['is_active'] == 0 ? 'Pending Activation' : 'Not applicable' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 text-right">
                            <form action="/logic/delete-user.php" method="POST" onsubmit="return confirm('Deactivate?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-bold uppercase">Deactivate</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>