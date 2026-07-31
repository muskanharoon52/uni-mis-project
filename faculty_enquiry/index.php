<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

global $conn;

$error = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'individual';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;

// Departments
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

// Sessions
$sessions = [];
$session_names = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; $session_names[$row['session_id']] = $row['session_name']; } }

$individual = null;
$individual_courses = [];
$bulk_faculty = [];

// =============================================
// INDIVIDUAL MODE: lookup faculty by ID
// =============================================
if ($mode === 'individual' && !empty($search)) {
    $searchTerm = $search;
    // Accept formats: "T-0001", "0001", "1", or legacy "F1"/"1"
    $numericSearch = preg_replace('/\D+/', '', $searchTerm);
    $idLike = ($numericSearch !== '') ? (string)(int)$numericSearch : '0';

    $sql = "SELECT t.teacher_id, t.teacher_name, t.designation, t.salary, t.email, t.phone,
                   t.department_id, t.status AS teacher_status, t.user_id,
                   d.department_name,
                   f.faculty_id, f.faculty_name AS legacy_faculty_name,
                   u.username, u.login_id, u.email AS user_email, u.status AS user_status
            FROM teachers t
            LEFT JOIN departments d ON d.department_id = t.department_id
            LEFT JOIN faculty f ON f.teacher_id = t.teacher_id
            LEFT JOIN users u ON u.user_id = t.user_id
            WHERE CAST(t.teacher_id AS CHAR) = ? OR CAST(t.teacher_id AS CHAR) = ?
                  OR LPAD(t.teacher_id, 4, '0') = ?
                  OR t.teacher_name LIKE ? OR t.email LIKE ?
            ORDER BY t.teacher_id ASC
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        $like = "%$searchTerm%";
        mysqli_stmt_bind_param($stmt, 'sssss', $searchTerm, $idLike, $searchTerm, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $individual = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if ($individual) {
        // Assigned courses for this teacher
        $course_sql = "SELECT tc.id, tc.course_id, c.course_code,
                              COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name,
                              c.credit_hours,
                              tc.semester_id, s.semester_name, tc.session_id, tc.section AS section_letter
                       FROM teacher_courses tc
                       LEFT JOIN courses c ON c.course_id = tc.course_id
                       LEFT JOIN semesters s ON s.semester_id = tc.semester_id
                       WHERE tc.teacher_id = ?
                       ORDER BY tc.session_id, tc.semester_id, c.course_code";
        $cstmt = mysqli_prepare($conn, $course_sql);
        if ($cstmt) {
            mysqli_stmt_bind_param($cstmt, 'i', $individual['teacher_id']);
            mysqli_stmt_execute($cstmt);
            $cres = mysqli_stmt_get_result($cstmt);
            while ($row = mysqli_fetch_assoc($cres)) { $individual_courses[] = $row; }
            mysqli_stmt_close($cstmt);
        }
    } else {
        $error = "No faculty found for the given ID. Try a faculty ID (e.g. T-0001), email, or name.";
    }
}

// =============================================
// BULK MODE: list faculty by department
// =============================================
if ($mode === 'bulk') {
    $sql = "SELECT t.teacher_id, t.teacher_name, t.designation, t.salary, t.email, t.phone,
                   t.department_id, t.status AS teacher_status,
                   d.department_name,
                   (SELECT COUNT(*) FROM teacher_courses tc WHERE tc.teacher_id = t.teacher_id) AS course_count
            FROM teachers t
            LEFT JOIN departments d ON d.department_id = t.department_id
            WHERE 1=1";
    $params = [];
    $types = '';
    if ($dept_filter > 0) { $sql .= " AND t.department_id = ?"; $params[] = $dept_filter; $types .= 'i'; }
    if (!empty($search)) {
        $sql .= " AND (t.teacher_name LIKE ? OR t.email LIKE ? OR CAST(t.teacher_id AS CHAR) LIKE ?)";
        $like = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= 'sss';
    }
    $sql .= " ORDER BY t.department_id, t.teacher_id ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $bulk_faculty[] = $row; }
        mysqli_stmt_close($stmt);
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-tie"></i> Faculty Enquiry</h2>
            <div class="btn-group">
                <a href="index.php?mode=individual" class="btn <?= $mode === 'individual' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-user"></i> By ID</a>
                <a href="index.php?mode=bulk" class="btn <?= $mode === 'bulk' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-list"></i> Bulk</a>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Search / Filters Panel -->
        <div class="panel">
            <form method="GET" class="row g-3">
                <input type="hidden" name="mode" value="<?= htmlspecialchars($mode); ?>">

                <?php if ($mode === 'bulk'): ?>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search name, email, or ID..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="dept" class="form-select">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['department_id']; ?>" <?= $dept_filter == $d['department_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($d['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="index.php?mode=bulk" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                    </div>
                <?php else: ?>
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" required
                               placeholder="Enter Faculty ID (e.g. T-0001), email, or name..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="index.php?mode=individual" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($mode === 'bulk'): ?>
            <!-- Bulk Faculty List -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Faculty Directory (<?= count($bulk_faculty); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($bulk_faculty)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>Faculty ID</th>
                                        <th>Name</th>
                                        <th>Designation</th>
                                        <th>Major (Department)</th>
                                        <th>Salary</th>
                                        <th>Email</th>
                                        <th>Courses</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bulk_faculty as $f): ?>
                                        <tr>
                                            <td style="font-weight:600;">T-<?= str_pad((int)$f['teacher_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= htmlspecialchars($f['teacher_name']); ?></td>
                                            <td><?= htmlspecialchars($f['designation'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($f['department_name'] ?? 'N/A'); ?></span></td>
                                            <td><?= $f['salary'] !== null ? 'Rs ' . number_format((float)$f['salary'], 0) : 'N/A'; ?></td>
                                            <td><?= htmlspecialchars($f['email']); ?></td>
                                            <td><span class="badge bg-primary"><?= (int)$f['course_count']; ?></span></td>
                                            <td>
                                                <span class="status-badge <?= $f['teacher_status'] === 'Active' ? 'status-active' : 'status-pending' ?>">
                                                    <?= htmlspecialchars($f['teacher_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="index.php?mode=individual&search=T-<?= str_pad((int)$f['teacher_id'], 4, '0', STR_PAD_LEFT); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-tie"></i>
                            <h5>No Faculty Found</h5>
                            <p class="text-muted">No faculty registered yet in the selected filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'individual' && $individual): ?>
            <!-- Individual Faculty Detail -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Faculty Detail</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Faculty ID</label>
                                <span>T-<?= str_pad((int)$individual['teacher_id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Full Name</label>
                                <span><?= htmlspecialchars($individual['teacher_name']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Designation</label>
                                <span><?= htmlspecialchars($individual['designation'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Major (Department)</label>
                                <span><?= htmlspecialchars($individual['department_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Salary</label>
                                <span><?= $individual['salary'] !== null ? 'Rs ' . number_format((float)$individual['salary'], 0) : 'N/A'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Status</label>
                                <span><span class="status-badge <?= $individual['teacher_status'] === 'Active' ? 'status-active' : 'status-pending' ?>"><?= htmlspecialchars($individual['teacher_status']); ?></span></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Email</label>
                                <span><?= htmlspecialchars($individual['email']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Phone</label>
                                <span><?= htmlspecialchars($individual['phone'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Login Username</label>
                                <span><?= htmlspecialchars($individual['username'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($individual_courses)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Assigned Courses (<?= count($individual_courses); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credit Hours</th>
                                    <th>Semester</th>
                                    <th>Section</th>
                                    <th>Session</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($individual_courses as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['course_code'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($c['course_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($c['credit_hours'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($c['semester_name'] ?? ('Semester #' . $c['semester_id'])); ?></td>
                                        <td>
                                            <span class="badge bg-info">Section <?= htmlspecialchars($c['section_letter'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td class="muted"><?= !empty($c['session_id']) && isset($session_names[$c['session_id']]) ? htmlspecialchars($session_names[$c['session_id']]) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<style>
.detail-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; }
.detail-block label { display: block; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 2px; }
.detail-block span { font-size: 14px; font-weight: 500; color: #0f172a; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
