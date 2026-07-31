<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

global $conn;

$error = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'bulk';
$bulk_students = [];
$individual = null;
$individual_courses = [];
$individual_allocation = [];

// Filter data
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$sessions = [];
$session_names = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; $session_names[$row['session_id']] = $row['session_name']; } }

$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// =============================================
// BULK MODE: list admitted students by session + dept
// (sourced from the admission module: admission_students with fee paid)
// =============================================
if ($mode === 'bulk' && ($dept_filter > 0 || $session_filter > 0 || !empty($search))) {
    $sql = "SELECT asd.student_id AS adm_student_id, asd.application_id, asd.fee_paid,
                   asd.full_name, asd.father_name, asd.gender, asd.contact_no, asd.email,
                   p.program_name, d.department_name, sec.section_name,
                   aa.session_id AS app_session_id,
                   CASE WHEN st.student_id IS NOT NULL THEN 1 ELSE 0 END AS is_registered,
                   st.roll_no, st.student_id AS sso_student_id
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
    if (!empty($search)) {
        $sql .= " AND (asd.full_name LIKE ? OR asd.student_id LIKE ? OR st.roll_no LIKE ? OR asd.application_id LIKE ?)";
        $like = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= 'ssss';
    }

    $sql .= " ORDER BY d.department_name, p.program_name, asd.full_name";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) { $bulk_students[] = $row; }
        mysqli_stmt_close($stmt);
    }

    if (empty($bulk_students)) {
        $error = "No admitted students found for the selected filters. Only students whose admission fee is paid are listed.";
    }
}

