<?php
$ssrActive = 'history';
$pageTitle = 'Schedule Request History';
require_once __DIR__ . '/_common.php';

$f_status = $_GET['status'] ?? '';
$f_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$f_program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$f_search = trim($_GET['student'] ?? '');

$sql = "SELECT r.*, p.program_name, d.department_name, ss.session_name, sem.semester_name,
               c.course_code, COALESCE(NULLIF(c.course_name, ''), c.course_title) AS course_name,
               rb.full_name AS reviewer_name
        FROM student_schedule_requests r
        LEFT JOIN programs p ON p.program_id = r.program_id
        LEFT JOIN departments d ON d.department_id = r.department_id
        LEFT JOIN sessions ss ON ss.session_id = r.session_id
        LEFT JOIN semesters sem ON sem.semester_id = r.semester_id
        LEFT JOIN courses c ON c.course_id = r.course_id
        LEFT JOIN students rb ON rb.student_id = r.reviewed_by
        WHERE 1=1";
$params = [];
$types = '';
if ($f_status !== '' && in_array($f_status, ['Pending', 'Approved', 'Rejected', 'Forwarded'], true)) { $sql .= " AND r.status = ?"; $params[] = $f_status; $types .= 's'; }
if ($f_dept > 0) { $sql .= " AND r.department_id = ?"; $params[] = $f_dept; $types .= 'i'; }
if ($f_program > 0) { $sql .= " AND r.program_id = ?"; $params[] = $f_program; $types .= 'i'; }
if ($f_search !== '') { $sql .= " AND (r.roll_no = ? OR r.student_name LIKE ? OR CAST(r.student_id AS CHAR) = ?)"; $params[] = $f_search; $params[] = "%$f_search%"; $params[] = $f_search; $types .= 'sss'; }
$sql .= " ORDER BY r.created_at DESC, r.id DESC";

$requests = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $requests[] = $row; }
    mysqli_stmt_close($stmt);
}

// Counts for summary cards
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Forwarded' => 0];
$all_req = [];
$res = mysqli_query($conn, "SELECT status, COUNT(*) c FROM student_schedule_requests GROUP BY status");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $counts[$row['status']] = (int)$row['c']; } }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-history"></i> Schedule Request History</h2>
            <p class="text-muted mb-0">Complete audit log of all student schedule requests.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Summary cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-warning"><?= $counts['Pending']; ?></div><small class="text-muted">Pending</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-success"><?= $counts['Approved']; ?></div><small class="text-muted">Approved</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-danger"><?= $counts['Rejected']; ?></div><small class="text-muted">Rejected</small></div></div></div>
            <div class="col-md-3 col-6"><div class="card shadow-sm border-0"><div class="card-body text-center"><div class="display-6 fw-bold text-info"><?= $counts['Forwarded']; ?></div><small class="text-muted">Forwarded</small></div></div></div>
        </div>

        <!-- Filter panel -->
        <div class="panel">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="Pending" <?= $f_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?= $f_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?= $f_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Forwarded" <?= $f_status === 'Forwarded' ? 'selected' : ''; ?>>Forwarded</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Department</label>
                    <select name="dept" class="form-select">
                        <option value="0">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['department_id']; ?>" <?= $f_dept === (int)$d['department_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($d['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Program</label>
                    <select name="program" class="form-select">
                        <option value="0">All Programs</option>
                        <?php foreach ($programs as $pr): ?>
                            <option value="<?= (int)$pr['program_id']; ?>" <?= $f_program === (int)$pr['program_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($pr['program_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Student ID / Roll</label>
                    <input type="text" name="student" class="form-control" value="<?= htmlspecialchars($f_search); ?>" placeholder="Search student...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>All Requests (<?= count($requests); ?>)</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
            <div class="card-body">
                <?php if (!empty($requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Program / Session</th>
                                    <th>Status</th>
                                    <th>Reviewed By</th>
                                    <th>Submitted</th>
                                    <th>Reviewed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <tr>
                                        <td><?= (int)$r['id']; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($r['student_name']); ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($r['roll_no'] ?? ''); ?> (ID <?= (int)$r['student_id']; ?>)</small>
                                        </td>
                                        <td><strong><?= htmlspecialchars($r['course_code'] ?? '—'); ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['course_name'] ?? ''); ?></small></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['conflict_type']); ?></span></td>
                                        <td><small><?= htmlspecialchars($r['program_name'] ?? '—'); ?><br><?= htmlspecialchars($r['session_name'] ?? '—'); ?> / <?= htmlspecialchars($r['semester_name'] ?? '—'); ?></small></td>
                                        <td>
                                            <?php
                                            $badge = 'bg-warning text-dark';
                                            if ($r['status'] === 'Approved') $badge = 'bg-success';
                                            elseif ($r['status'] === 'Rejected') $badge = 'bg-danger';
                                            elseif ($r['status'] === 'Forwarded') $badge = 'bg-info text-dark';
                                            ?>
                                            <span class="badge <?= $badge; ?>"><?= htmlspecialchars($r['status']); ?></span>
                                            <?php if (!empty($r['rejection_reason'])): ?><br><small class="text-danger"><?= htmlspecialchars(substr($r['rejection_reason'], 0, 60)); ?></small><?php endif; ?>
                                        </td>
                                        <td><small><?= htmlspecialchars($r['reviewer_name'] ?? '—'); ?></small></td>
                                        <td><small><?= date('d M Y', strtotime($r['created_at'])); ?></small></td>
                                        <td><small><?= $r['reviewed_at'] ? date('d M Y', strtotime($r['reviewed_at'])) : '—'; ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h5>No Requests Found</h5>
                        <p class="text-muted">No schedule requests match the current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
