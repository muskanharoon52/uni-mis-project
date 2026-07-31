<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

global $conn;

$error = '';
$success = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'bulk';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;

// ---- Reference data ----
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$sessions = [];
$session_names = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; $session_names[$row['session_id']] = $row['session_name']; } }

$programs = [];
$res = mysqli_query($conn, "SELECT p.program_id, p.program_name, p.department_id, d.department_name FROM programs p LEFT JOIN departments d ON d.department_id = p.department_id WHERE p.status = 'Active' ORDER BY p.program_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $programs[] = $row; } }

$courses = [];
$cres = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.status = 'Active' ORDER BY c.course_code");
if ($cres) { while ($row = mysqli_fetch_assoc($cres)) { $courses[] = $row; } }

$section_letters = ['A', 'B', 'C'];

// ---- Sub-module navigation ----
$acrModules = [
    'section_change'   => ['title' => 'Section Change Request',      'file' => 'section_change.php'],
    'department'       => ['title' => 'Department Transfer Request', 'file' => 'department_transfer.php'],
    'program'          => ['title' => 'Program Change Request',      'file' => 'program_change.php'],
    'course_add_drop'  => ['title' => 'Course Add/Drop Request',     'file' => 'course_add_drop.php'],
    'course_withdrawal'=> ['title' => 'Course Withdrawal Request',   'file' => 'course_withdrawal.php'],
    'request_status'   => ['title' => 'Request Status',              'file' => 'request_status.php'],
];
$acrActive = $acrActive ?? 'section_change';

// =============================================
// Helpers
// =============================================
function acr_bulk_students($dept, $session) {
    global $conn;
    $sql = "SELECT asd.id, asd.student_id AS adm_student_id, asd.application_id, asd.full_name,
                   p.program_name, d.department_name, sec.section_name,
                   aa.session_id AS app_session_id,
                   CASE WHEN st.student_id IS NOT NULL THEN 1 ELSE 0 END AS is_registered,
                   st.roll_no
            FROM admission_students asd
            LEFT JOIN admission_applications aa ON aa.application_id = asd.application_id
            LEFT JOIN programs p ON p.program_id = asd.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            LEFT JOIN sections sec ON sec.section_id = asd.section_id
            LEFT JOIN students st ON st.application_id = asd.application_id
            WHERE asd.fee_paid = 1 AND asd.status = 'active'";
    $params = [];
    $types = '';
    if ($dept > 0) { $sql .= " AND p.department_id = ?"; $params[] = $dept; $types .= 'i'; }
    if ($session > 0) { $sql .= " AND aa.session_id = ?"; $params[] = $session; $types .= 'i'; }
    $sql .= " ORDER BY d.department_name, p.program_name, asd.full_name";

    $out = [];
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
        mysqli_stmt_close($stmt);
    }
    return $out;
}

