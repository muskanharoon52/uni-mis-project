<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

global $conn;

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) {
            return false;
        }
    }
}

$error = '';
$success = '';

$mode = $_GET['mode'] ?? 'bulk';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$search = trim($_GET['search'] ?? '');

$hasSectionId = columnExists($conn, 'admission_students', 'section_id');

// =============================================
// GET FILTER DATA
// =============================================
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$sessions = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; } }

$semesters = [];
$res = mysqli_query($conn, "SELECT DISTINCT semester FROM student_course_allocation ORDER BY semester");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $semesters[] = $row['semester']; } }

// Sections for the selected dept. Always offer A, B, C, D and merge in any
// extra sections that actually exist in the database for the department.
$sections = ['A', 'B', 'C', 'D'];
if ($dept_filter > 0) {
    $stmt = mysqli_prepare($conn, "SELECT DISTINCT TRIM(REPLACE(s.section_name, 'Section ', '')) AS section_name FROM sections s JOIN programs p ON p.program_id = s.program_id WHERE p.department_id = ? AND s.status = 'Active' ORDER BY section_name");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $dept_filter);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['section_name'])) { $sections[] = $row['section_name']; }
        }
        mysqli_stmt_close($stmt);
    }
    $sections = array_values(array_unique($sections));
    sort($sections);
}

