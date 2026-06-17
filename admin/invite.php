<?php
// admin/invite.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; // Required for security/sanitization

checkRole(['Admin']);

// Ensure CSRF token is available for the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-slate-50">
    <div class="w-64 flex-shrink-0 hidden md:block">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="flex-1 p-4 md:p-8">
        <div class="max-w-lg mx-auto bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm">
            <h1 class="text-xl font-bold text-slate-800 mb-6">Invite New Member</h1>
            
            <form action="/logic/process-invite.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email Address</label>
                    <input type="email" name="email" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-all shadow-md">
                    Send Invitation
                </button>
            </form>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>