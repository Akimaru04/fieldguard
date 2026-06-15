<?php
// /includes/calendar.php

if (!function_exists('renderCalendar')) {
    function renderCalendar($currentMonth, $currentYear) {
        global $pdo;
        
        // Fetch logs for the specific month/year
        $stmt = $pdo->prepare("SELECT DAY(check_in_time) as day, status FROM attendance_logs 
                               WHERE MONTH(check_in_time) = ? AND YEAR(check_in_time) = ?");
        $stmt->execute([$currentMonth, $currentYear]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dayData = [];
        foreach ($logs as $l) {
            $dayData[$l['day']][] = $l['status'];
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
        $firstDayOfMonth = date('N', strtotime("$currentYear-$currentMonth-01")) - 1;

        echo '<div class="grid grid-cols-7 gap-px bg-slate-200 rounded-xl overflow-hidden border border-slate-200">';
        foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d) 
            echo "<div class='bg-slate-50 p-2 text-center text-[10px] font-bold text-slate-400 uppercase'>$d</div>";

        for ($i = 0; $i < $firstDayOfMonth; $i++) echo "<div class='bg-white h-28'></div>";

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $isToday = ($day == date('j') && $currentMonth == date('m') && $currentYear == date('Y'));
            $hasLogs = isset($dayData[$day]);
            $isFlagged = $hasLogs && in_array('Flagged', $dayData[$day]);
            
            // CSS classes
            $bg = $isFlagged ? 'bg-red-50' : ($hasLogs ? 'bg-emerald-50' : 'bg-white');
            $border = $isToday ? 'ring-2 ring-blue-500 ring-inset' : '';

            echo "<div class='p-3 h-28 flex flex-col $bg $border'>";
            echo "<span class='text-sm font-medium " . ($isToday ? 'text-blue-600 font-bold' : 'text-slate-700') . "'>$day</span>";
            if ($hasLogs) {
                echo "<span class='text-[10px] mt-auto font-semibold " . ($isFlagged ? 'text-red-600' : 'text-emerald-600') . "'>" . count($dayData[$day]) . " log(s)</span>";
            }
            echo "</div>";
        }
        echo '</div>';
    }
}