// =============================================
// HANDLE POST (single combined enroll form)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? array_map('intval', $_POST['student_ids']) : [];

    if ($action === 'enroll') {
        $course_ids = isset($_POST['course_ids']) && is_array($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : [];
        $new_section = trim($_POST['new_section'] ?? '');
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $dept_id = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : $dept_filter;
        $allocated_by = (int)($_SESSION['user_id'] ?? 0);

        if (empty($student_ids)) {
            $error = "No students selected.";
        } elseif (empty($course_ids) && empty($new_section)) {
            $error = "Please select at least one course or a section.";
        } else {
            $student_count = 0;
            $course_count = 0;
            $section_count = 0;

            foreach ($student_ids as $aid) {
                $q = mysqli_query($conn, "SELECT application_id, program_id, full_name FROM admission_students WHERE id = $aid");
                $st = $q ? mysqli_fetch_assoc($q) : null;
                if (!$st || empty($st['application_id'])) continue;
                $app_id = (int)$st['application_id'];
                $student_count++;

                if (!empty($course_ids)) {
                    $roll_no = '';
                    $rq = mysqli_query($conn, "SELECT roll_no FROM students WHERE application_id = $app_id LIMIT 1");
                    if ($rq && ($rr = mysqli_fetch_assoc($rq))) { $roll_no = $rr['roll_no']; }

                    foreach ($course_ids as $cid) {
                        $chk = mysqli_query($conn, "SELECT id FROM student_course_allocation WHERE application_id = $app_id AND course_id = $cid");
                        if ($chk && mysqli_num_rows($chk) > 0) continue;

                        $cq = mysqli_query($conn, "SELECT course_code, course_name, course_title, credit_hours FROM courses WHERE course_id = $cid");
                        $c = $cq ? mysqli_fetch_assoc($cq) : null;
                        if (!$c) continue;
                        $c_name = $c['course_name'] ?: $c['course_title'];

                        $ins = mysqli_query($conn, "INSERT INTO student_course_allocation (application_id, course_id, course_code, course_name, credit_hours, semester, allocated_by) VALUES ($app_id, $cid, '" . mysqli_real_escape_string($conn, $c['course_code']) . "', '" . mysqli_real_escape_string($conn, $c_name) . "', " . (int)$c['credit_hours'] . ", 1, $allocated_by)");
                        if ($ins) $course_count++;

                        if (!empty($roll_no)) {
                            $chk2 = mysqli_query($conn, "SELECT id FROM student_courses WHERE student_id = '" . mysqli_real_escape_string($conn, $roll_no) . "' AND course_id = $cid");
                            if (!($chk2 && mysqli_num_rows($chk2) > 0)) {
                                mysqli_query($conn, "INSERT INTO student_courses (student_id, course_id, enrollment_date, status) VALUES ('" . mysqli_real_escape_string($conn, $roll_no) . "', $cid, CURDATE(), 'Active')");
                            }
                        }
                    }
                }

                if (!empty($new_section)) {
                    $section_id = null;
                    if ($st['program_id']) {
                        $sq = mysqli_query($conn, "SELECT section_id FROM sections WHERE TRIM(REPLACE(section_name, 'Section ', '')) = '" . mysqli_real_escape_string($conn, $new_section) . "' AND program_id = " . (int)$st['program_id'] . " AND status = 'Active' LIMIT 1");
                        if ($sq && ($r = mysqli_fetch_assoc($sq))) $section_id = $r['section_id'];
                    }
                    if (!$section_id) {
                        $sq = mysqli_query($conn, "SELECT section_id FROM sections WHERE TRIM(REPLACE(section_name, 'Section ', '')) = '" . mysqli_real_escape_string($conn, $new_section) . "' AND status = 'Active' LIMIT 1");
                        if ($sq && ($r = mysqli_fetch_assoc($sq))) $section_id = $r['section_id'];
                    }

                    if ($section_id) {
                        $upd = mysqli_query($conn, "UPDATE admission_students SET section_id = " . (int)$section_id . " WHERE id = $aid");
                        if ($upd) $section_count++;
                        if (!empty($st['application_id'])) {
                            mysqli_query($conn, "UPDATE students SET section_id = " . (int)$section_id . " WHERE application_id = " . (int)$st['application_id']);
                        }
                    }
                }
            }

            if (!empty($new_section) && $capacity > 0 && $dept_id > 0) {
                $upd = "UPDATE sections s JOIN programs p ON p.program_id = s.program_id SET s.capacity = $capacity WHERE p.department_id = $dept_id AND TRIM(REPLACE(s.section_name, 'Section ', '')) = '" . mysqli_real_escape_string($conn, $new_section) . "' AND s.status = 'Active'";
                if (mysqli_query($conn, $upd)) {
                    $affected = mysqli_affected_rows($conn);
                    $success .= " Capacity for Section '$new_section' set to $capacity (updated $affected record(s)).";
                }
            }

            $msg_parts = [];
            if ($student_count > 0) $msg_parts[] = "$student_count student(s)";
            if ($course_count > 0) $msg_parts[] = "$course_count course allocation(s)";
            if ($section_count > 0) $msg_parts[] = "$section_count section assignment(s)";
            $success = "Enrolled: " . implode(', ', $msg_parts) . "." . $success;
        }
    }

    elseif ($action === 'set_capacity') {
        $section_name = trim($_POST['section_name'] ?? '');
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $dept_id = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : 0;

        if (empty($section_name)) {
            $error = "Please select a section.";
        } elseif ($capacity <= 0) {
            $error = "Capacity must be a positive number.";
        } elseif ($dept_id <= 0) {
            $error = "Please select a department.";
        } else {
            $upd = "UPDATE sections s JOIN programs p ON p.program_id = s.program_id SET s.capacity = $capacity WHERE p.department_id = $dept_id AND TRIM(REPLACE(s.section_name, 'Section ', '')) = '" . mysqli_real_escape_string($conn, $section_name) . "' AND s.status = 'Active'";
            if (mysqli_query($conn, $upd)) {
                $affected = mysqli_affected_rows($conn);
                $success = "Capacity for section '$section_name' set to $capacity (updated $affected section record(s)).";
            } else {
                $error = "Error updating capacity: " . mysqli_error($conn);
            }
        }
    }
}

