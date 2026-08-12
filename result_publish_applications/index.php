<?php
// result_publish_applications/index.php - SSO: review and approve/reject result publish requests.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
require_once __DIR__ . '/../modules/sbe/config/database.php';
require_once __DIR__ . '/includes/transcript_pdf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireSSO();

$db = db();
$conn = getConnection();

$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'approved', 'rejected'], true)
    ? $_GET['status'] : 'pending';
$deptFilter = !empty($_GET['department_id']) ? (int) $_GET['department_id'] : 0;

$departments = $db->query('SELECT department_id, department_name FROM departments WHERE status = "Active" ORDER BY department_name')->fetchAll();

if (!function_exists('grade_letter')) {
    function grade_letter(?float $pct): string
    {
        if ($pct === null) return '-';
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        return 'F';
    }
}

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appId = (int) ($_POST['app_id'] ?? 0);

    if ($appId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $stmt = $db->prepare(
            'SELECT app.*, er.exam_result_id, er.obtained_marks, er.total_marks, er.percentage,
                    er.pass_fail_status, er.remarks,
                    e.exam_code, e.title AS exam_title, e.exam_type,
                    s.full_name, s.roll_no, s.student_id, s.batch_year, s.user_id AS student_user_id,
                    sem.semester_name, p.program_name
             FROM result_publish_applications app
             JOIN sbe_exam_results er ON er.exam_result_id = app.exam_result_id
             JOIN sbe_exams e ON e.exam_id = app.exam_id
             JOIN students s ON s.student_id = app.student_id
             LEFT JOIN programs p ON p.program_id = s.program_id
             LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
             WHERE app.id = :id'
        );
        $stmt->execute([':id' => $appId]);
        $app = $stmt->fetch();

        if (!$app || $app['status'] !== 'pending') {
            $error = 'Application not found or already reviewed.';
        } else {
            $userId = (int) ($_SESSION['user_id'] ?? 0);

            if ($action === 'approve') {
                $pdf = result_transcript_pdf([
                    'student_name'   => $app['full_name'],
                    'roll_no'        => $app['roll_no'],
                    'student_id'     => $app['student_id'],
                    'program_name'   => $app['program_name'] ?? '',
                    'semester_name'  => $app['semester_name'] ?? '',
                    'batch_year'     => $app['batch_year'],
                    'exam_code'      => $app['exam_code'],
                    'exam_title'     => $app['exam_title'],
                    'exam_type'      => $app['exam_type'],
                    'obtained_marks' => $app['obtained_marks'],
                    'total_marks'    => $app['total_marks'],
                    'percentage'     => $app['percentage'],
                    'grade'          => grade_letter((float) $app['percentage']),
                    'pass_fail_status' => $app['pass_fail_status'],
                    'remarks'        => $app['remarks'],
                    'published_at'   => date('d M Y h:i A'),
                    'issued_by'      => (string) ($_SESSION['full_name'] ?? 'SSO Office'),
                ]);

                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/uploads/transcripts/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }
                $fileName = 'transcript_' . (int) $app['exam_result_id'] . '_' . date('Ymd_His') . '.pdf';
                $filePath = 'uploads/transcripts/' . $fileName;

                if (file_put_contents($uploadDir . $fileName, $pdf) === false) {
                    $error = 'Could not generate transcript PDF. Please try again.';
                } else {
                    $db->beginTransaction();
                    $db->prepare('UPDATE result_publish_applications SET status = :status, transcript_path = :path, reviewed_by = :by, reviewed_at = NOW(), reviewer_remarks = NULL WHERE id = :id')
                        ->execute([
                            ':status' => 'approved',
                            ':path'   => $filePath,
                            ':by'     => $userId,
                            ':id'     => $appId,
                        ]);
                    $db->prepare('UPDATE sbe_exam_results SET status = :status, published_at = NOW() WHERE exam_result_id = :id')
                        ->execute([':status' => 'Published', ':id' => (int) $app['exam_result_id']]);
                    $db->commit();

                    if ($conn) {
                        $title = 'Result Published';
                        $msg = 'Your result for ' . $app['exam_code'] . ' - ' . $app['exam_title'] . ' has been published. You can view it in your LMS examination page.';
                        $ins = mysqli_prepare($conn, 'INSERT INTO notifications (title, message, audience_type, sent_by, recipient_count) VALUES (?, ?, ?, ?, 1)');
                        if ($ins) {
                            $aud = 'Students';
                            $ins->bind_param('sssi', $title, $msg, $aud, $userId);
                            $ins->execute();
                            $nid = (int) $conn->insert_id;
                            $ins->close();
                            $rid = (int) $app['student_id'];
                            $rt = 'Students';
                            $recip = mysqli_prepare($conn, 'INSERT INTO notification_recipients (notification_id, recipient_type, recipient_id) VALUES (?, ?, ?)');
                            if ($recip) {
                                $recip->bind_param('isi', $nid, $rt, $rid);
                                $recip->execute();
                                $recip->close();
                            }
                        }
                    }

                    require_once __DIR__ . '/../includes/activity.php';
                    log_activity('Examination', 'Result Published', 'sbe_exam_results', (int) $app['exam_result_id'], 'SSO approved and published result (exam_result_id ' . $app['exam_result_id'] . ')');

                    $message = 'Result approved and published to the student LMS.';
                }
            } else {
                $remarks = trim((string) ($_POST['remarks'] ?? ''));
                $db->beginTransaction();
                $db->prepare('UPDATE result_publish_applications SET status = :status, reviewer_remarks = :remarks, reviewed_by = :by, reviewed_at = NOW() WHERE id = :id')
                    ->execute([
                        ':status'  => 'rejected',
                        ':remarks' => $remarks !== '' ? $remarks : 'Rejected by SSO office.',
                        ':by'      => $userId,
                        ':id'      => $appId,
                    ]);
                $db->prepare('UPDATE sbe_exam_results SET status = :status, published_at = NULL WHERE exam_result_id = :id')
                    ->execute([':status' => 'Draft', ':id' => (int) $app['exam_result_id']]);
                $db->commit();

                if ($conn) {
                    $title = 'Result Publish Rejected';
                    $msg = 'Your publish request for ' . $app['exam_code'] . ' - ' . $app['exam_title'] . ' was rejected by SSO.';
                    $ins = mysqli_prepare($conn, 'INSERT INTO notifications (title, message, audience_type, sent_by, recipient_count) VALUES (?, ?, ?, ?, 1)');
                    if ($ins) {
                        $aud = 'Students';
                        $ins->bind_param('sssi', $title, $msg, $aud, $userId);
                        $ins->execute();
                        $nid = (int) $conn->insert_id;
                        $ins->close();
                        $rid = (int) $app['student_id'];
                        $rt = 'Students';
                        $recip = mysqli_prepare($conn, 'INSERT INTO notification_recipients (notification_id, recipient_type, recipient_id) VALUES (?, ?, ?)');
                        if ($recip) {
                            $recip->bind_param('isi', $nid, $rt, $rid);
                            $recip->execute();
                            $recip->close();
                        }
                    }
                }

                require_once __DIR__ . '/../includes/activity.php';
                log_activity('Examination', 'Result Rejected', 'sbe_exam_results', (int) $app['exam_result_id'], 'SSO rejected publish request for exam_result_id ' . $app['exam_result_id']);

                $message = 'Request rejected. The result has been returned to the examination module.';
            }
        }
    }
}

