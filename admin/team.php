<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; 
require_once __DIR__ . '/../includes/header.php';

checkRole(['Admin']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sites = $pdo->query("SELECT id, name FROM sites WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.role, u.is_active, s.name as current_site, s.id as current_site_id, ua.employment_type
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
    <div class="w-64 flex-shrink-0 border-r border-slate-200 bg-white hidden md:block"><?php include __DIR__ . '/../includes/sidebar.php'; ?></div>

    <main class="flex-1 flex flex-col min-h-screen">
        <div class="flex-grow p-6 md:p-10">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Team Management</h1>
                        <p class="text-slate-500 text-sm">Manage roles, sites, and employment status.</p>
                    </div>
                    <button onclick="toggleInvite()" class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                        + Invite Member
                    </button>
                </div>
                
                <div class="flex flex-wrap justify-start gap-6">
                    <?php foreach ($team as $member): ?>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm w-full sm:w-[320px] flex flex-col">
                        <div class="mb-4 flex-grow">
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-slate-900 text-sm truncate"><?= e($member['name']) ?></h3>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?= ($member['employment_type'] === 'Part-Time') ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' ?>">
                                    <?= e($member['employment_type'] ?? 'Full-Time') ?>
                                </span>
                            </div>
                            <p class="text-slate-400 text-[10px] truncate"><?= e($member['email']) ?></p>
                        </div>

                        <div class="space-y-3">
                            <form action="/logic/update-role.php" method="POST" class="flex items-center gap-1">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <select name="role" class="bg-slate-50 border border-slate-200 p-1.5 rounded-lg text-[10px] flex-grow">
                                    <option value="Field Worker" <?= $member['role'] == 'Field Worker' ? 'selected' : '' ?>>Worker</option>
                                    <option value="Manager" <?= $member['role'] == 'Manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="Admin" <?= $member['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" class="bg-slate-800 text-white px-2 py-1.5 rounded-lg text-[9px] font-bold uppercase hover:bg-slate-900">Save</button>
                            </form>

                            <?php if ($member['is_active'] == 1 && $member['role'] === 'Field Worker'): ?>
                                <form action="/logic/process-assignment.php" method="POST" class="flex items-center gap-1">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                    <select name="site_id" class="bg-slate-50 border border-slate-200 p-1.5 rounded-lg text-[10px] flex-grow">
                                        <option value="">-- <?= e($member['current_site'] ?? 'Unassigned') ?> --</option>
                                        <?php foreach ($sites as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="text-indigo-600 font-bold text-[9px] uppercase hover:underline">Set</button>
                                </form>
                            <?php else: ?>
                                <div class="text-slate-400 text-[9px] font-bold italic bg-slate-50 p-2 rounded-lg text-center border border-dashed border-slate-200 uppercase tracking-widest">
                                    <?= $member['is_active'] == 0 ? 'Pending' : 'Management' ?>
                                </div>
                            <?php endif; ?>

                            <form action="/logic/delete-user.php" method="POST" onsubmit="return confirm('Deactivate this user?');" class="pt-2 border-t border-slate-100 text-right">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-[9px] font-bold uppercase tracking-wider">Deactivate</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="invite-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm items-center justify-center p-4 z-50">
    <form action="/logic/process-invite.php" method="POST" class="bg-white p-8 rounded-2xl max-w-sm w-full shadow-2xl">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <h2 class="text-lg font-bold mb-4">Invite New Member</h2>
        <div class="space-y-4">
            <input type="text" name="name" required placeholder="Full Name" class="w-full p-3 border border-slate-200 rounded-xl text-sm">
            <input type="email" name="email" required placeholder="Email Address" class="w-full p-3 border border-slate-200 rounded-xl text-sm">
            <select name="role" class="w-full p-3 border border-slate-200 rounded-xl text-sm">
                <option value="Field Worker">Field Worker</option>
                <option value="Manager">Manager</option>
            </select>
            <div class="flex gap-2">
                <button type="button" onclick="toggleInvite()" class="flex-1 py-2 bg-slate-100 rounded-lg text-sm font-bold">Cancel</button>
                <button type="submit" class="flex-1 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold">Send Invite</button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleInvite() {
    const modal = document.getElementById('invite-modal');
    modal.classList.toggle('hidden');
    modal.classList.toggle('flex');
}
</script>