function acr_find_individual($search) {
    global $conn;
    $sql = "SELECT asd.id, asd.student_id AS adm_student_id, asd.application_id, asd.full_name,
                   asd.program_id, asd.section_id,
                   p.program_name, d.department_name, sec.section_name,
                   aa.session_id AS app_session_id,
                   st.student_id AS sso_student_id, st.roll_no, st.status AS sso_status
            FROM admission_students asd
            LEFT JOIN admission_applications aa ON aa.application_id = asd.application_id
            LEFT JOIN programs p ON p.program_id = asd.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            LEFT JOIN sections sec ON sec.section_id = asd.section_id
            LEFT JOIN students st ON st.application_id = asd.application_id
            WHERE asd.student_id = ? OR st.roll_no = ? OR CAST(asd.application_id AS CHAR) = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'sss', $search, $search, $search);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function acr_student_row($id) {
    global $conn;
    $id = (int)$id;
    $q = mysqli_query($conn, "SELECT asd.id, asd.application_id, asd.program_id, asd.section_id, asd.department_id,
                                     asd.student_id AS adm_student_id, asd.full_name,
                                     st.student_id AS sso_student_id, st.roll_no,
                                     sec.section_name, p.program_name, d.department_name
                              FROM admission_students asd
                              LEFT JOIN students st ON st.application_id = asd.application_id
                              LEFT JOIN sections sec ON sec.section_id = asd.section_id
                              LEFT JOIN programs p ON p.program_id = asd.program_id
                              LEFT JOIN departments d ON d.department_id = asd.department_id
                              WHERE asd.id = $id AND asd.fee_paid = 1");
    return $q ? mysqli_fetch_assoc($q) : null;
}

function acr_section_id_for($program_id, $letter) {
    global $conn;
    $letter = mysqli_real_escape_string($conn, strtoupper($letter));
    $q = mysqli_query($conn, "SELECT section_id FROM sections WHERE TRIM(REPLACE(section_name, 'Section ', '')) = '$letter' AND program_id = " . (int)$program_id . " AND status = 'Active' LIMIT 1");
    if ($q && ($r = mysqli_fetch_assoc($q))) return (int)$r['section_id'];
    $q = mysqli_query($conn, "SELECT section_id FROM sections WHERE TRIM(REPLACE(section_name, 'Section ', '')) = '$letter' AND status = 'Active' LIMIT 1");
    if ($q && ($r = mysqli_fetch_assoc($q))) return (int)$r['section_id'];
    return 0;
}

function acr_log($type, $student, $old, $new, $status = 'Applied') {
    global $conn;
    $by = (int)($_SESSION['user_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "INSERT INTO acr_requests (request_type, student_ref, application_id, student_name, old_value, new_value, status, requested_by, requested_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) return;
    $ref = $student['adm_student_id'];
    $app = (int)$student['application_id'];
    $name = $student['full_name'];
    mysqli_stmt_bind_param($stmt, 'ssissssi', $type, $ref, $app, $name, $old, $new, $status, $by);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function acr_course_codes($app_id) {
    global $conn;
    $codes = [];
    $res = mysqli_query($conn, "SELECT course_code FROM student_course_allocation WHERE application_id = " . (int)$app_id . " ORDER BY course_code");
    if ($res) { while ($r = mysqli_fetch_assoc($res)) { $codes[] = $r['course_code']; } }
    return implode(', ', $codes);
}

function acr_apply_section($id, $new_section) {
    $st = acr_student_row($id);
    if (!$st) return ['ok' => false, 'msg' => 'Student not found'];
    $section_id = acr_section_id_for((int)$st['program_id'], $new_section);
    if (!$section_id) return ['ok' => false, 'msg' => 'No active section found for that program'];
    $old = $st['section_name'] ? 'Section ' . $st['section_name'] : 'None';
    $new = 'Section ' . strtoupper($new_section);
    mysqli_query($GLOBALS['conn'], "UPDATE admission_students SET section_id = $section_id WHERE id = " . (int)$id);
    if (!empty($st['sso_student_id'])) {
        mysqli_query($GLOBALS['conn'], "UPDATE students SET section_id = $section_id WHERE application_id = " . (int)$st['application_id']);
    }
    acr_log('Section Change', $st, $old, $new);
    return ['ok' => true, 'msg' => "$old -> $new"];
}

function acr_apply_department($id, $dept_id) {
    $st = acr_student_row($id);
    if (!$st) return ['ok' => false, 'msg' => 'Student not found'];
    $dept_id = (int)$dept_id;
    $dq = mysqli_query($GLOBALS['conn'], "SELECT program_id FROM programs WHERE department_id = $dept_id AND status = 'Active' ORDER BY program_id ASC LIMIT 1");
    $drow = $dq ? mysqli_fetch_assoc($dq) : null;
    if (!$drow) return ['ok' => false, 'msg' => 'No active program found in that department'];
    $target_program = (int)$drow['program_id'];
    $ndq = mysqli_query($GLOBALS['conn'], "SELECT department_name FROM departments WHERE department_id = $dept_id");
    $dept_name = ($ndq && ($r = mysqli_fetch_assoc($ndq))) ? $r['department_name'] : 'Department';
    $old = $st['department_name'] ?: 'None';
    $new = $dept_name;
    mysqli_query($GLOBALS['conn'], "UPDATE admission_students SET program_id = $target_program, department_id = $dept_id WHERE id = " . (int)$id);
    if (!empty($st['sso_student_id'])) {
        mysqli_query($GLOBALS['conn'], "UPDATE students SET program_id = $target_program WHERE application_id = " . (int)$st['application_id']);
    }
    acr_log('Department Transfer', $st, $old, $new);
    return ['ok' => true, 'msg' => "$old -> $new"];
}

function acr_apply_program($id, $program_id) {
    $st = acr_student_row($id);
    if (!$st) return ['ok' => false, 'msg' => 'Student not found'];
    $program_id = (int)$program_id;
    if ($program_id === (int)$st['program_id']) return ['ok' => true, 'msg' => 'Already on this program'];
    $pq = mysqli_query($GLOBALS['conn'], "SELECT program_name, department_id FROM programs WHERE program_id = $program_id AND status = 'Active' LIMIT 1");
    $prow = $pq ? mysqli_fetch_assoc($pq) : null;
    if (!$prow) return ['ok' => false, 'msg' => 'Program not found'];
    $old = $st['program_name'] ?: 'None';
    $new = $prow['program_name'];
    $dept_id = (int)$prow['department_id'];
    mysqli_query($GLOBALS['conn'], "UPDATE admission_students SET program_id = $program_id, department_id = $dept_id WHERE id = " . (int)$id);
    if (!empty($st['sso_student_id'])) {
        mysqli_query($GLOBALS['conn'], "UPDATE students SET program_id = $program_id WHERE application_id = " . (int)$st['application_id']);
    }
    acr_log('Program Change', $st, $old, $new);
    return ['ok' => true, 'msg' => "$old -> $new"];
}

function acr_apply_courses($id, $add, $remove, $withdrawal_only = false) {
    $st = acr_student_row($id);
    if (!$st) return ['ok' => false, 'msg' => 'Student not found'];
    $conn = $GLOBALS['conn'];
    $app_id = (int)$st['application_id'];
    $roll = $st['roll_no'] ?? '';
    $allocated_by = (int)($_SESSION['user_id'] ?? 0);
    $old = acr_course_codes($app_id);
    $parts = [];

    if (!$withdrawal_only) {
        foreach ($add as $cid) {
            $cid = (int)$cid;
            $chk = mysqli_query($conn, "SELECT id FROM student_course_allocation WHERE application_id = $app_id AND course_id = $cid");
            if ($chk && mysqli_num_rows($chk) > 0) continue;
            $cq = mysqli_query($conn, "SELECT course_code, course_name, course_title, credit_hours FROM courses WHERE course_id = $cid");
            $c = $cq ? mysqli_fetch_assoc($cq) : null;
            if (!$c) continue;
            $c_name = $c['course_name'] ?: $c['course_title'];
            mysqli_query($conn, "INSERT INTO student_course_allocation (application_id, course_id, course_code, course_name, credit_hours, semester, allocated_by) VALUES ($app_id, $cid, '" . mysqli_real_escape_string($conn, $c['course_code']) . "', '" . mysqli_real_escape_string($conn, $c_name) . "', " . (int)$c['credit_hours'] . ", 1, $allocated_by)");
            if (!empty($roll)) {
                $chk2 = mysqli_query($conn, "SELECT id FROM student_courses WHERE student_id = '" . mysqli_real_escape_string($conn, $roll) . "' AND course_id = $cid");
                if (!($chk2 && mysqli_num_rows($chk2) > 0)) {
                    mysqli_query($conn, "INSERT INTO student_courses (student_id, course_id, enrollment_date, status) VALUES ('" . mysqli_real_escape_string($conn, $roll) . "', $cid, CURDATE(), 'Active')");
                }
            }
            $parts[] = 'Add ' . $c['course_code'];
        }
    }

    foreach ($remove as $cid) {
        $cid = (int)$cid;
        $cq = mysqli_query($conn, "SELECT course_code FROM courses WHERE course_id = $cid");
        $ccode = ($cq && ($r = mysqli_fetch_assoc($cq))) ? $r['course_code'] : 'ID ' . $cid;
        mysqli_query($conn, "DELETE FROM student_course_allocation WHERE application_id = $app_id AND course_id = $cid");
        if (!empty($roll)) {
            mysqli_query($conn, "DELETE FROM student_courses WHERE student_id = '" . mysqli_real_escape_string($conn, $roll) . "' AND course_id = $cid");
        }
        $parts[] = 'Drop ' . $ccode;
    }

    if (empty($parts)) return ['ok' => true, 'msg' => 'No changes required'];

    $new = acr_course_codes($app_id);
    acr_log($withdrawal_only ? 'Course Withdrawal' : 'Course Add/Drop', $st, $old ?: 'None', $new ?: 'None');
    return ['ok' => true, 'msg' => implode(', ', $parts)];
}

// =============================================
// Resolve selected students (bulk / individual)
// =============================================
$bulk_students = [];
if ($mode === 'bulk' && ($dept_filter > 0 || $session_filter > 0)) {
    $bulk_students = acr_bulk_students($dept_filter, $session_filter);
}

$individual = null;
if ($mode === 'individual' && !empty($search)) {
    $individual = acr_find_individual($search);
    if (!$individual) {
        $error = "No student found for the given ID. Try a student ID, roll number, or application ID.";
    }
}
