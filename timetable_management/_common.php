<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

global $conn;

$error = '';
$success = '';

// ---- Reference data ----
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$programs = [];
$res = mysqli_query($conn, "SELECT p.program_id, p.program_name, p.department_id FROM programs p WHERE p.status = 'Active' ORDER BY p.program_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $programs[] = $row; } }

$sessions = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; } }

$semesters = [];
$res = mysqli_query($conn, "SELECT semester_id, semester_name, semester_number, department_id FROM semesters ORDER BY semester_id");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $semesters[] = $row; } }

$courses = [];
$res = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours, c.program_id, c.department_id FROM courses c WHERE c.status = 'Active' ORDER BY c.course_code");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $courses[] = $row; } }

$teachers = [];
$res = mysqli_query($conn, "SELECT t.teacher_id, t.teacher_name, t.designation, t.department_id FROM teachers t WHERE t.status = 'Active' ORDER BY t.teacher_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $teachers[] = $row; } }

$rooms = [];
$res = mysqli_query($conn, "SELECT id, room_no, room_type, building, capacity, status FROM rooms WHERE status = 'Active' ORDER BY room_no");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $rooms[] = $row; } }

$section_letters = ['A', 'B', 'C'];
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$time_slots = [
    '08:00' => '08:00 AM', '09:00' => '09:00 AM', '10:00' => '10:00 AM', '11:00' => '11:00 AM',
    '12:00' => '12:00 PM', '13:00' => '01:00 PM', '14:00' => '02:00 PM', '15:00' => '03:00 PM',
    '16:00' => '04:00 PM', '17:00' => '05:00 PM',
];

// ---- Sub-module navigation ----
$ttModules = [
    'generate' => ['title' => 'Generate Timetable', 'file' => 'generate.php'],
    'view'     => ['title' => 'View Timetable',     'file' => 'view.php'],
    'conflicts'=> ['title' => 'Conflict Detection', 'file' => 'conflicts.php'],
    'adjust'   => ['title' => 'Timetable Adjustment','file' => 'adjust.php'],
    'publish'  => ['title' => 'Publish Timetable',  'file' => 'publish.php'],
];
$ttActive = $ttActive ?? 'generate';

// =============================================
// Helpers
// =============================================
function tt_overlaps($start1, $end1, $start2, $end2) {
    return $start1 < $end2 && $end1 > $start2;
}

function tt_time_ago($time) {
    return date('h:i A', strtotime($time));
}

// Fetch all entries with joined data
function tt_entries_query() {
    return "SELECT e.id AS entry_id, e.timetable_id, e.course_id, e.teacher_id, e.room_id,
                   e.day_of_week, e.start_time, e.end_time, e.section AS entry_section, e.status AS entry_status,
                   t.department_id, t.program_id, t.session_id, t.semester_id, t.section AS tt_section, t.status AS tt_status,
                   c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours,
                   tc.teacher_name, tc.designation,
                   r.room_no, r.room_type,
                   p.program_name, d.department_name, ss.session_name, sem.semester_name
            FROM timetable_entries e
            INNER JOIN timetables t ON t.id = e.timetable_id
            INNER JOIN courses c ON c.course_id = e.course_id
            LEFT JOIN teachers tc ON tc.teacher_id = e.teacher_id
            LEFT JOIN rooms r ON r.id = e.room_id
            LEFT JOIN programs p ON p.program_id = t.program_id
            LEFT JOIN departments d ON d.department_id = t.department_id
            LEFT JOIN sessions ss ON ss.session_id = t.session_id
            LEFT JOIN semesters sem ON sem.semester_id = t.semester_id";
}

function tt_all_entries($conn) {
    $out = [];
    $res = mysqli_query($conn, tt_entries_query());
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; } }
    return $out;
}

// Check conflicts for a proposed (or existing) entry.
// Returns array of conflict arrays: ['type' => ..., 'with' => entry_row, 'desc' => ...]
function tt_check_conflicts($conn, $timetable_id, $course_id, $teacher_id, $day, $start, $end, $room_id, $exclude_entry_id = 0) {
    $conflicts = [];
    $exclude_entry_id = (int)$exclude_entry_id;

    $sql = "SELECT e.id AS entry_id, e.course_id, e.teacher_id, e.room_id, e.timetable_id,
                   e.day_of_week, e.start_time, e.end_time,
                   c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name,
                   tc.teacher_name, r.room_no
            FROM timetable_entries e
            JOIN courses c ON c.course_id = e.course_id
            LEFT JOIN teachers tc ON tc.teacher_id = e.teacher_id
            LEFT JOIN rooms r ON r.id = e.room_id
            WHERE e.day_of_week = ? AND e.id <> ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return $conflicts;
    mysqli_stmt_bind_param($stmt, 'si', $day, $exclude_entry_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        if (!tt_overlaps($start, $end, $row['start_time'], $row['end_time'])) continue;

        // Room conflict
        if ($room_id && $row['room_id'] && (int)$row['room_id'] === (int)$room_id) {
            $conflicts[] = [
                'type' => 'Room',
                'with' => $row,
                'desc' => "Room " . ($row['room_no'] ?: $row['room_id']) . " already occupied at this time by " . $row['course_code'] . " (" . $row['course_name'] . ").",
            ];
        }
        // Teacher conflict
        if ($teacher_id && $row['teacher_id'] && (int)$row['teacher_id'] === (int)$teacher_id) {
            $conflicts[] = [
                'type' => 'Teacher',
                'with' => $row,
                'desc' => "Teacher " . ($row['teacher_name'] ?: $row['teacher_id']) . " is already assigned to " . $row['course_code'] . " (" . $row['course_name'] . ") at this time.",
            ];
        }
        // Student/class conflict (same timetable => same section students)
        if ((int)$row['timetable_id'] === (int)$timetable_id && (int)$row['course_id'] !== (int)$course_id) {
            $conflicts[] = [
                'type' => 'Student',
                'with' => $row,
                'desc' => "Student section already has " . $row['course_code'] . " (" . $row['course_name'] . ") scheduled at this time.",
            ];
        }
    }
    mysqli_stmt_close($stmt);
    return $conflicts;
}

function tt_room_available($conn, $room_id, $day, $start, $end, $exclude_entry_id = 0) {
    $res = tt_check_conflicts($conn, 0, 0, 0, $day, $start, $end, $room_id, $exclude_entry_id);
    foreach ($res as $c) { if ($c['type'] === 'Room') return false; }
    return true;
}

function tt_find_timetable($conn, $program_id, $session_id, $semester_id, $section) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM timetables WHERE program_id = ? AND session_id = ? AND semester_id = ? AND section = ? LIMIT 1");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'iiis', $program_id, $session_id, $semester_id, $section);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}