// ---- Load applications ----
$conds  = ["app.status = :status"];
$params = [':status' => $statusFilter];
if ($deptFilter > 0) {
    $conds[] = 'app.department_id = :dept';
    $params[':dept'] = $deptFilter;
}

$sql = "SELECT app.*, er.exam_result_id, er.obtained_marks, er.total_marks, er.percentage,
               er.pass_fail_status, er.status AS result_status,
               e.exam_code, e.title AS exam_title, e.exam_type,
               s.full_name, s.roll_no, s.student_id, s.batch_year,
               sem.semester_name, p.program_name, d.department_name, sess.session_name,
               u.full_name AS requested_by_name
        FROM result_publish_applications app
        JOIN sbe_exam_results er ON er.exam_result_id = app.exam_result_id
        JOIN sbe_exams e ON e.exam_id = app.exam_id
        JOIN students s ON s.student_id = app.student_id
        LEFT JOIN programs p ON p.program_id = s.program_id
        LEFT JOIN departments d ON d.department_id = p.department_id
        LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
        LEFT JOIN sessions sess ON sess.session_id = s.current_session_id
        LEFT JOIN users u ON u.user_id = app.requested_by
        WHERE " . implode(' AND ', $conds) . "
        ORDER BY app.requested_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$counts = $db->query("SELECT status, COUNT(*) AS cnt FROM result_publish_applications GROUP BY status")->fetchAll();
$countMap = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($counts as $c) {
    $countMap[$c['status']] = (int) $c['cnt'];
}

