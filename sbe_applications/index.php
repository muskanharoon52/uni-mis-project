<?php
$pageTitle = 'SBE Applications';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
require_once __DIR__ . '/../notifications/_helpers.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

// Only SSO staff can review and approve schedule applications.
$allowedRoles = ['Admin', 'Super Admin', 'Examiner'];
$role = strtolower($_SESSION['role_name'] ?? '');
if (!in_array($role, array_map('strtolower', $allowedRoles), true)) {
    header('Location: /uni-mis-project/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/activity.php';
global $conn;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $remarks = trim((string) ($_POST['review_remarks'] ?? ''));

    $appSql = "SELECT ap.*, e.exam_code, e.title AS exam_title, e.exam_type, e.batch_year, e.department_id, e.course_id, e.status AS exam_status, d.department_name, t.teacher_name
               FROM sbe_applications ap
               INNER JOIN sbe_exams e ON e.exam_id = ap.exam_id
               LEFT JOIN departments d ON d.department_id = e.department_id
               LEFT JOIN teachers t ON t.teacher_id = ap.teacher_id
               WHERE ap.application_id = " . $applicationId . " LIMIT 1";
    $appRes = mysqli_query($conn, $appSql);
    $app = $appRes ? mysqli_fetch_assoc($appRes) : null;

    if (!$app) {
        $error = 'Application not found.';
    } elseif ($app['status'] !== 'Pending') {
        $error = 'This application has already been reviewed.';
    } elseif ($action === 'reject') {
        $stmt = mysqli_prepare($conn, "UPDATE sbe_applications SET status = 'Rejected', review_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE application_id = ?");
        mysqli_stmt_bind_param($stmt, 'sii', $remarks, $_SESSION['user_id'], $applicationId);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Application rejected. The teacher has been notified with your remarks.';
            log_activity('SBE Applications', 'Application Rejected', 'sbe_applications', $applicationId, 'Exam: ' . $app['exam_code'] . ' | Remarks: ' . mb_substr($remarks, 0, 120));
        } else {
            $error = 'Failed to update the application: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } elseif ($action === 'approve') {
        if (empty($app['semester_id']) || empty($app['requested_date']) || empty($app['start_time']) || empty($app['end_time']) || empty($app['location'])) {
            $error = 'The application is missing required schedule details (semester, date, time or location).';
        } else {
            mysqli_begin_transaction($conn);
            try {
                // 1. Mark application as approved.
                $stmt = mysqli_prepare($conn, "UPDATE sbe_applications SET status = 'Approved', review_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE application_id = ?");
                mysqli_stmt_bind_param($stmt, 'sii', $remarks, $_SESSION['user_id'], $applicationId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // 2. Create / refresh the schedule row for this exam.
                $examId = (int) $app['exam_id'];
                $schSql = "SELECT schedule_id FROM sbe_exam_schedule WHERE exam_id = " . $examId . " LIMIT 1";
                $schRes = mysqli_query($conn, $schSql);
                $existingSchedule = $schRes ? mysqli_fetch_assoc($schRes) : null;

                $remarksForSchedule = $remarks !== '' ? $remarks : ($app['message'] ?: 'Approved by SSO');
                $sectionName = $app['section_name'] ?? 'A';
                $semesterId = (int) $app['semester_id'];
                $requestedDate = $app['requested_date'];
                $startTime = $app['start_time'];
                $endTime = $app['end_time'];
                $location = $app['location'];
                if ($existingSchedule) {
                    $stmt = mysqli_prepare($conn, "UPDATE sbe_exam_schedule SET section = ?, semester_id = ?, exam_date = ?, start_time = ?, end_time = ?, late_submission_grace_minutes = 0, location = ?, remarks = ?, status = 'Scheduled' WHERE schedule_id = ?");
                    mysqli_stmt_bind_param($stmt, 'sisssssi', $sectionName, $semesterId, $requestedDate, $startTime, $endTime, $location, $remarksForSchedule, $existingSchedule['schedule_id']);
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO sbe_exam_schedule (exam_id, section, semester_id, exam_date, start_time, end_time, late_submission_grace_minutes, location, remarks, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'Scheduled')");
                    mysqli_stmt_bind_param($stmt, 'isisssss', $examId, $sectionName, $semesterId, $requestedDate, $startTime, $endTime, $location, $remarksForSchedule);
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // 3. Publish the exam so students can take it.
                mysqli_query($conn, "UPDATE sbe_exams SET status = 'Published' WHERE exam_id = " . $examId . " AND status != 'Published'");

                // 4. Notify students of the department + batch.
                $deptId = (int) $app['department_id'];
                $batchYear = (int) $app['batch_year'];
                $studentIds = notif_student_ids($conn, $deptId ? [$deptId] : [], $batchYear ? [$batchYear] : []);

                if (!empty($studentIds)) {
                    $title = 'SBE Exam Scheduled - ' . $app['exam_code'];
                    $timeWindow = substr((string) $app['start_time'], 0, 5) . ' - ' . substr((string) $app['end_time'], 0, 5);
                    $message = 'Your ' . $app['exam_type'] . ' exam "' . $app['exam_title'] . '" is scheduled on ' . $app['requested_date'] . ' from ' . $timeWindow . ' at ' . $app['location'] . '.' . ($remarks !== '' ? ' Note: ' . $remarks : '');

                    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (title, message, audience_type, sent_by, recipient_count) VALUES (?, ?, 'Students', ?, ?)");
                    $total = count($studentIds);
                    mysqli_stmt_bind_param($stmt, 'ssii', $title, $message, $_SESSION['user_id'], $total);
                    mysqli_stmt_execute($stmt);
                    $notificationId = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    $stmtR = mysqli_prepare($conn, "INSERT INTO notification_recipients (notification_id, recipient_type, recipient_id) VALUES (?, 'Students', ?)");
                    foreach ($studentIds as $sid) {
                        mysqli_stmt_bind_param($stmtR, 'ii', $notificationId, $sid);
                        mysqli_stmt_execute($stmtR);
                    }
                    mysqli_stmt_close($stmtR);
                }

                mysqli_commit($conn);

                $success = 'Application approved. The exam is now scheduled and ' . count($studentIds) . ' student(s) have been notified.';
                log_activity('SBE Applications', 'Application Approved', 'sbe_applications', $applicationId, 'Exam: ' . $app['exam_code'] . ' | Date: ' . $app['requested_date'] . ' | Notified: ' . count($studentIds));
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = 'Failed to approve the application: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Invalid action.';
    }
}

$sql = "SELECT ap.*, e.exam_code, e.title AS exam_title, e.exam_type, e.batch_year, d.department_name, s.section_name, c.course_code, t.teacher_name, u.full_name AS reviewer_name
        FROM sbe_applications ap
        INNER JOIN sbe_exams e ON e.exam_id = ap.exam_id
        LEFT JOIN departments d ON d.department_id = e.department_id
        LEFT JOIN sections s ON s.section_id = e.section_id
        LEFT JOIN courses c ON c.course_id = e.course_id
        LEFT JOIN teachers t ON t.teacher_id = ap.teacher_id
        LEFT JOIN users u ON u.user_id = ap.reviewed_by
        ORDER BY (ap.status = 'Pending') DESC, ap.created_at DESC";

$applications = [];
$res = mysqli_query($conn, $sql);
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $applications[] = $r; } }

$stats = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
foreach ($applications as $ap) { $stats[$ap['status']] = ($stats[$ap['status']] ?? 0) + 1; }

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2><i class="fas fa-clipboard-check"></i> SBE Applications</h2>
            <span class="text-muted small">Schedule applications sent by teachers for Quiz, Assignment Test and Practice exams.</span>
        </div>
        <a href="<?= BASE_URL ?>modules/sbe/schedule.php" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-box-arrow-up-right"></i> Open SBE Schedule</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row mt-3 g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?= $stats['Pending'] ?></h5>
                        <span class="text-muted small">Pending Review</span>
                    </div>
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?= $stats['Approved'] ?></h5>
                        <span class="text-muted small">Approved</span>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?= $stats['Rejected'] ?></h5>
                        <span class="text-muted small">Rejected</span>
                    </div>
                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Schedule Applications (<?= count($applications); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-0">No schedule applications yet. Teachers submit them from the SBE Schedule module.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Teacher</th>
                                <th>Class</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Review</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $ap): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($ap['exam_code']) ?></span>
                                        <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($ap['exam_title'], 0, 34, '...')) ?></div>
                                        <div class="small"><span class="badge text-bg-info"><?= htmlspecialchars($ap['exam_type']) ?></span></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($ap['teacher_name'] ?? '—') ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars($ap['section_name'] ?? '—') ?><br>
                                        <span class="text-muted"><?= htmlspecialchars($ap['department_name'] ?? '—') ?> &middot; <?= $ap['batch_year'] ? (int) $ap['batch_year'] : '—' ?></span>
                                    </td>
                                    <td class="small">
                                        <div><?= htmlspecialchars($ap['requested_date'] ?? '—') ?></div>
                                        <div class="text-muted"><?= htmlspecialchars(substr((string) $ap['start_time'], 0, 5) . ' – ' . substr((string) $ap['end_time'], 0, 5)) ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($ap['location'] ?? '—') ?></td>
                                    <td class="small" style="max-width:180px;"><?= htmlspecialchars($ap['message'] ?? '—') ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = $ap['status'] === 'Approved' ? 'text-bg-success'
                                            : ($ap['status'] === 'Rejected' ? 'text-bg-danger' : 'text-bg-warning');
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($ap['status']) ?></span>
                                        <?php if ($ap['reviewed_at']): ?>
                                            <div class="small text-muted mt-1"><?= htmlspecialchars($ap['reviewer_name'] ?? '') ?> &middot; <?= date('d M Y', strtotime($ap['reviewed_at'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small" style="max-width:200px;">
                                        <?php if ($ap['status'] === 'Pending'): ?>
                                            <form method="post" class="d-flex flex-column gap-2">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="application_id" value="<?= (int) $ap['application_id'] ?>">
                                                <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="Remarks (optional)">
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-success btn-sm flex-fill"><i class="fas fa-check"></i> Approve</button>
                                            </form>
                                            <form method="post" class="d-flex flex-column gap-2">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="application_id" value="<?= (int) $ap['application_id'] ?>">
                                                <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="Reason (required for reject)">
                                                <button type="submit" class="btn btn-outline-danger btn-sm flex-fill"><i class="fas fa-times"></i> Reject</button>
                                            </form>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted"><?= htmlspecialchars($ap['review_remarks'] ?? '—') ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
