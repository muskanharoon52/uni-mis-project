<?php
/**
 * Shared activity/audit logger for the SSO module.
 * Writes every user action into the activity_logs table.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('log_activity')) {
    function log_activity($module, $action, $reference_table = null, $reference_id = null, $details = null) {
        $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        global $conn;
        if (empty($conn)) return;
        $reference_id = $reference_id === null ? null : (int)$reference_id;
        $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) return;
        mysqli_stmt_bind_param($stmt, 'sssiss', $module, $action, $reference_table, $reference_id, $user_id, $details);
        @mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('activity_module_for_page')) {
    function activity_module_for_page($currentFolder, $currentPage) {
        $map = [
            'academic_change_requests'    => 'Academic Change Requests',
            'timetable_management'        => 'Timetable Management',
            'student_schedule_requests'   => 'Student Schedule Requests',
            'reports'                     => 'Reports',
            'students'                    => 'Students',
            'student_inquiry'             => 'Student Inquiry',
            'faculty_registry'            => 'Faculty Registry',
            'faculty_management'          => 'Faculty Management',
            'faculty_enquiry'             => 'Faculty Enquiry',
        ];
        if ($currentPage === 'dashboard.php') return 'Dashboard';
        if ($currentPage === 'lms_applications.php') return 'Application';
        return $map[$currentFolder] ?? ($currentFolder !== '' ? ucwords(str_replace('_', ' ', $currentFolder)) : 'Dashboard');
    }
}

if (!function_exists('activity_sanitize_post')) {
    function activity_sanitize_post($post) {
        if (!is_array($post) || empty($post)) return '';
        $parts = [];
        foreach ($post as $k => $v) {
            if (stripos($k, 'password') !== false || stripos($k, 'pass') !== false) continue;
            if (is_array($v)) {
                $flat = [];
                foreach ($v as $item) { $flat[] = (string)$item; }
                $v = implode(', ', array_slice($flat, 0, 8));
                if (count($flat) > 8) $v .= '…';
            } else {
                $v = (string)$v;
            }
            if (strlen($v) > 120) $v = substr($v, 0, 120) . '…';
            $parts[] = $k . '=' . $v;
        }
        $joined = implode(' | ', $parts);
        if (strlen($joined) > 1900) $joined = substr($joined, 0, 1900) . '…';
        return $joined;
    }
}
