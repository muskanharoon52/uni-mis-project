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
$search = trim($_GET['search'] ?? '');

// Departments
$departments = [];
$deptNames = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; $deptNames[(int)$row['department_id']] = $row['department_name']; } }

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

$search_results = [];
$searched_teacher = null;
if ($search !== '') {
    $search_term = mysqli_real_escape_string($conn, $search);
    $search_id = null;
    if (preg_match('/^T-(\d+)$/i', $search, $matches)) {
        $search_id = (int)$matches[1];
    } elseif (preg_match('/^\d+$/', $search)) {
        $search_id = (int)$search;
    }

    $conditions = [];
    if ($search_id !== null) {
        $conditions[] = "t.teacher_id = $search_id";
        $conditions[] = "t.teacher_id_display = 'T-" . str_pad($search_id, 4, '0', STR_PAD_LEFT) . "'";
        $conditions[] = "u.login_id = '" . mysqli_real_escape_string($conn, (string)$search_id) . "'";
    }
    $conditions[] = "u.login_id = '$search_term'";
    $conditions[] = "u.username = '$search_term'";
    $conditions[] = "t.teacher_name LIKE '%$search_term%'";
    $conditions[] = "t.email LIKE '%$search_term%'";
    $conditions[] = "t.phone LIKE '%$search_term%'";

    $search_sql = "SELECT t.*, d.department_name, u.login_id, u.username, u.user_id AS user_id FROM teachers t 
                   LEFT JOIN departments d ON d.department_id = t.department_id 
                   LEFT JOIN users u ON u.user_id = t.user_id 
                   WHERE " . implode(' OR ', $conditions) . " ORDER BY t.teacher_id ASC";
    $res = mysqli_query($conn, $search_sql);
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $search_results[] = $row; } }
    if (count($search_results) === 1) {
        $searched_teacher = $search_results[0];
    }
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

    if ($action === 'reset_password') {
        $new_password = trim($_POST['new_password'] ?? '');
        $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;

        if ($teacher_id <= 0) {
            $error = "Please select a valid teacher to reset the password.";
        } elseif ($new_password === '' || strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            $user_res = mysqli_query($conn, "SELECT user_id FROM teachers WHERE teacher_id = $teacher_id LIMIT 1");
            if ($user_res && mysqli_num_rows($user_res) > 0) {
                $user_row = mysqli_fetch_assoc($user_res);
                $user_id = (int)$user_row['user_id'];
                if ($user_id > 0) {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE user_id = ?");
                    if ($update) {
                        mysqli_stmt_bind_param($update, 'si', $password_hash, $user_id);
                        if (mysqli_stmt_execute($update)) {
                            $success = "Password reset successfully for teacher #$teacher_id.";
                        } else {
                            $error = "Error updating password: " . mysqli_stmt_error($update);
                        }
                        mysqli_stmt_close($update);
                    } else {
                        $error = "Could not prepare password update statement.";
                    }
                } else {
                    $error = "This teacher has no linked login account.";
                }
            } else {
                $error = "Teacher record not found.";
            }
        }
    } elseif ($action === 'assign') {
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
                <span class="badge bg-primary" style="align-self:center;"><?= count($teachers); ?> teacher(s) in <?= $dept_filter > 0 ? htmlspecialchars($deptNames[$dept_filter] ?? $dept_filter) : 'all'; ?></span>
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
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Search Teacher</label>
                    <input type="text" name="search" class="form-control" placeholder="T-0001 or login ID or email" value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <?php if ($dept_filter > 0): ?><input type="hidden" name="dept" value="<?= $dept_filter; ?>"><?php endif; ?>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if ($search !== ''): ?>
            <?php if (!empty($search_results)): ?>
                <div class="panel mt-3">
                    <h5>Search Results for "<?= htmlspecialchars($search); ?>"</h5>
                    <?php if (count($search_results) === 1 && $searched_teacher): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="panel p-3">
                                    <h6>Teacher Details</h6>
                                    <p><strong>Teacher ID:</strong> T-<?= str_pad((int)$searched_teacher['teacher_id'], 4, '0', STR_PAD_LEFT); ?></p>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($searched_teacher['teacher_name']); ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($searched_teacher['email']); ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($searched_teacher['phone'] ?? 'N/A'); ?></p>
                                    <p><strong>Login ID:</strong> <?= htmlspecialchars($searched_teacher['login_id'] ?? 'N/A'); ?></p>
                                    <p><strong>Username:</strong> <?= htmlspecialchars($searched_teacher['username'] ?? 'N/A'); ?></p>
                                    <p><strong>Department:</strong> <?= htmlspecialchars($searched_teacher['department_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Status:</strong> <?= htmlspecialchars($searched_teacher['status']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="panel p-3">
                                    <h6>Reset Password</h6>
                                    <p class="text-muted">Passwords are stored securely and cannot be recovered in plain text. Enter a new password to reset the teacher's login.</p>
                                    <form method="POST" class="row g-3">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="teacher_id" value="<?= (int)$searched_teacher['teacher_id']; ?>">
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold small text-muted">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Enter new password">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-warning w-100"><i class="fas fa-key"></i> Reset Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Teacher ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Login ID</th>
                                        <th>Username</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($search_results as $row): ?>
                                        <tr>
                                            <td>T-<?= str_pad((int)$row['teacher_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= htmlspecialchars($row['teacher_name']); ?></td>
                                            <td><?= htmlspecialchars($row['email']); ?></td>
                                            <td><?= htmlspecialchars($row['login_id'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($row['username'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($row['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3"><i class="fas fa-exclamation-circle"></i> No teacher found for "<?= htmlspecialchars($search); ?>".</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($dept_filter > 0): ?>
        <form method="POST" id="assignForm">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="dept_id" value="<?= $dept_filter; ?>">

        <!-- Teachers of this dept (select ONE via checkbox) -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Teachers of <?= htmlspecialchars($deptNames[$dept_filter] ?? $dept_filter); ?> (select one)</h5>
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
