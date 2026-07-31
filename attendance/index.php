<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

// =============================================
// FILTER DATA SOURCES
// =============================================
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$sessions = [];
$res = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $sessions[] = $row; } }

// =============================================
// FILTER PARAMETERS
// =============================================
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$session_filter = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$section_filter = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$course_search = isset($_GET['course']) ? trim($_GET['course']) : '';

// Sections for the selected department (normalized A/B/C names)
$sections = [];
if ($dept_filter > 0) {
    $stmt = mysqli_prepare($conn, "SELECT DISTINCT sec.section_id, TRIM(REPLACE(sec.section_name, 'Section ', '')) AS section_name
                                   FROM sections sec
                                   JOIN programs p ON p.program_id = sec.program_id
                                   WHERE p.department_id = ? AND sec.status = 'Active'
                                   ORDER BY section_name");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $dept_filter);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['section_name'])) { $sections[] = $row; }
        }
        mysqli_stmt_close($stmt);
    }
}

// =============================================
// BUILD ATTENDANCE QUERY (grouped into classes)
// =============================================
$sql = "SELECT
            a.attendance_id,
            a.class_date,
            a.status,
            a.remark,
            a.marked_at,
            s.student_id,
            s.roll_no,
            s.full_name AS student_name,
            p.program_id,
            p.program_name,
            d.department_id,
            d.department_name,
            sec.section_id AS student_section_id,
            sec.section_name,
            c.course_id,
            c.course_code,
            COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name,
            t.teacher_name,
            DENSE_RANK() OVER (PARTITION BY a.course_id ORDER BY a.class_date ASC) AS class_no,
            ROW_NUMBER() OVER (PARTITION BY a.course_id, a.class_date ORDER BY s.full_name ASC) AS row_no
        FROM attendance a
        JOIN students s ON s.student_id = a.student_id
        JOIN programs p ON p.program_id = s.program_id
        JOIN departments d ON d.department_id = p.department_id
        LEFT JOIN sections sec ON sec.section_id = s.section_id
        JOIN courses c ON c.course_id = a.course_id
        LEFT JOIN teachers t ON t.teacher_id = a.teacher_id
        WHERE 1=1";

$params = [];
$types = '';

if ($dept_filter > 0) { $sql .= " AND d.department_id = ?"; $params[] = $dept_filter; $types .= 'i'; }
if ($session_filter > 0) {
    $sql .= " AND (s.current_session_id = ? OR s.admission_session_id = ?)";
    $params[] = $session_filter; $params[] = $session_filter; $types .= 'ii';
}
if ($section_filter > 0) { $sql .= " AND s.section_id = ?"; $params[] = $section_filter; $types .= 'i'; }
if (!empty($course_search)) { $sql .= " AND c.course_code LIKE ?"; $like = "%$course_search%"; $params[] = $like; $types .= 's'; }

$sql .= " ORDER BY c.course_code, a.class_date, s.full_name";

$attendances = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $attendances[] = $row; }
    mysqli_stmt_close($stmt);
}

// =============================================
// GROUP ATTENDANCE ROWS INTO CLASSES
// =============================================
$classes = [];
foreach ($attendances as $att) {
    $key = $att['course_id'] . '|' . $att['class_date'];
    if (!isset($classes[$key])) {
        $classes[$key] = [
            'course_id'    => $att['course_id'],
            'course_code'  => $att['course_code'],
            'course_name'  => $att['course_name'],
            'class_date'   => $att['class_date'],
            'class_no'     => $att['class_no'],
            'teacher_name' => $att['teacher_name'],
            'students'     => [],
        ];
    }
    $classes[$key]['students'][] = $att;
}

$total_classes = count($classes);
$total_records = count($attendances);
$present = 0; $absent = 0; $leave = 0;
foreach ($attendances as $att) {
    switch (strtolower($att['status'])) {
        case 'present': $present++; break;
        case 'absent':  $absent++;  break;
        case 'leave':   $leave++;   break;
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list"></i> Attendance Records</h2>
            <div class="btn-group">
                <span class="badge bg-primary" style="align-self:center;"><?= $total_classes ?> class(es)</span>
                <span class="badge bg-secondary" style="align-self:center;"><?= $total_records ?> record(s)</span>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <div class="stats-number"><?= $total_classes ?></div>
                    <div class="stats-label">Total Classes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-present">
                    <div class="stats-number"><?= $present ?></div>
                    <div class="stats-label">Present</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-absent">
                    <div class="stats-number"><?= $absent ?></div>
                    <div class="stats-label">Absent</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-late">
                    <div class="stats-number"><?= $leave ?></div>
                    <div class="stats-label">Leave</div>
                </div>
            </div>
        </div>

        <!-- Search / Filter Panel -->
        <div class="panel">
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-3">
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['department_id']; ?>" <?= $dept_filter == $d['department_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="session" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Sessions</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s['session_id']; ?>" <?= $session_filter == $s['session_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($s['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="section" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Sections</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= $sec['section_id']; ?>" <?= $section_filter == $sec['section_id'] ? 'selected' : ''; ?>>
                                Section <?= htmlspecialchars($sec['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="course" class="form-control" placeholder="Course code (e.g. CS101)"
                           value="<?= htmlspecialchars($course_search); ?>">
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </div>
                <div class="col-12">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Pick a department to enable section filtering. Results are shown class-by-class (each class date is a separate block).</small>
                </div>
            </form>
        </div>

        <?php if (!empty($classes)): ?>
            <?php foreach ($classes as $key => $cls): ?>
                <!-- One block per class (course + class date) -->
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">
                                <span class="badge bg-primary me-2">Class #<?= (int)$cls['class_no']; ?></span>
                                <?= htmlspecialchars($cls['course_code']); ?> - <?= htmlspecialchars($cls['course_name']); ?>
                            </h5>
                            <small class="text-muted">
                                <i class="fas fa-calendar-day me-1"></i><?= date('d M Y', strtotime($cls['class_date'])); ?>
                                <?php if (!empty($cls['teacher_name'])): ?>
                                    &nbsp;|&nbsp;<i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($cls['teacher_name']); ?>
                                <?php endif; ?>
                                &nbsp;|&nbsp;<span class="text-muted"><?= count($cls['students']); ?> student(s)</span>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Roll No</th>
                                        <th>Student</th>
                                        <th>Section</th>
                                        <th>Status</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; ?>
                                    <?php foreach ($cls['students'] as $att): ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><?= htmlspecialchars($att['roll_no'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($att['student_name'] ?? 'N/A'); ?></strong>
                                                <br>
                                                <small class="text-muted">ID: <?= htmlspecialchars($att['student_id']); ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($att['section_name'])): ?>
                                                    <span class="badge bg-info">Section <?= htmlspecialchars(trim(str_replace('Section ', '', $att['section_name']))); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $status = strtolower($att['status']); ?>
                                                <span class="status-badge <?= $status === 'present' ? 'active' : ($status === 'absent' ? 'inactive' : 'pending'); ?>">
                                                    <?= htmlspecialchars(ucfirst($status)); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($att['remark'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card mt-3">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>No Attendance Records Found</h5>
                    <p class="text-muted mb-0">Try changing the filters. Pick a department, session, section, or enter a course code (e.g. CS101) to view classes.</p>
                </div>
            </div>
        <?php endif; ?>

    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
