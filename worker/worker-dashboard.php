<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

checkRole(['Field Worker']);

$stmt = $pdo->prepare("SELECT s.id, s.name, s.address, s.latitude, s.longitude FROM sites s JOIN user_assignments sa ON s.id = sa.site_id WHERE sa.user_id = ? AND s.is_active = TRUE LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$assignedSite = $stmt->fetch(PDO::FETCH_ASSOC);

$activeLog = null;
if ($assignedSite) {
    $stmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = ? AND site_id = ? AND check_out_time IS NULL LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $assignedSite['id']]);
    $activeLog = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html class="h-full">
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-50 overflow-x-hidden">

<div id="geoModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white p-6 rounded-2xl max-w-sm w-full text-center shadow-xl">
        <h2 id="modalTitle" class="text-xl font-bold text-red-600 mb-4">Warning</h2>
        <p id="modalMessage" class="text-slate-600 mb-6"></p>
        <button onclick="document.getElementById('geoModal').classList.add('hidden'); location.reload();" 
                class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold">Dismiss</button>
    </div>
</div>

<div class="flex min-h-screen w-full">
    <aside class="hidden md:block w-64 bg-white border-r border-slate-200 flex-shrink-0">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </aside>

    <main class="flex-1 min-w-0 p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-6">Welcome, <?= e($_SESSION['name']) ?></h1>
        <?php if ($assignedSite): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current Site</h2>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= e($assignedSite['name']) ?></p>
                
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        <video id="video" autoplay playsinline class="w-full aspect-video rounded-2xl bg-slate-900 shadow-inner object-cover"></video>
                        <canvas id="canvas" class="hidden"></canvas>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <?php if (!$activeLog): ?>
                                <button id="checkInBtn" onclick="performCheckIn()" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700">Capture & Check In</button>
                            <?php else: ?>
                                <button id="checkOutBtn" onclick="performCheckOut()" class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-bold hover:bg-rose-700">Capture & Check Out</button>
                            <?php endif; ?>
                            <button onclick="stopCamera()" class="bg-slate-100 text-slate-700 py-3 px-6 rounded-xl font-bold hover:bg-slate-200">Off</button>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 h-fit">
                        <h3 class="font-bold text-slate-900 mb-2">Site Details</h3>
                        <p class="text-sm text-slate-600"><?= e($assignedSite['address']) ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-amber-50 border border-amber-200 p-6 rounded-2xl text-center"><h2>No site assigned</h2></div>
        <?php endif; ?>
    </main>
</div>

<script>
const video = document.getElementById('video'), canvas = document.getElementById('canvas');
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
    .then(s => video.srcObject = s).catch(e => console.error("Camera Error:", e));

function stopCamera() { if(video.srcObject) video.srcObject.getTracks().forEach(t => t.stop()); }

function showModal(title, msg, isError) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalTitle').className = isError ? "text-xl font-bold text-red-600 mb-4" : "text-xl font-bold text-blue-600 mb-4";
    document.getElementById('modalMessage').innerText = msg;
    document.getElementById('geoModal').classList.remove('hidden');
}

async function performAction(url, btnId, siteId = null) {
    const btn = document.getElementById(btnId);
    btn.disabled = true; btn.innerText = "Processing...";

    navigator.geolocation.getCurrentPosition(async (pos) => {
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        canvas.toBlob(async (blob) => {
            const fd = new FormData();
            fd.append('photo', blob, 'capture.jpg');
            fd.append('lat', pos.coords.latitude);
            fd.append('long', pos.coords.longitude);
            fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            if (siteId) fd.append('site_id', siteId);

            try {
                const res = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                showModal(data.status === 'success' ? 'Success' : 'Notice', data.message, data.status !== 'success');
            } catch(e) { showModal('Error', 'Server unreachable', true); btn.disabled = false; }
        }, 'image/jpeg');
    }, () => { showModal('Error', 'Location access required', true); btn.disabled = false; }, { enableHighAccuracy: true });
}

function performCheckIn() { performAction('/logic/checkin.php', 'checkInBtn', '<?= $assignedSite['id'] ?? '' ?>'); }
function performCheckOut() { performAction('/logic/checkout.php', 'checkOutBtn'); }
</script>
</body>
</html>