// =============================================
// INDIVIDUAL MODE: lookup student by ID/name/roll
// =============================================
$individual = null;
$individual_courses = [];
$indDeptId = 0;
if ($mode === 'individual' && $search !== '') {
    $st = mysqli_prepare($conn, "SELECT asd.*, aa.session_id AS app_session_id, sess.session_name AS app_session_name,
                 p.program_name, d.department_name, sec.section_name AS assigned_section,
                 CASE WHEN stu.student_id IS NOT NULL THEN 1 ELSE 0 END AS is_registered,
                 stu.roll_no AS reg_roll_no
            FROM admission_students asd
            LEFT JOIN admission_applications aa ON aa.application_id = asd.application_id
            LEFT JOIN sessions sess ON sess.session_id = aa.session_id
            LEFT JOIN programs p ON p.program_id = asd.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            LEFT JOIN sections sec ON sec.section_id = asd.section_id
            LEFT JOIN students stu ON stu.application_id = asd.application_id
            WHERE (asd.student_id = ? OR asd.student_name LIKE ? OR asd.full_name LIKE ?
                   OR stu.roll_no = ? OR CAST(asd.application_id AS CHAR) = ? OR CAST(asd.id AS CHAR) = ?)
            ORDER BY asd.id DESC
            LIMIT 1");
    if ($st) {
        $like = "%$search%";
        mysqli_stmt_bind_param($st, 'ssssss', $search, $like, $like, $search, $search, $search);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $individual = mysqli_fetch_assoc($res);
        mysqli_stmt_close($st);
    }

    if ($individual) {
        $indDeptId = (int)($individual['department_id'] ?? 0);
        if ($indDeptId <= 0) {
            $dq = mysqli_query($conn, "SELECT department_id FROM programs WHERE program_id = " . (int)$individual['program_id'] . " LIMIT 1");
            if ($dq && ($dr = mysqli_fetch_assoc($dq))) $indDeptId = (int)$dr['department_id'];
        }
        $indActivated = ((int)($individual['fee_paid'] ?? 0) === 1 && ($individual['status'] ?? '') === 'active');

        $app_id = (int)($individual['application_id'] ?? 0);
        if ($app_id > 0) {
            $cst = mysqli_prepare($conn, "SELECT sca.id, sca.course_id, sca.course_code, sca.course_name, sca.credit_hours, sca.semester
                     FROM student_course_allocation sca
                     WHERE sca.application_id = ?
                     ORDER BY sca.semester, sca.course_code");
            if ($cst) {
                mysqli_stmt_bind_param($cst, 'i', $app_id);
                mysqli_stmt_execute($cst);
                $cres = mysqli_stmt_get_result($cst);
                while ($row = mysqli_fetch_assoc($cres)) { $individual_courses[] = $row; }
                mysqli_stmt_close($cst);
            }
        }
    } else {
        $error = "No activated student found for \"" . htmlspecialchars($search) . "\". Try the student ID (e.g. STU-2026-2145 or STD026), roll no, or name.";
    }
}

// =============================================
// BUILD STUDENT LIST (bulk mode)
// =============================================
$sql = "SELECT asd.*, aa.session_id AS app_session_id, p.program_name, d.department_name, sec.section_name AS assigned_section,
            CASE WHEN st.student_id IS NOT NULL THEN 1 ELSE 0 END AS is_registered, st.roll_no AS reg_roll_no,
            (SELECT DISTINCT sca.semester FROM student_course_allocation sca WHERE sca.application_id = asd.application_id ORDER BY sca.semester DESC LIMIT 1) AS semester
        FROM admission_students asd
        LEFT JOIN admission_applications aa ON aa.application_id = asd.application_id
        LEFT JOIN programs p ON p.program_id = asd.program_id
        LEFT JOIN departments d ON d.department_id = p.department_id
        LEFT JOIN sections sec ON sec.section_id = asd.section_id
        LEFT JOIN students st ON st.application_id = asd.application_id
        WHERE asd.fee_paid = 1 AND asd.status = 'active'";

$params = [];
$types = '';

if ($dept_filter > 0) { $sql .= " AND p.department_id = ?"; $params[] = $dept_filter; $types .= 'i'; }
if ($session_filter > 0) { $sql .= " AND aa.session_id = ?"; $params[] = $session_filter; $types .= 'i'; }
if ($semester_filter > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM student_course_allocation sca WHERE sca.application_id = asd.application_id AND sca.semester = ?)";
    $params[] = $semester_filter;
    $types .= 'i';
}

$sql .= " ORDER BY asd.id DESC";

$students = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $students[] = $row; }
    mysqli_stmt_close($stmt);
}

