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

$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;

// Departments
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

// Sessions
$sessions = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; } }

// Sections for the selected dept
$sections = [];
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
}

// =============================================
// HANDLE POST
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teacher_ids = isset($_POST['teacher_ids']) && is_array($_POST['teacher_ids']) ? array_map('intval', $_POST['teacher_ids']) : [];
    $course_ids = isset($_POST['course_ids']) && is_array($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : [];
    $section = trim($_POST['section'] ?? '');
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $dept_id = (int)($_POST['dept_id'] ?? 0);

    if ($action === 'assign') {
        if (empty($teacher_ids)) {
            $error = "Please select one teacher from the list.";
        } elseif (empty($course_ids)) {
            $error = "Please select at least one course.";
        } elseif (empty($section)) {
            $error = "Please choose the class (section) the teacher will teach.";
        } elseif ($semester_id <= 0) {
            $error = "Please select a semester.";
        } elseif ($session_id <= 0) {
            $error = "Please select a session.";
        } else {
            $count = 0;
            foreach ($teacher_ids as $tid) {
                foreach ($course_ids as $cid) {
                    $chk = mysqli_query($conn, "SELECT id FROM teacher_courses WHERE teacher_id = $tid AND course_id = $cid AND semester_id = $semester_id AND session_id = $session_id AND section = '" . mysqli_real_escape_string($conn, $section) . "'");
                    if ($chk && mysqli_num_rows($chk) > 0) continue;

                    $ins = mysqli_query($conn, "INSERT INTO teacher_courses (teacher_id, course_id, semester_id, session_id, section) VALUES ($tid, $cid, $semester_id, $session_id, '" . mysqli_real_escape_string($conn, $section) . "')");
                    if ($ins) $count++;
                }
            }
            $success = "Assigned $count course-section record(s) to teacher ID(s): " . implode(', ', $teacher_ids) . ".";
        }
    }
}

// =============================================
// TEACHERS OF SELECTED DEPT
// =============================================
$teachers = [];
if ($dept_filter > 0) {
    $stmt = mysqli_prepare($conn, "SELECT t.*, d.department_name FROM teachers t LEFT JOIN departments d ON d.department_id = t.department_id WHERE t.department_id = ? AND t.status = 'Active' ORDER BY t.teacher_id ASC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $dept_filter);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $teachers[] = $row; }
        mysqli_stmt_close($stmt);
    }
}

// =============================================
// COURSES OF SELECTED DEPT
// =============================================
$courses = [];
if ($dept_filter > 0) {
    $cres = mysqli_query($conn, "SELECT c.course_id, c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name, c.credit_hours FROM courses c WHERE c.status = 'Active' AND (c.program_id IN (SELECT program_id FROM programs WHERE department_id = $dept_filter) OR c.program_id IS NULL) ORDER BY c.course_code");
    if ($cres) { while ($row = mysqli_fetch_assoc($cres)) { $courses[] = $row; } }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Management</h2>
            <div class="btn-group">
                <span class="badge bg-primary" style="align-self:center;"><?= count($teachers); ?> teacher(s) in <?= $dept_filter > 0 ? htmlspecialchars($dept_filter) : 'all'; ?></span>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Department Filter -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Select Department</label>
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- Choose Department --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['department_id']; ?>" <?= $dept_filter === (int)$d['department_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Show Teachers</button>
                </div>
            </form>
        </div>

        <?php if ($dept_filter > 0): ?>
        <form method="POST" id="assignForm">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="dept_id" value="<?= $dept_filter; ?>">

        <!-- Teachers of this dept (select ONE via checkbox) -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Teachers of <?= htmlspecialchars($dept_filter); ?> (select one)</h5>
                <small class="text-muted">Tick the checkbox of the teacher to manage</small>
            </div>
            <div class="card-body">
                <?php if (!empty($teachers)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select_all" style="margin-right:5px;" title="Select all"></th>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Major (Department)</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $t): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="teacher_ids[]" value="<?= (int)$t['teacher_id']; ?>" class="teacher-cb">
                                        </td>
                                        <td style="font-weight:600;">T-<?= str_pad((int)$t['teacher_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?= htmlspecialchars($t['teacher_name']); ?></td>
                                        <td><?= htmlspecialchars($t['designation'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($t['department_name'] ?? 'N/A'); ?></span></td>
                                        <td><?= $t['salary'] !== null ? 'Rs ' . number_format((float)$t['salary'], 0) : 'N/A'; ?></td>
                                        <td><span class="status-badge status-active"><?= htmlspecialchars($t['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-tie"></i>
                        <h5>No Teachers in this Department</h5>
                        <p class="text-muted">Add teachers in the Faculty Registry module first.</p>
                        <a href="../faculty_registry/index.php" class="btn btn-primary"><i class="fas fa-plus"></i> Go to Faculty Registry</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign courses + section -->
        <div class="panel mt-3" style="border:1px dashed var(--border);">
            <h5 class="mb-2"><i class="fas fa-book"></i> Assign Courses & Class to Selected Teacher</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Select Courses</label>
                    <select name="course_ids[]" class="form-select" multiple style="min-height:120px;">
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['course_id']; ?>">
                                <?= htmlspecialchars($c['course_code'] . ' - ' . ($c['course_name'] ?: 'Untitled')); ?> (<?= (int)$c['credit_hours']; ?> cr)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple courses</div>
                </div>
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Class (Section) <span class="text-danger">*</span></label>
                            <select name="section" class="form-select">
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= htmlspecialchars($sec); ?>">Section <?= htmlspecialchars($sec); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Semester</label>
                            <select name="semester_id" class="form-select">
                                <option value="0">Select Semester</option>
                                <?php
                                $sem = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters WHERE department_id = $dept_filter ORDER BY semester_number ASC");
                                if ($sem) { while ($srow = mysqli_fetch_assoc($sem)) { ?>
                                    <option value="<?= (int)$srow['semester_id']; ?>"><?= htmlspecialchars($srow['semester_name']); ?></option>
                                <?php } } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="0">Select Session</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?= $s['session_id']; ?>"><?= htmlspecialchars($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100" onclick="return requireSelection()">
                            <i class="fas fa-check-circle"></i> Assign Courses & Section
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select_all');
    if (selectAll) selectAll.addEventListener('change', function() {
        document.querySelectorAll('.teacher-cb').forEach(function(cb) { cb.checked = selectAll.checked; });
    });
    document.querySelectorAll('.teacher-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const total = document.querySelectorAll('.teacher-cb').length;
            const checked = document.querySelectorAll('.teacher-cb:checked').length;
            if (selectAll) selectAll.checked = total > 0 && checked === total;
        });
    });

    window.requireSelection = function() {
        if (document.querySelectorAll('.teacher-cb:checked').length === 0) {
            alert('Please select at least one teacher first.');
            return false;
        }
        const sec = document.querySelector('select[name="section"]');
        if (sec && !sec.value) {
            alert('Please choose the class (section) the teacher will teach.');
            return false;
        }
        return true;
    };
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
