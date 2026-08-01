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
$res = mysqli_query($conn, "SELECT p.program_id, p.program_name, p.department_id, d.department_name FROM programs p LEFT JOIN departments d ON d.department_id = p.department_id WHERE p.status = 'Active' ORDER BY p.program_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $programs[] = $row; } }

$sessions = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; } }

$semesters = [];
$res = mysqli_query($conn, "SELECT semester_id, semester_name, semester_number FROM semesters ORDER BY semester_id");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $semesters[] = $row; } }

$courses = [];
$res = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.status = 'Active' ORDER BY c.course_code");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $courses[] = $row; } }

$section_letters = ['A', 'B', 'C'];

// ---- Sub-module navigation ----
$ssrModules = [
    'submit'  => ['title' => 'Submit Request',    'file' => 'submit.php'],
    'review'  => ['title' => 'Review Requests',   'file' => 'review.php'],
    'history' => ['title' => 'Request History',   'file' => 'history.php'],
];
$ssrActive = $ssrActive ?? 'submit';

// =============================================
// Helpers
// =============================================
function ssr_section_letter($student) {
    if (empty($student['section_name'])) return 'A';
    return strtoupper(trim(str_replace('Section ', '', $student['section_name'])));
}

function ssr_student_row($student_id) {
    global $conn;
    $student_id = (int)$student_id;
    $q = mysqli_query($conn, "SELECT st.student_id, st.application_id, st.roll_no, st.full_name,
                                     st.program_id, st.current_session_id, st.current_semester_id, st.section_id,
                                     p.department_id, sec.section_name, p.program_name, d.department_name,
                                     ss.session_name, sem.semester_name
                              FROM students st
                              LEFT JOIN sections sec ON sec.section_id = st.section_id
                              LEFT JOIN programs p ON p.program_id = st.program_id
                              LEFT JOIN departments d ON d.department_id = p.department_id
                              LEFT JOIN sessions ss ON ss.session_id = st.current_session_id
                              LEFT JOIN semesters sem ON sem.semester_id = st.current_semester_id
                              WHERE st.student_id = $student_id LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function ssr_find_student($search) {
    global $conn;
    $sql = "SELECT st.student_id, st.application_id, st.roll_no, st.full_name,
                   st.program_id, st.current_session_id, st.current_semester_id, st.section_id,
                   sec.section_name, p.program_name, d.department_name, sem.semester_name
            FROM students st
            LEFT JOIN sections sec ON sec.section_id = st.section_id
            LEFT JOIN programs p ON p.program_id = st.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            LEFT JOIN semesters sem ON sem.semester_id = st.current_semester_id
            WHERE st.roll_no = ? OR CAST(st.student_id AS CHAR) = ? OR CAST(st.application_id AS CHAR) = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'sss', $search, $search, $search);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// Fetch a student's current timetable entries (matching program/session/semester/section)
function ssr_student_timetable($student) {
    global $conn;
    $program_id = (int)($student['program_id'] ?? 0);
    $session_id = (int)($student['current_session_id'] ?? 0);
    $semester_id = (int)($student['current_semester_id'] ?? 0);
    $section = ssr_section_letter($student);
    if (!$program_id || !$session_id || !$semester_id) return [];

    $sql = "SELECT e.id AS entry_id, e.course_id, e.teacher_id, e.room_id, e.day_of_week, e.start_time, e.end_time,
                   e.section AS entry_section,
                   c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours,
                   tc.teacher_name, r.room_no, r.room_type
            FROM timetable_entries e
            INNER JOIN timetables t ON t.id = e.timetable_id
            INNER JOIN courses c ON c.course_id = e.course_id
            LEFT JOIN teachers tc ON tc.teacher_id = e.teacher_id
            LEFT JOIN rooms r ON r.id = e.room_id
            WHERE t.program_id = $program_id AND t.session_id = $session_id AND t.semester_id = $semester_id
              AND t.status = 'Published' AND t.section = '" . mysqli_real_escape_string($conn, $section) . "'
            ORDER BY FIELD(e.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), e.start_time";
    $out = [];
    $res = mysqli_query($conn, $sql);
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; } }
    return $out;
}

function ssr_request_row($id) {
    global $conn;
    $id = (int)$id;
    $q = mysqli_query($conn, "SELECT r.*, p.program_name, d.department_name, ss.session_name, sem.semester_name,
                                     c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name,
                                     rb.full_name AS reviewer_name
                              FROM student_schedule_requests r
                              LEFT JOIN programs p ON p.program_id = r.program_id
                              LEFT JOIN departments d ON d.department_id = r.department_id
                              LEFT JOIN sessions ss ON ss.session_id = r.session_id
                              LEFT JOIN semesters sem ON sem.semester_id = r.semester_id
                              LEFT JOIN courses c ON c.course_id = r.course_id
                              LEFT JOIN students rb ON rb.student_id = r.reviewed_by
                              WHERE r.id = $id LIMIT 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function ssr_log_request($student, $course_id, $conflict_type, $description, $solution, $current_tt) {
    global $conn;
    $by = (int)($_SESSION['user_id'] ?? 0);
    $student_id = (int)($student['student_id'] ?? 0);
    $admission_id = isset($student['application_id']) ? (int)$student['application_id'] : null;
    $roll = $student['roll_no'] ?? '';
    $name = $student['full_name'] ?? '';
    $program = (int)($student['program_id'] ?? 0);
    $session = (int)($student['current_session_id'] ?? 0);
    $semester = (int)($student['current_semester_id'] ?? 0);
    $course_id = (int)$course_id;
    $stmt = mysqli_prepare($conn, "INSERT INTO student_schedule_requests
        (student_id, admission_student_id, roll_no, student_name, department_id, program_id, session_id, semester_id, course_id,
         conflict_type, current_timetable, conflict_description, requested_solution, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    if (!$stmt) return false;
    $dept = (int)($student['department_id'] ?? 0);
    mysqli_stmt_bind_param($stmt, 'isssiiiiissss', $student_id, $admission_id, $roll, $name, $dept, $program, $session, $semester, $course_id, $conflict_type, $current_tt, $description, $solution);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_affected_rows($conn) > 0;
    mysqli_stmt_close($stmt);
    if ($ok) {
        log_activity('Student Schedule Requests', 'Request Submitted', 'student_schedule_requests', null, "$name ($roll): $conflict_type on course #$course_id");
    }
    return $ok;
}
