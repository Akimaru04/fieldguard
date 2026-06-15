<?php
// /index.php
session_start();
if (isset($_SESSION['user_id'])) {
    // Send them to their dashboard if they are already logged in
    $redirect = (in_array($_SESSION['role'], ['Admin', 'Manager'])) ? '/shared/dashboard.php' : '/worker/worker-dashboard.php';
    header("Location: " . $redirect);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FieldGuard | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center h-screen">
    <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-200 w-96">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-blue-600">FieldGuard</h1>
        </div>
        
        <form action="/logic/login-process.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" required 
                       class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-sm">
                Sign In
            </button>
        </form>
    </div>
</body>
</html>