$pageTitle = 'Result Publish Requests';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Blue Header Bar -->
<div style="background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 50%,#2563EB 100%);border-radius:var(--radius-lg);padding:24px 28px;margin-bottom:24px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
    <div>
        <div style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">Result Publish Requests</div>
        <div style="font-size:.85rem;color:rgba(255,255,255,0.75);">Approve or reject result publication requests sent by the Examination module.</div>
    </div>
    <div style="text-align:right;font-size:.85rem;color:rgba(255,255,255,0.85);">
        <?= date('l') ?><br><?= date('d M Y') ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom:18px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:18px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon stat-card-warning">&#9203;</div>
        <div class="stat-number"><?= $countMap['pending'] ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-card-success">&#9989;</div>
        <div class="stat-number"><?= $countMap['approved'] ?></div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-card-danger" style="color:var(--danger);">&#10060;</div>
        <div class="stat-number"><?= $countMap['rejected'] ?></div>
        <div class="stat-label">Rejected</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-card-primary">&#128202;</div>
        <div class="stat-number"><?= $countMap['pending'] + $countMap['approved'] + $countMap['rejected'] ?></div>
        <div class="stat-label">Total Requests</div>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:18px;">
            <div style="min-width:140px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div style="min-width:200px;">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['department_id'] ?>" <?= $deptFilter === (int) $dept['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Department</th>
                        <th>Session</th>
                        <th>Marks / Grade</th>
                        <th>Requested By</th>
                        <th>Requested At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">&#128202;</div>
                                    <p class="empty-state-text">No requests found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php
                            $appStatus = $app['status'];
                            $statusStyle = $appStatus === 'approved'
                                ? 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'
                                : ($appStatus === 'rejected'
                                    ? 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);'
                                    : 'background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border);');
                            $pct = (float) $app['percentage'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($app['full_name']) ?></strong><br>
                                    <small style="color:var(--text-secondary);">ID: <?= (int) $app['student_id'] ?><?= $app['roll_no'] ? ' &middot; Roll: ' . htmlspecialchars((string) $app['roll_no']) : '' ?></small>
                                </td>
                                <td>
                                    <span class="status-badge badge-exam-quiz"><?= htmlspecialchars($app['exam_code']) ?></span><br>
                                    <small style="color:var(--text-secondary);"><?= htmlspecialchars(mb_strimwidth((string) $app['exam_title'], 0, 28, '...')) ?></small>
                                </td>
                                <td><?= $app['department_name'] ? htmlspecialchars($app['department_name']) : '-' ?></td>
                                <td><?= $app['session_name'] ? htmlspecialchars($app['session_name']) : '-' ?></td>
                                <td>
                                    <strong><?= number_format((float) $app['obtained_marks'], 2) ?></strong> / <?= number_format((float) $app['total_marks'], 2) ?><br>
                                    <small style="color:var(--text-secondary);"><?= number_format($pct, 1) ?>% &middot; Grade <?= grade_letter($pct) ?></small>
                                </td>
                                <td><?= $app['requested_by_name'] ? htmlspecialchars($app['requested_by_name']) : '-' ?></td>
                                <td><?= $app['requested_at'] ? date('d M Y h:i A', strtotime((string) $app['requested_at'])) : '-' ?></td>
                                <td>
                                    <span class="status-badge" style="<?= $statusStyle ?>"><?= ucfirst($appStatus) ?></span>
                                    <?php if ($appStatus === 'rejected' && $app['reviewer_remarks']): ?>
                                        <small style="color:var(--text-secondary);display:block;max-width:180px;"><?= htmlspecialchars(mb_strimwidth((string) $app['reviewer_remarks'], 0, 60, '...')) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>examination/results/paper.php?exam_result_id=<?= (int) $app['exam_result_id'] ?>" target="_blank" title="View Paper">
                                            <i class="bi bi-eye"></i> Paper
                                        </a>
                                        <?php if ($appStatus === 'pending'): ?>
                                            <form method="post" action="index.php" style="display:inline;" onsubmit="return confirm('Approve and publish this result to the student LMS?')">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="app_id" value="<?= (int) $app['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve & Publish">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" action="index.php" style="display:inline;" onsubmit="return promptRemarks(this);">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="app_id" value="<?= (int) $app['id'] ?>">
                                                <input type="hidden" name="remarks" value="">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </form>
                                        <?php elseif ($appStatus === 'approved' && $app['transcript_path']): ?>
                                            <a class="btn btn-sm btn-outline-success" href="transcript.php?app_id=<?= (int) $app['id'] ?>" target="_blank" title="View Transcript">
                                                <i class="bi bi-file-earmark-pdf"></i> Transcript
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function promptRemarks(form) {
    var remarks = window.prompt('Reason for rejection (optional):');
    if (remarks === null) { return false; }
    form.querySelector('input[name="remarks"]').value = remarks;
    return confirm('Reject this publish request?');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
