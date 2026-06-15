<?php
// /worker/worker-dashboard.php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

checkRole(['Field Worker']);

$stmt = $pdo->prepare("SELECT s.id, s.name, s.address FROM sites s JOIN user_assignments sa ON s.id = sa.site_id WHERE sa.user_id = ? AND s.is_active = TRUE LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$assignedSite = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="h-full">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full m-0 p-0 overflow-hidden">

<div class="flex h-screen w-screen overflow-hidden bg-slate-50">
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
                            <button id="checkInBtn" onclick="performCheckIn()" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all disabled:opacity-50">
                                Capture & Check In
                            </button>
                            <button onclick="stopCamera()" class="px-6 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300">
                                Off
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 p-6 rounded-2xl text-center">
                    <h2 class="text-amber-800 font-bold">No site assigned</h2>
                </div>
            <?php endif; ?>
        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
        
        <script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');

// Start Camera
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
    .then(stream => { video.srcObject = stream; })
    .catch(err => alert("Camera access required for verification."));

function stopCamera() {
    const stream = video.srcObject;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

async function performCheckIn() {
    const btn = document.getElementById('checkInBtn');
    if (!video.srcObject || video.paused) {
        alert("Please ensure the camera is active before checking in.");
        return;
    }
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    
    const isCanvasEmpty = !canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data.some(channel => channel !== 0);
    if (isCanvasEmpty) {
        alert("Capture failed. Please try again.");
        return;
    }

    btn.disabled = true;
    btn.innerText = "Processing...";

    navigator.geolocation.getCurrentPosition(async (pos) => {
        canvas.toBlob(async (blob) => {
            const fd = new FormData();
            fd.append('photo', blob, 'capture.jpg');
            fd.append('site_id', '<?= $assignedSite['id'] ?? '' ?>');
            fd.append('latitude', pos.coords.latitude);
            fd.append('longitude', pos.coords.longitude);

            try {
                const res = await fetch('/logic/process-checkin.php', { method: 'POST', body: fd });
                const data = await res.json();
                alert(data.msg);
                if(data.status === 'success') {
                    stopCamera();
                    window.location.reload();
                }
            } catch (err) {
                alert("Connection error.");
            } finally {
                btn.disabled = false;
                btn.innerText = "Capture & Check In";
            }
        }, 'image/jpeg');
    }, () => {
        alert("Location access is required for verification.");
        btn.disabled = false;
        btn.innerText = "Capture & Check In";
    }, { enableHighAccuracy: true });
}
</script>
    </main>
</div>

</body>
</html>
<?php ob_end_flush(); ?>