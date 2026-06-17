<?php
// /includes/calendar.php

function renderCalendar(int $currentMonth, int $currentYear) {
    global $pdo;
    
    // Fetch logs
    $stmt = $pdo->prepare("SELECT DAY(check_in_time) as day, status FROM attendance_logs 
                           WHERE MONTH(check_in_time) = ? AND YEAR(check_in_time) = ?");
    $stmt->execute([$currentMonth, $currentYear]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dayData = [];
    foreach ($logs as $l) { $dayData[(int)$l['day']][] = $l['status']; }

    $daysInMonth = (int)cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    $firstDayOfMonth = (int)date('N', strtotime("$currentYear-$currentMonth-01")) - 1;
    $today = new DateTime('now', new DateTimeZone('Asia/Manila'));

    echo '<div class="grid grid-cols-7 gap-px bg-slate-200 rounded-2xl overflow-hidden border border-slate-200 shadow-sm">';
    
    // Header
    foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d) 
        echo "<div class='bg-slate-50 p-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider'>$d</div>";

    // Padding
    for ($i = 0; $i < $firstDayOfMonth; $i++) echo "<div class='bg-slate-50/50 h-28'></div>";

    // Days
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $isToday = ($day === (int)$today->format('j') && $currentMonth === (int)$today->format('n') && $currentYear === (int)$today->format('Y'));
        $hasLogs = isset($dayData[$day]);
        $isFlagged = $hasLogs && in_array('Flagged', $dayData[$day]);
        
        $bg = $isFlagged ? 'bg-red-50' : ($hasLogs ? 'bg-emerald-50' : 'bg-white');
        $border = $isToday ? 'ring-2 ring-blue-500 ring-inset' : '';

        echo "<div class='p-3 h-28 flex flex-col $bg $border transition hover:bg-slate-50'>";
        echo "<span class='text-sm " . ($isToday ? 'font-black text-blue-600' : 'font-medium text-slate-700') . "'>$day</span>";
        
        if ($hasLogs) {
            echo "<div class='flex items-center mt-auto'>";
            echo "<span class='w-1.5 h-1.5 rounded-full " . ($isFlagged ? 'bg-red-500' : 'bg-emerald-500') . " mr-1'></span>";
            echo "<a href='/admin/daily-view.php?date=$currentYear-$currentMonth-$day' class='text-[10px] font-semibold " . ($isFlagged ? 'text-red-600' : 'text-emerald-600') . " hover:underline'>" . count($dayData[$day]) . " logs</a>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo '</div>';
}