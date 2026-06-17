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
<body class="h-full m-0 p-0 overflow-hidden bg-slate-50">

<div id="geoModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white p-8 rounded-2xl max-w-sm w-full text-center shadow-xl">
        <h2 id="modalTitle" class="text-xl font-bold text-red-600 mb-4">Warning</h2>
        <p id="modalMessage" class="text-slate-600 mb-6">Message goes here.</p>
        <button onclick="document.getElementById('geoModal').classList.add('hidden'); location.reload();" 
                class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold">Dismiss</button>
    </div>
</div>

<div class="flex h-screen w-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 h-full overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Welcome, <?= e($_SESSION['name']) ?></h1>
            <?php if ($assignedSite): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Current Site</h2>
                    <p class="text-2xl text-slate-900 font-bold mt-1"><?= e($assignedSite['name']) ?></p>
                    <div class="mt-6">
                        <video id="video" autoplay playsinline class="w-full rounded-xl bg-slate-900"></video>
                        <canvas id="canvas" class="hidden"></canvas>
                        <div class="flex gap-2 mt-4">
                            <?php if (!$activeLog): ?>
                                <button id="checkInBtn" onclick="performCheckIn()" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all disabled:opacity-50">Capture & Check In</button>
                            <?php else: ?>
                                <button id="checkOutBtn" onclick="performCheckOut()" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition-all disabled:opacity-50">Capture & Check Out</button>
                            <?php endif; ?>
                            <button onclick="stopCamera()" class="px-6 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300">Off</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 p-6 rounded-2xl text-center"><h2 class="text-amber-800 font-bold">No site assigned</h2></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
const video = document.getElementById('video'), canvas = document.getElementById('canvas');
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
    .then(s => video.srcObject = s).catch(e => console.error(e));

function stopCamera() { if(video.srcObject) video.srcObject.getTracks().forEach(t => t.stop()); }

function showModal(title, message, isError = true) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalTitle').className = isError ? "text-xl font-bold text-red-600 mb-4" : "text-xl font-bold text-blue-600 mb-4";
    document.getElementById('modalMessage').innerText = message;
    document.getElementById('geoModal').classList.remove('hidden');
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

async function performAction(url, btnId, btnText, siteId = null) {
    const btn = document.getElementById(btnId);
    btn.disabled = true; btn.innerText = "Processing...";

    navigator.geolocation.getCurrentPosition(async (pos) => {
        const siteLat = <?= $assignedSite['latitude'] ?? 0 ?>; 
        const siteLon = <?= $assignedSite['longitude'] ?? 0 ?>;
        
        let isOutside = (siteLat !== 0 && calculateDistance(pos.coords.latitude, pos.coords.longitude, siteLat, siteLon) > 50);

        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        canvas.toBlob(async (blob) => {
            const fd = new FormData();
            fd.append('photo', blob, 'capture.jpg');
            fd.append('latitude', pos.coords.latitude); 
            fd.append('longitude', pos.coords.longitude);
            if (siteId) fd.append('site_id', siteId);

            try {
                const res = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                // Show server message in the custom modal instead of alert()
                showModal(isOutside ? 'Notice' : 'Success', data.msg, isOutside);
            } catch (err) { 
                showModal('Error', 'Connection failed.'); 
                btn.disabled = false; btn.innerText = btnText;
            }
        }, 'image/jpeg');
    }, () => { 
        showModal('Error', 'Location access is required to proceed.'); 
        btn.disabled = false; btn.innerText = btnText; 
    }, { enableHighAccuracy: true });
}

function performCheckIn() { performAction('/logic/process-checkin.php', 'checkInBtn', 'Capture & Check In', '<?= $assignedSite['id'] ?? '' ?>'); }
function performCheckOut() { performAction('/logic/process-checkout.php', 'checkOutBtn', 'Capture & Check Out'); }
</script>
</body>
</html>
<?php ob_end_flush(); ?>