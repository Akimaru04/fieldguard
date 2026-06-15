<?php
// /includes/calendar.php

function renderCalendar($currentMonth, $currentYear) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT DAY(check_in_time) as day, status FROM attendance_logs 
                           WHERE MONTH(check_in_time) = ? AND YEAR(check_in_time) = ?");
    $stmt->execute([$currentMonth, $currentYear]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dayData = [];
    foreach ($logs as $l) { $dayData[$l['day']][] = $l['status']; }

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    $firstDayOfMonth = date('N', strtotime("$currentYear-$currentMonth-01")) - 1;

    // Define Today's Date components based on Philippine Time (Asia/Manila)
    date_default_timezone_set('Asia/Manila');
    $todayDay = (int)date('j');
    $todayMonth = (int)date('m');
    $todayYear = (int)date('Y');

    echo '<div class="grid grid-cols-7 gap-px bg-slate-200 rounded-xl overflow-hidden border border-slate-200">';
    foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d) 
        echo "<div class='bg-slate-50 p-2 text-center text-[10px] font-bold text-slate-400 uppercase'>$d</div>";

    for ($i = 0; $i < $firstDayOfMonth; $i++) echo "<div class='bg-white h-28'></div>";

    for ($day = 1; $day <= $daysInMonth; $day++) {
        // Sync Logic: Check if the loop day matches the exact current date components
        $isToday = ($day === $todayDay && (int)$currentMonth === $todayMonth && (int)$currentYear === $todayYear);
        
        $hasLogs = isset($dayData[$day]);
        $isFlagged = $hasLogs && in_array('Flagged', $dayData[$day]);
        
        $bg = $isFlagged ? 'bg-red-50' : ($hasLogs ? 'bg-emerald-50' : 'bg-white');
        $ring = $isToday ? 'ring-2 ring-blue-500 ring-inset' : '';

        echo "<div class='p-3 h-28 flex flex-col $bg $ring'>";
        echo "<span class='text-sm " . ($isToday ? 'font-black text-blue-600' : 'font-medium text-slate-700') . "'>$day</span>";
        
        if ($hasLogs) {
            echo "<span class='text-[10px] mt-auto font-semibold " . ($isFlagged ? 'text-red-600' : 'text-emerald-600') . "'>" . count($dayData[$day]) . " log(s)</span>";
        }
        echo "</div>";
    }
    echo '</div>';
}