<?php
// /shared/audit-trail.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';
checkRole(['Manager']);
?>

<div class="min-h-screen bg-slate-50 flex justify-start">
    <aside class="w-64 hidden md:block bg-white border-r border-slate-200"><?php include __DIR__ . '/../includes/sidebar.php'; ?></aside>

    <main class="flex-1 min-w-0 p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-6">Audit Trail</h1>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="py-4 px-6 text-[10px] uppercase text-slate-400">Proof</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-slate-400">User (Shift)</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-slate-400">Site</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-center">Status</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-center">Reason</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-center">Overridden By</th>
                            <th class="py-4 px-6 text-[10px] uppercase text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php
                        // Fetch all data in one query
                        $stmt = $pdo->query("
                            SELECT al.*, u.email as user_email, s.name as site_name, admin.name as admin_name 
                            FROM attendance_logs al 
                            JOIN users u ON al.user_id = u.id 
                            JOIN sites s ON al.site_id = s.id 
                            LEFT JOIN users admin ON al.overridden_by_id = admin.id
                            ORDER BY al.check_in_time DESC
                        ");
                        
                        while ($log = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-4 px-6">
                                    <?php if (!empty($log['photo_url'])): ?>
                                        <button onclick="openPhoto('<?= e($log['photo_url']) ?>')" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-colors">View</button>
                                    <?php else: ?><span class="text-xs text-slate-300 italic">None</span><?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-sm text-slate-700"><?= e($log['user_email']) ?> <span class="text-[10px] text-slate-400">(<?= e($log['shift_type']) ?>)</span></td>
                                <td class="py-4 px-6 text-sm text-slate-700"><?= e($log['site_name']) ?></td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-2 py-1 text-xs rounded-lg <?= ($log['status'] === 'Flagged') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>"><?= e($log['status']) ?></span>
                                </td>
                                <td class="py-4 px-6 text-sm text-center text-slate-500 italic"><?= e($log['override_reason'] ?: '-') ?></td>
                                <td class="py-4 px-6 text-center text-xs font-medium text-slate-700">
                                    <?= e($log['admin_name'] ?: '—') ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($log['status'] !== 'Overridden'): ?>
                                        <button onclick="openReview(<?= $log['id'] ?>)" class="text-xs font-bold text-blue-600 hover:underline">Override</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="photoModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 z-[60]">
    <button onclick="closePhoto()" class="absolute top-4 right-4 text-white font-bold text-lg">Close</button>
    <img id="modalImage" src="" class="max-h-[80vh] max-w-full rounded-lg shadow-2xl">
</div>

<div id="reviewModal" class="hidden fixed inset-0 bg-slate-900/40 flex items-center justify-center p-4 z-50">
    <form id="overrideForm" class="bg-white p-6 rounded-2xl max-w-sm w-full shadow-xl">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="log_id" id="modal_log_id">
        <h2 class="text-lg font-bold mb-4">Manual Override</h2>
        <textarea name="reason" required class="w-full p-3 border rounded-xl mb-4 text-sm" placeholder="Reason for override..."></textarea>
        <button type="submit" class="w-full py-2 bg-slate-900 text-white rounded-lg font-bold text-xs uppercase">Confirm</button>
    </form>
</div>

<script>
function openReview(id) { document.getElementById('modal_log_id').value = id; document.getElementById('reviewModal').classList.remove('hidden'); }
function openPhoto(url) { document.getElementById('modalImage').src = url; document.getElementById('photoModal').classList.remove('hidden'); }
function closePhoto() { document.getElementById('photoModal').classList.add('hidden'); }
document.getElementById('overrideForm').onsubmit = async (e) => {
    e.preventDefault();
    const res = await fetch('/logic/manager-override.php', { method: 'POST', body: new FormData(e.target) });
    if((await res.json()).status === 'success') location.reload();
};
</script>