<?php
$ssrActive = 'review';
$pageTitle = 'Review Schedule Requests';
require_once __DIR__ . '/_common.php';

$f_status = $_GET['status'] ?? '';
$f_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$f_session = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$f_semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$f_program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$f_search = trim($_GET['student'] ?? '');

// =============================================
// POST: approve / reject / forward
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $rejection = trim($_POST['rejection_reason'] ?? '');
    $reviewer = (int)($_SESSION['user_id'] ?? 0);

    $req = ssr_request_row($request_id);
    if (!$req) {
        $error = "Request not found.";
    } elseif ($decision === 'Approved') {
        mysqli_query($conn, "UPDATE student_schedule_requests SET status = 'Approved', reviewed_by = $reviewer, reviewed_at = NOW() WHERE id = $request_id");
        $success = "Request #$request_id approved.";
        log_activity('Student Schedule Requests', 'Request Approved', 'student_schedule_requests', $request_id, "Request #$request_id for {$req['student_name']} approved");
    } elseif ($decision === 'Forwarded') {
        mysqli_query($conn, "UPDATE student_schedule_requests SET status = 'Forwarded', reviewed_by = $reviewer, reviewed_at = NOW() WHERE id = $request_id");
        $success = "Request #$request_id forwarded to senior administration.";
        log_activity('Student Schedule Requests', 'Request Forwarded', 'student_schedule_requests', $request_id, "Request #$request_id for {$req['student_name']} forwarded");
    } elseif ($decision === 'Rejected') {
        if ($rejection === '') { $error = "Please provide a reason for rejection."; }
        else {
            $stmt = mysqli_prepare($conn, "UPDATE student_schedule_requests SET status = 'Rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sii', $rejection, $reviewer, $request_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $success = "Request #$request_id rejected.";
            log_activity('Student Schedule Requests', 'Request Rejected', 'student_schedule_requests', $request_id, "Request #$request_id for {$req['student_name']} rejected: $rejection");
        }
    } else {
        $error = "Invalid action.";
    }
}

// =============================================
// Load filtered requests
// =============================================
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
if ($f_session > 0) { $sql .= " AND r.session_id = ?"; $params[] = $f_session; $types .= 'i'; }
if ($f_semester > 0) { $sql .= " AND r.semester_id = ?"; $params[] = $f_semester; $types .= 'i'; }
if ($f_program > 0) { $sql .= " AND r.program_id = ?"; $params[] = $f_program; $types .= 'i'; }
if ($f_search !== '') { $sql .= " AND (r.roll_no = ? OR r.student_name LIKE ? OR CAST(r.student_id AS CHAR) = ?)"; $params[] = $f_search; $params[] = "%$f_search%"; $params[] = $f_search; $types .= 'sss'; }
$sql .= " ORDER BY CASE r.status WHEN 'Pending' THEN 0 ELSE 1 END, r.created_at DESC, r.id DESC";