// Courses for assignment (dept-specific; falls back to student's dept in individual mode)
$coursesDept = $dept_filter > 0 ? $dept_filter : $indDeptId;
$courses = [];
if ($coursesDept > 0) {
    $cres = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.status = 'Active' AND (c.program_id IN (SELECT program_id FROM programs WHERE department_id = $coursesDept) OR c.program_id IS NULL) ORDER BY c.course_code");
    if ($cres) { while ($row = mysqli_fetch_assoc($cres)) { $courses[] = $row; } }
} else {
    $cres = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.status = 'Active' ORDER BY c.course_code");
    if ($cres) { while ($row = mysqli_fetch_assoc($cres)) { $courses[] = $row; } }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-graduate"></i> Student Management</h2>
            <div class="btn-group">
                <a href="index.php?mode=individual" class="btn <?= $mode === 'individual' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-user"></i> By ID</a>
                <a href="index.php?mode=bulk" class="btn <?= $mode === 'bulk' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-list"></i> Bulk</a>
                <?php if ($mode === 'bulk'): ?>
                    <span class="badge bg-primary" style="align-self:center;"><?= count($students) ?> activated student(s)</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <?php if ($mode === 'individual'): ?>
            <!-- Individual Student Lookup -->
            <div class="panel">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="mode" value="individual">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" required
                               placeholder="Enter student ID (e.g. STU-2026-2145 or STD026), roll no, or name..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search Student</button>
                        <a href="index.php?mode=individual" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                    </div>
                </form>
            </div>

            <?php if ($individual): ?>
                <!-- Individual Student Detail -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Student Detail</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-block"><label>Student ID</label><span><?= htmlspecialchars($individual['student_id'] ?? 'N/A'); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Full Name</label><span><?= htmlspecialchars($individual['full_name'] ?: $individual['student_name']); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Roll No</label><span><?= htmlspecialchars($individual['reg_roll_no'] ?? 'N/A'); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Program</label><span><?= htmlspecialchars($individual['program_name'] ?? 'N/A'); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Department</label><span><?= htmlspecialchars($individual['department_name'] ?? 'N/A'); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Session</label><span><?= htmlspecialchars($individual['app_session_name'] ?? ($individual['app_session_id'] ? 'Session #' . $individual['app_session_id'] : 'N/A')); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Section</label><span><?= htmlspecialchars($individual['assigned_section'] ?? 'Not assigned'); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-block"><label>Status</label>
                                    <span><span class="status-badge <?= $individual['is_registered'] ? 'status-active' : 'status-pending' ?>"><?= $individual['is_registered'] ? 'Registered' : 'Pending' ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($individual_courses)): ?>
                <div class="card mt-3">
                    <div class="card-header"><h5>Allocated Courses (<?= count($individual_courses); ?>)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Credit Hours</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($individual_courses as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['course_code'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($c['course_name'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($c['credit_hours'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($c['semester'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Enroll this student -->
                <?php if ($indActivated): ?>
                <form method="POST" id="bulkForm">
                    <div class="panel mt-3" style="border:1px dashed var(--border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="m-0"><i class="fas fa-user-plus"></i> Enroll This Student</h5>
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small text-muted">Courses (from SSO Courses)</label>
                                <select name="course_ids[]" class="form-select" multiple style="min-height:90px;">
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['course_id']; ?>">
                                            <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple courses</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small text-muted">Section</label>
                                <select name="new_section" class="form-select">
                                    <option value="">Select Section</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= htmlspecialchars($sec); ?>">Section <?= htmlspecialchars($sec); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small text-muted">Capacity</label>
                                <input type="number" name="capacity" class="form-control" min="1" placeholder="e.g. 30">
                            </div>
                            <div class="col-md-2">
                                <input type="hidden" name="student_ids[]" value="<?= (int)$individual['id']; ?>">
                                <input type="hidden" name="dept_id" value="<?= $indDeptId; ?>">
                                <button type="submit" name="action" value="enroll" class="btn btn-primary w-100" onclick="return requireSelection()">
                                    <i class="fas fa-check-circle"></i> Enroll
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning mt-3"><i class="fas fa-exclamation-circle"></i> This student is not activated yet (fee not paid or status not active). Enrolling is available once the admission fee is marked as paid in the Finance module.</div>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>

            <!-- Filter Section (Bulk) -->
            <div class="panel">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-4">
                        <select name="dept" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['department_id']; ?>" <?= $dept_filter == $d['department_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($d['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="session" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Sessions</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= $s['session_id']; ?>" <?= $session_filter == $s['session_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="semester" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Semesters</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem; ?>" <?= $semester_filter == $sem ? 'selected' : ''; ?>>
                                    Semester <?= htmlspecialchars($sem); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="index.php?mode=bulk" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Single form: student checkboxes + enroll panel below -->
            <form method="POST" id="bulkForm">

            <!-- Students Table -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Activated Admission Students (<?= count($students); ?>)</h5>
                    <label style="font-weight:500;">
                        <input type="checkbox" id="select_all" style="margin-right:5px;"> Select All
                    </label>
                </div>
                <div class="card-body">
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover datatable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select_all_top"></th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Department</th>
                                        <th>Session</th>
                                        <th>Semester</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><input type="checkbox" name="student_ids[]" value="<?= $s['id']; ?>" class="student-cb"></td>
                                            <td style="font-weight:600;"><a href="index.php?mode=individual&search=<?= urlencode($s['student_id'] ?? ''); ?>"><?= htmlspecialchars($s['student_id'] ?? 'N/A'); ?></a></td>
                                            <td>
                                                <?= htmlspecialchars($s['full_name'] ?? 'N/A'); ?>
                                                <?php if (!empty($s['reg_roll_no'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($s['reg_roll_no']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($s['program_name'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($s['department_name'] ?? 'N/A'); ?></td>
                                            <td class="muted"><?= $s['app_session_id'] ? 'Session #' . htmlspecialchars($s['app_session_id']) : 'N/A'; ?></td>
                                            <td><?= $s['semester'] ?? 'N/A'; ?></td>
                                            <td>
                                                <?php if ($s['is_registered']): ?>
                                                    <span class="status-badge status-active">Registered</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h5>No Activated Students Found</h5>
                            <p class="text-muted">Students appear here after their admission fee is marked as paid in the Finance module.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enroll Selected Students -->
            <div class="panel mt-3" style="border:1px dashed var(--border);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="m-0"><i class="fas fa-user-plus"></i> Enroll Selected Students</h5>
                    <span class="text-muted small">Students ticked above will be enrolled below.</span>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">Predefined Courses (from SSO Courses)</label>
                        <select name="course_ids[]" class="form-select" multiple style="min-height:90px;">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id']; ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple courses</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted">Section</label>
                        <select name="new_section" class="form-select">
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= htmlspecialchars($sec); ?>">Section <?= htmlspecialchars($sec); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small text-muted">Capacity</label>
                        <input type="number" name="capacity" class="form-control" min="1" placeholder="e.g. 30">
                    </div>
                    <div class="col-md-2">
                        <input type="hidden" name="dept_id" value="<?= $dept_filter; ?>">
                        <button type="submit" name="action" value="enroll" class="btn btn-primary w-100" onclick="return requireSelection('enroll')">
                            <i class="fas fa-check-circle"></i> Enroll
                        </button>
                    </div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="fas fa-users"></i> Capacity sets the max students allowed in the chosen section. Courses are assigned to all selected students.
                </div>
            </div>

            </form>
        <?php endif; ?>
    </div>

<style>
.detail-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
.detail-block label { display: block; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 2px; }
.detail-block span { font-size: 14px; font-weight: 500; color: #0f172a; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selects = ['#select_all', '#select_all_top'];
    selects.forEach(function(sel) {
        const el = document.querySelector(sel);
        if (el) el.addEventListener('change', function() {
            document.querySelectorAll('.student-cb').forEach(function(cb) {
                cb.checked = el.checked;
            });
        });
    });

    document.querySelectorAll('.student-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const total = document.querySelectorAll('.student-cb').length;
            const checked = document.querySelectorAll('.student-cb:checked').length;
            document.querySelectorAll('#select_all, #select_all_top').forEach(function(el) {
                el.checked = total > 0 && checked === total;
            });
        });
    });

    window.requireSelection = function() {
        if (document.querySelector('input[name="student_ids[]"][type="hidden"]')) return true;
        if (document.querySelectorAll('.student-cb:checked').length === 0) {
            alert('Please select at least one student first.');
            return false;
        }
        return true;
    };

    const bf = document.getElementById('bulkForm');
    if (bf) bf.addEventListener('submit', function(e) {
        if (document.querySelector('input[name="student_ids[]"][type="hidden"]')) return;
        if (document.querySelectorAll('.student-cb:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one student first.');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