// =============================================
// INDIVIDUAL MODE: lookup student by id
// (searches the admission module first, then SSO registered students)
// =============================================
if ($mode === 'individual' && !empty($search)) {
    $sql = "SELECT asd.student_id AS adm_student_id, asd.application_id, asd.fee_paid,
                   asd.full_name, asd.father_name, asd.gender, asd.cnic_or_bform, asd.dob,
                   asd.contact_no, asd.email, asd.address, asd.status AS adm_status,
                   asd.section_id AS adm_section_id,
                   p.program_name, d.department_name, sec.section_name,
                   aa.session_id AS app_session_id,
                   st.student_id AS sso_student_id, st.roll_no, st.status AS sso_status,
                   st.batch_year, st.admission_date, st.current_semester_id, st.current_session_id
            FROM admission_students asd
            LEFT JOIN admission_applications aa ON aa.application_id = asd.application_id
            LEFT JOIN programs p ON p.program_id = asd.program_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            LEFT JOIN sections sec ON sec.section_id = asd.section_id
            LEFT JOIN students st ON st.application_id = asd.application_id
            WHERE asd.student_id = ? OR st.roll_no = ? OR st.student_id = ? OR CAST(asd.application_id AS CHAR) = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $search, $search, $search, $search);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $individual = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if ($individual) {
        // Enrolled courses (student_courses)
        $course_sql = "SELECT sc.course_id, c.course_code, c.course_title, sc.enrollment_date, sc.status
                       FROM student_courses sc
                       LEFT JOIN courses c ON c.course_id = sc.course_id
                       WHERE sc.student_id = ? ORDER BY c.course_code";
        $cstmt = mysqli_prepare($conn, $course_sql);
        if ($cstmt) {
            $stu_ref = $individual['roll_no'] ?: $individual['adm_student_id'];
            mysqli_stmt_bind_param($cstmt, 's', $stu_ref);
            mysqli_stmt_execute($cstmt);
            $cres = mysqli_stmt_get_result($cstmt);
            while ($row = mysqli_fetch_assoc($cres)) { $individual_courses[] = $row; }
            mysqli_stmt_close($cstmt);
        }

        // Course allocations (student_course_allocation)
        $alloc_sql = "SELECT sca.course_id, sca.course_code, sca.course_name, sca.semester, sca.allocated_at
                      FROM student_course_allocation sca
                      WHERE sca.application_id = ? ORDER BY sca.course_code";
        $astmt = mysqli_prepare($conn, $alloc_sql);
        if ($astmt) {
            mysqli_stmt_bind_param($astmt, 'i', $individual['application_id']);
            mysqli_stmt_execute($astmt);
            $ares = mysqli_stmt_get_result($astmt);
            while ($row = mysqli_fetch_assoc($ares)) { $individual_allocation[] = $row; }
            mysqli_stmt_close($astmt);
        }
    } else {
        $error = "No student found for the given ID. Try a student ID, roll number, or application ID.";
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-search"></i> Student Inquiry</h2>
            <div class="btn-group">
                <a href="index.php?mode=bulk" class="btn <?= $mode === 'bulk' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-list"></i> Bulk</a>
                <a href="index.php?mode=individual" class="btn <?= $mode === 'individual' ? 'btn-primary' : 'btn-outline-primary' ?>"><i class="fas fa-user"></i> Individual</a>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Search / Filters Panel -->
        <div class="panel">
            <form method="GET" class="row g-3" id="inquiryForm">
                <input type="hidden" name="mode" value="<?= htmlspecialchars($mode); ?>">

                <?php if ($mode === 'bulk'): ?>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search name or id (optional)..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="session" class="form-select">
                            <option value="0">All Sessions</option>
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= $s['session_id']; ?>" <?= $session_filter == $s['session_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="col-12">
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Select a session and/or department to list all students in bulk.</small>
                    </div>
                <?php else: ?>
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" required
                               placeholder="Enter Student ID, Roll No, or Application ID..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="index.php?mode=individual" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($mode === 'bulk' && !empty($bulk_students)): ?>
            <!-- Bulk Results -->
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Students (<?= count($bulk_students); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Department</th>
                                    <th>Section</th>
                                    <th>Session</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bulk_students as $s): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= htmlspecialchars($s['adm_student_id'] ?: $s['sso_student_id']); ?></td>
                                        <td><?= htmlspecialchars($s['roll_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?= htmlspecialchars($s['full_name']); ?>
                                            <?php if (!empty($s['is_registered'])): ?>
                                                <br><small class="text-muted"><i class="fas fa-check-circle"></i> Registered in SSO</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($s['program_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($s['department_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($s['section_name'])): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($s['section_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="muted"><?= !empty($s['app_session_id']) && isset($session_names[$s['app_session_id']]) ? htmlspecialchars($session_names[$s['app_session_id']]) : 'N/A'; ?></td>
                                        <td><?= htmlspecialchars($s['contact_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($s['is_registered'])): ?>
                                                <span class="status-badge status-active">Registered</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Fee Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?mode=individual&search=<?= urlencode($s['adm_student_id'] ?: $s['sso_student_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'individual' && $individual): ?>
            <!-- Individual Student Detail -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Student Detail</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Student ID</label>
                                <span><?= htmlspecialchars($individual['adm_student_id'] ?: $individual['sso_student_id']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Roll No</label>
                                <span><?= htmlspecialchars($individual['roll_no'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Status</label>
                                <span>
                                    <?php if (!empty($individual['roll_no']) || !empty($individual['sso_student_id'])): ?>
                                        <span class="status-badge status-active">Registered</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Fee Paid</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Full Name</label>
                                <span><?= htmlspecialchars($individual['full_name']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Father Name</label>
                                <span><?= htmlspecialchars($individual['father_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>CNIC / B-Form</label>
                                <span><?= htmlspecialchars($individual['cnic_or_bform'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Gender</label>
                                <span><?= htmlspecialchars($individual['gender'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Date of Birth</label>
                                <span><?= htmlspecialchars($individual['dob'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Contact</label>
                                <span><?= htmlspecialchars($individual['contact_no'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Email</label>
                                <span><?= htmlspecialchars($individual['email'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Address</label>
                                <span><?= htmlspecialchars($individual['address'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Program</label>
                                <span><?= htmlspecialchars($individual['program_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Department</label>
                                <span><?= htmlspecialchars($individual['department_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Section</label>
                                <span><?= htmlspecialchars($individual['section_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Admission Session</label>
                                <span><?= !empty($individual['app_session_id']) && isset($session_names[$individual['app_session_id']]) ? htmlspecialchars($session_names[$individual['app_session_id']]) : 'N/A'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Batch Year</label>
                                <span><?= htmlspecialchars($individual['batch_year'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Admission Date</label>
                                <span><?= htmlspecialchars($individual['admission_date'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Current Semester</label>
                                <span><?= htmlspecialchars($individual['current_semester_id'] ? 'Semester #' . $individual['current_semester_id'] : 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-block">
                                <label>Fee Paid</label>
                                <span><?= $individual['fee_paid'] ? '<span class="status-badge status-active">Yes</span>' : '<span class="status-badge status-pending">No</span>'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($individual_courses) || !empty($individual_allocation)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Enrolled Courses</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($individual_allocation)): ?>
                        <h6 class="text-muted mb-2">SSO Course Allocation</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Semester</th>
                                        <th>Allocated At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($individual_allocation as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['course_code']); ?></td>
                                            <td><?= htmlspecialchars($c['course_name']); ?></td>
                                            <td><?= htmlspecialchars($c['semester']); ?></td>
                                            <td><?= htmlspecialchars($c['allocated_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($individual_courses)): ?>
                        <h6 class="text-muted mb-2">Course Registrations</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($individual_courses as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['course_code'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($c['course_title'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($c['enrollment_date'] ?? 'N/A'); ?></td>
                                            <td><span class="status-badge status-active"><?= htmlspecialchars($c['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($individual_courses) && empty($individual_allocation)): ?>
                        <p class="text-muted mb-0">No courses allocated yet.</p>
                    <?php endif; ?>
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