$requests = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $requests[] = $row; }
    mysqli_stmt_close($stmt);
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-inbox"></i> Review Schedule Requests</h2>
            <p class="text-muted mb-0">Approve, reject or forward student schedule requests.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

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
                    <label class="form-label small text-muted fw-semibold">Session</label>
                    <select name="session" class="form-select">
                        <option value="0">All Sessions</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int)$s['session_id']; ?>" <?= $f_session === (int)$s['session_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['session_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="0">All Semesters</option>
                        <?php foreach ($semesters as $se): ?>
                            <option value="<?= (int)$se['semester_id']; ?>" <?= $f_semester === (int)$se['semester_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($se['semester_name']); ?></option>
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
                <h5>Schedule Requests (<?= count($requests); ?>)</h5>
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
                                    <th>Description</th>
                                    <th>Program / Session</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th style="min-width:260px;">Action</th>
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
                                        <td style="max-width:220px;">
                                            <small><?= nl2br(htmlspecialchars(substr($r['conflict_description'], 0, 120))); ?><?= strlen($r['conflict_description']) > 120 ? '...' : ''; ?></small>
                                            <?php if (!empty($r['requested_solution'])): ?><br><small class="text-info"><i class="fas fa-lightbulb"></i> <?= nl2br(htmlspecialchars(substr($r['requested_solution'], 0, 80))); ?></small><?php endif; ?>
                                        </td>
                                        <td><small><?= htmlspecialchars($r['program_name'] ?? '—'); ?><br><?= htmlspecialchars($r['session_name'] ?? '—'); ?> / <?= htmlspecialchars($r['semester_name'] ?? '—'); ?></small></td>
                                        <td>
                                            <?php
                                            $badge = 'bg-warning text-dark';
                                            if ($r['status'] === 'Approved') $badge = 'bg-success';
                                            elseif ($r['status'] === 'Rejected') $badge = 'bg-danger';
                                            elseif ($r['status'] === 'Forwarded') $badge = 'bg-info text-dark';
                                            ?>
                                            <span class="badge <?= $badge; ?>"><?= htmlspecialchars($r['status']); ?></span>
                                            <?php if (!empty($r['rejection_reason'])): ?><br><small class="text-danger" title="Reason"><?= htmlspecialchars(substr($r['rejection_reason'], 0, 50)); ?></small><?php endif; ?>
                                        </td>
                                        <td><small><?= date('d M Y, h:i A', strtotime($r['created_at'])); ?></small></td>
                                        <td>
                                            <?php if ($r['status'] === 'Pending'): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="openReview(<?= (int)$r['id']; ?>, <?= (int)$r['id']; ?>)"><i class="fas fa-edit"></i> Review</button>
                                            <?php else: ?>
                                                <small class="text-muted">Reviewed by <?= htmlspecialchars($r['reviewer_name'] ?? 'staff'); ?><br><?= $r['reviewed_at'] ? date('d M Y', strtotime($r['reviewed_at'])) : ''; ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No Requests Found</h5>
                        <p class="text-muted">Try adjusting the filters, or submit a new request.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Review modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Review Schedule Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="rejectBtn"><i class="fas fa-times"></i> Reject</button>
                    <button type="button" class="btn btn-info" id="forwardBtn"><i class="fas fa-forward"></i> Forward</button>
                    <button type="button" class="btn btn-success" id="approveBtn"><i class="fas fa-check"></i> Approve</button>
                </div>
            </div>
        </div>
    </div>

<script>
const REQUESTS_JSON = <?= json_encode($requests); ?>;

function openReview(requestId, rowId) {
    const r = REQUESTS_JSON.find(function(x) { return x.id === requestId; });
    if (!r) return;
    document.getElementById('reviewBody').innerHTML =
        '<input type="hidden" id="reviewRequestId" value="' + r.id + '">' +
        '<div class="mb-2"><strong>' + r.student_name + '</strong> <span class="badge bg-secondary">' + (r.roll_no || '') + '</span></div>' +
        '<table class="table table-sm table-bordered">' +
        '<tr><th style="width:160px;">Course</th><td><strong>' + (r.course_code || '—') + '</strong> - ' + (r.course_name || '') + '</td></tr>' +
        '<tr><th>Type</th><td><span class="badge bg-secondary">' + r.conflict_type + '</span></td></tr>' +
        '<tr><th>Program</th><td>' + (r.program_name || '—') + ' / ' + (r.session_name || '—') + ' / ' + (r.semester_name || '—') + '</td></tr>' +
        '<tr><th>Description</th><td>' + nl2brEsc(r.conflict_description) + '</td></tr>' +
        '<tr><th>Requested Solution</th><td>' + (r.requested_solution ? nl2brEsc(r.requested_solution) : '<em class="text-muted">None</em>') + '</td></tr>' +
        '<tr><th>Current Timetable</th><td><small class="text-muted">' + (r.current_timetable || '—') + '</small></td></tr>' +
        '<tr><th>Submitted</th><td>' + new Date(r.created_at.replace(' ', 'T')).toLocaleString() + '</td></tr>' +
        '</table>' +
        '<div class="d-none" id="rejectBox">' +
        '<label class="form-label small text-muted fw-semibold">Rejection Reason <span class="text-danger">*</span></label>' +
        '<textarea id="rejectionReason" class="form-control" rows="2" placeholder="Explain why this request is being rejected..."></textarea></div>';
    const bsModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    bsModal.show();
}

function nl2brEsc(s) {
    if (!s) return '';
    return String(s).replace(/[&<>]/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[m]; }).replace(/\n/g, '<br>');
}

function submitDecision(decision) {
    const id = document.getElementById('reviewRequestId').value;
    if (!id) return;
    const form = document.createElement('form');
    form.method = 'POST';
    const inp1 = document.createElement('input'); inp1.type = 'hidden'; inp1.name = 'request_id'; inp1.value = id; form.appendChild(inp1);
    const inp2 = document.createElement('input'); inp2.type = 'hidden'; inp2.name = 'decision'; inp2.value = decision; form.appendChild(inp2);
    if (decision === 'Rejected') {
        const reason = document.getElementById('rejectionReason').value.trim();
        if (!reason) { alert('Please provide a rejection reason.'); return; }
        const inp3 = document.createElement('input'); inp3.type = 'hidden'; inp3.name = 'rejection_reason'; inp3.value = reason; form.appendChild(inp3);
    }
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const rejectBox = document.getElementById('rejectBox');
    const rejectBtn = document.getElementById('rejectBtn');
    const forwardBtn = document.getElementById('forwardBtn');
    const approveBtn = document.getElementById('approveBtn');
    if (!rejectBtn || !forwardBtn || !approveBtn) return;
    rejectBtn.addEventListener('click', function() {
        const box = document.getElementById('rejectBox');
        if (box) box.classList.remove('d-none');
        submitDecision('Rejected');
    });
    forwardBtn.addEventListener('click', function() { submitDecision('Forwarded'); });
    approveBtn.addEventListener('click', function() {
        if (confirm('Approve this schedule request?')) { submitDecision('Approved'); }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
