<?php

declare(strict_types=1);

$page_title = 'Exam Schedule';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../notifications/_helpers.php';
require_once __DIR__ . '/../includes/sbe_datesheet_pdf.php';

$conn = getConnection();

$allowedRoles = ['Admin', 'Super Admin', 'Examiner'];
$role = strtolower($_SESSION['role_name'] ?? '');
if (!in_array($role, array_map('strtolower', $allowedRoles), true)) {
    header('Location: ' . BASE_URL . 'examination/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../includes/activity.php';
global $conn;

$error = '';
$success = '';
$preselectedExamId = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'schedule') {
    $examId      = (int) ($_POST['exam_id'] ?? 0);
    $section     = trim((string) ($_POST['section'] ?? ''));
    $semesterId  = (int) ($_POST['semester_id'] ?? 0);
    $examDate    = (string) ($_POST['exam_date'] ?? '');
    $startTime   = (string) ($_POST['start_time'] ?? '');
    $endTime     = (string) ($_POST['end_time'] ?? '');
    $location    = trim((string) ($_POST['location'] ?? ''));
    $remarks     = trim((string) ($_POST['remarks'] ?? ''));
    $invigilatorIds = isset($_POST['invigilators']) && is_array($_POST['invigilators'])
        ? array_map('intval', $_POST['invigilators'])
        : [];

    if ($examId <= 0 || $section === '' || $semesterId <= 0 || $examDate === '' || $startTime === '' || $endTime === '' || $location === '') {
        $error = 'Exam, section, semester, date, time window and location are required.';
    } elseif ($startTime >= $endTime) {
        $error = 'End time must be after start time.';
    } else {
        $examSql = "SELECT e.exam_id, e.exam_code, e.title, e.exam_type, e.batch_year, e.department_id, e.status,
                           (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS q_count
                    FROM sbe_exams e
                    WHERE e.exam_id = " . $examId . " AND e.exam_type IN ('Mid', 'Final') AND e.status != 'Archived'
                    LIMIT 1";
        $examRes = mysqli_query($conn, $examSql);
        $exam = $examRes ? mysqli_fetch_assoc($examRes) : null;

        if (!$exam) {
            $error = 'Exam not found or it is not a Mid/Final exam.';
        } elseif ((int) $exam['q_count'] === 0) {
            $error = 'This exam has no questions yet. Teachers must add questions before it can be scheduled.';
        } else {
            // Existing schedule id (when rescheduling)
            $schRes = mysqli_query($conn, "SELECT schedule_id FROM sbe_exam_schedule WHERE exam_id = " . $examId . " LIMIT 1");
            $existingSchedule = $schRes ? mysqli_fetch_assoc($schRes) : null;
            $scheduleId = $existingSchedule ? (int) $existingSchedule['schedule_id'] : 0;

            // Conflict check: no overlapping exam on the same date/time window.
            $conflictSql = "SELECT es.schedule_id, e.exam_code, e.title AS exam_title, es.exam_date, es.start_time, es.end_time
                            FROM sbe_exam_schedule es
                            JOIN sbe_exams e ON e.exam_id = es.exam_id
                            WHERE es.exam_date = ? AND es.status != 'Cancelled'
                              AND es.start_time < ? AND ? < es.end_time";
            $cParams = [$examDate, $endTime, $startTime];
            if ($scheduleId > 0) {
                $conflictSql .= " AND es.schedule_id != ?";
                $cParams[] = $scheduleId;
            }
            $cStmt = mysqli_prepare($conn, $conflictSql);
            mysqli_stmt_bind_param($cStmt, str_repeat('s', count($cParams)), ...$cParams);
            mysqli_stmt_execute($cStmt);
            $conflicts = [];
            $cRes = mysqli_stmt_get_result($cStmt);
            while ($crow = mysqli_fetch_assoc($cRes)) {
                $conflicts[] = htmlspecialchars($crow['exam_code']) . ' (' . substr((string) $crow['start_time'], 0, 5) . '-' . substr((string) $crow['end_time'], 0, 5) . ')';
            }
            mysqli_stmt_close($cStmt);

            if (!empty($conflicts)) {
                $error = 'Conflict detected! The following exam(s) are already scheduled on ' . $examDate . ' in this time window: ' . implode(', ', $conflicts);
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $remarksForSchedule = $remarks !== '' ? $remarks : 'Scheduled by Examination';

                    if ($existingSchedule) {
                        $stmt = mysqli_prepare($conn, "UPDATE sbe_exam_schedule SET section = ?, semester_id = ?, exam_date = ?, start_time = ?, end_time = ?, late_submission_grace_minutes = 0, location = ?, remarks = ?, status = 'Scheduled' WHERE schedule_id = ?");
                        mysqli_stmt_bind_param($stmt, 'sisssssi', $section, $semesterId, $examDate, $startTime, $endTime, $location, $remarksForSchedule, $scheduleId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    } else {
                        $stmt = mysqli_prepare($conn, "INSERT INTO sbe_exam_schedule (exam_id, section, semester_id, exam_date, start_time, end_time, late_submission_grace_minutes, location, remarks, status) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 'Scheduled')");
                        mysqli_stmt_bind_param($stmt, 'isisssss', $examId, $section, $semesterId, $examDate, $startTime, $endTime, $location, $remarksForSchedule);
                        mysqli_stmt_execute($stmt);
                        $scheduleId = (int) mysqli_insert_id($conn);
                        mysqli_stmt_close($stmt);
                    }

                    // Replace invigilators for this schedule slot.
                    mysqli_query($conn, "DELETE FROM sbe_exam_invigilators WHERE schedule_id = " . $scheduleId);
                    if (!empty($invigilatorIds)) {
                        $validIds = [];
                        foreach ($invigilatorIds as $tid) {
                            if ($tid <= 0) { continue; }
                            $validIds[] = $tid;
                        }
                        if (!empty($validIds)) {
                            $stmtI = mysqli_prepare($conn, "INSERT INTO sbe_exam_invigilators (schedule_id, teacher_id) VALUES (?, ?)");
                            foreach ($validIds as $tid) {
                                mysqli_stmt_bind_param($stmtI, 'ii', $scheduleId, $tid);
                                mysqli_stmt_execute($stmtI);
                            }
                            mysqli_stmt_close($stmtI);
                        }
                    }

                    // Publish the exam so students can take it.
                    mysqli_query($conn, "UPDATE sbe_exams SET status = 'Published' WHERE exam_id = " . $examId . " AND status != 'Published'");

                    // Notify students of the department + batch.
                    $deptId = (int) $exam['department_id'];
                    $batchYear = (int) $exam['batch_year'];
                    $studentIds = notif_student_ids($conn, $deptId ? [$deptId] : [], $batchYear ? [$batchYear] : []);

                    if (!empty($studentIds)) {
                        $title = 'SBE Exam Scheduled - ' . $exam['exam_code'];
                        $timeWindow = substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5);
                        $message = 'Your ' . $exam['exam_type'] . ' exam "' . $exam['title'] . '" is scheduled on ' . $examDate . ' from ' . $timeWindow . ' at ' . $location . '.' . ($remarks !== '' ? ' Note: ' . $remarks : '');

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

                    // Push the updated schedule into the datesheets submodule as a downloadable PDF.
                    $dsPushed = false;
                    if (function_exists('sbe_regenerate_datesheet')) {
                        sbe_regenerate_datesheet($conn, $semesterId, (int) $exam['department_id'], (string) $exam['exam_type']);
                        $dsPushed = true;
                    }

                    $success = 'Exam scheduled. It is now published and ' . count($studentIds) . ' student(s) have been notified.'
                        . ($dsPushed ? ' The datesheet has been updated for PDF download.' : '');
                    log_activity('Examination', 'Exam Scheduled', 'sbe_exams', $examId, 'Exam: ' . $exam['exam_code'] . ' | Date: ' . $examDate . ' | Invigilators: ' . count($invigilatorIds) . ' | Notified: ' . count($studentIds));
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = 'Failed to schedule the exam: ' . $e->getMessage();
                }
            }
        }
    }
}

// ---- Data for the page ----

// All Mid/Final exams with scheduling status.
$exams = [];
$res = $conn->query(
    "SELECT e.exam_id, e.exam_code, e.title AS exam_title, e.exam_type, e.batch_year, e.status, e.department_id,
            d.department_name, s.section_name, c.course_code,
            (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS q_count,
            (SELECT COUNT(*) FROM sbe_exam_schedule es WHERE es.exam_id = e.exam_id) AS schedule_count
     FROM sbe_exams e
     LEFT JOIN departments d ON d.department_id = e.department_id
     LEFT JOIN sections s ON s.section_id = e.section_id
     LEFT JOIN courses c ON c.course_id = e.course_id
     WHERE e.exam_type IN ('Mid', 'Final') AND e.status != 'Archived'
     ORDER BY (schedule_count = 0) DESC, e.exam_id DESC"
);
if ($res) { $exams = $res->fetch_all(MYSQLI_ASSOC); }

// Scheduled slots (with invigilators).
$scheduledSlots = [];
$res = $conn->query(
    "SELECT es.schedule_id, es.exam_id, es.section, es.semester_id, es.exam_date, es.start_time, es.end_time, es.location, es.remarks, es.status,
            e.exam_code, e.title AS exam_title, e.exam_type,
            sem.semester_name,
            (SELECT GROUP_CONCAT(t.teacher_name ORDER BY t.teacher_name SEPARATOR ', ')
             FROM sbe_exam_invigilators inv JOIN teachers t ON t.teacher_id = inv.teacher_id
             WHERE inv.schedule_id = es.schedule_id) AS invigilators
     FROM sbe_exam_schedule es
     JOIN sbe_exams e ON e.exam_id = es.exam_id
     LEFT JOIN semesters sem ON sem.semester_id = es.semester_id
     WHERE e.exam_type IN ('Mid', 'Final')
     ORDER BY es.exam_date ASC, es.start_time ASC"
);
if ($res) { $scheduledSlots = $res->fetch_all(MYSQLI_ASSOC); }

$scheduledExamIds = array_map(fn($s) => (int) $s['exam_id'], $scheduledSlots);
$awaiting = array_values(array_filter($exams, fn($e) => !in_array((int) $e['exam_id'], $scheduledExamIds, true)));

// Semesters + sections for the form.
$semesters = [];
$res = $conn->query("SELECT sem.semester_id, sem.semester_name, sem.department_id, d.department_name FROM semesters sem LEFT JOIN departments d ON d.department_id = sem.department_id ORDER BY sem.semester_number, sem.department_id");
if ($res) { $semesters = $res->fetch_all(MYSQLI_ASSOC); }

$sectionNames = [];
$res = $conn->query("SELECT DISTINCT section_name FROM sections WHERE status = 'Active' ORDER BY section_name");
if ($res) { $sectionNames = array_column($res->fetch_all(MYSQLI_ASSOC), 'section_name'); }

// Preselected exam data (for reschedule / schedule-from-list).
$preselectedScheduleId = 0;
$preselectedInvigilators = [];
$preselectedQCount = 0;
$preselectedExam = null;
if ($preselectedExamId > 0) {
    foreach ($exams as $e) {
        if ((int) $e['exam_id'] === $preselectedExamId) { $preselectedExam = $e; break; }
    }
    if ($preselectedExam) {
        $preselectedQCount = (int) $preselectedExam['q_count'];
        $res = $conn->query("SELECT schedule_id FROM sbe_exam_schedule WHERE exam_id = " . $preselectedExamId . " LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $preselectedScheduleId = (int) $row['schedule_id'];
            $resI = $conn->query("SELECT teacher_id FROM sbe_exam_invigilators WHERE schedule_id = " . $preselectedScheduleId);
            if ($resI) {
                while ($ir = $resI->fetch_assoc()) { $preselectedInvigilators[] = (int) $ir['teacher_id']; }
            }
        }
    }
}

// Generic (non-SBE) course schedules kept for reference.
require_once __DIR__ . '/../models/ExamSchedule.php';
$model = new ExamSchedule();
$genericSchedules = $model->getAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Exam Schedule</h4>
            <p style="color:var(--text-secondary);font-size:13px;margin:2px 0 0;">Mid and Final exams created by teachers flow here. Schedule them, assign free invigilators, and the datesheet is generated automatically for PDF download.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>examination/datesheets/index.php" class="btn btn-outline">
                <i class="bi bi-file-earmark-pdf"></i> Datesheets
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card" id="schedule-form-card">
                <div class="card-header"><h5><i class="bi bi-calendar-plus me-2"></i>Schedule Mid / Final Exam</h5></div>
                <div class="card-body">
                    <form method="post" id="scheduleForm">
                        <input type="hidden" name="action" value="schedule">
                        <div class="mb-3">
                            <label class="form-label">Exam *</label>
                            <select name="exam_id" id="examSelect" class="form-select" required>
                                <option value="">Select Mid / Final exam</option>
                                <?php foreach ($exams as $e): ?>
                                    <?php $already = (int) $e['schedule_count'] > 0; ?>
                                    <option value="<?= (int) $e['exam_id'] ?>"
                                        data-dept="<?= (int) ($e['department_id'] ?? 0) ?>"
                                        data-qcount="<?= (int) $e['q_count'] ?>"
                                        <?= (int) $e['q_count'] === 0 ? 'disabled' : '' ?>
                                        <?= $preselectedExamId === (int) $e['exam_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['exam_code']) ?> &mdash; <?= htmlspecialchars(mb_strimwidth($e['exam_title'], 0, 34, '...')) ?> (<?= htmlspecialchars($e['exam_type']) ?>)<?= (int) $e['q_count'] === 0 ? ' &mdash; no questions' : '' ?><?= $already ? ' &mdash; scheduled' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only Mid and Final exams with questions are schedulable. Already-scheduled exams can be rescheduled.</div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Section *</label>
                                <select name="section" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($sectionNames as $sn): ?>
                                        <option value="<?= htmlspecialchars($sn) ?>"><?= htmlspecialchars($sn) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Semester *</label>
                                <select name="semester_id" id="semesterSelect" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($semesters as $sem): ?>
                                        <option value="<?= (int) $sem['semester_id'] ?>" data-dept="<?= (int) ($sem['department_id'] ?? 0) ?>"><?= htmlspecialchars($sem['semester_name']) ?><?= $sem['department_name'] ? ' &mdash; ' . htmlspecialchars($sem['department_name']) : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="exam_date" id="examDate" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start Time *</label>
                                <input type="time" name="start_time" id="startTime" class="form-control" required value="09:00">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End Time *</label>
                                <input type="time" name="end_time" id="endTime" class="form-control" required value="11:00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-control" required placeholder="e.g. Hall A, Lab 2" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Invigilators <span class="text-muted">(free teachers at this time)</span></label>
                            <div id="invigilatorsList" class="border rounded p-2" style="max-height:180px;overflow:auto;background:var(--bg);">
                                <div class="text-muted small p-2">Choose a date and time window to load free teachers.</div>
                            </div>
                            <div class="form-text" id="invigilatorInfo"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional note shown to students" maxlength="255">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar-check"></i> Save Schedule</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header"><h5><i class="bi bi-journal-text me-2"></i>Scheduled Exams (<?= count($scheduledSlots) ?>)</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($scheduledSlots)): ?>
                        <div class="p-4 text-center text-muted">
                            No Mid or Final exams scheduled yet. Schedule one from the form.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Exam</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                        <th>Location</th>
                                        <th>Invigilators</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scheduledSlots as $s): ?>
                                        <tr>
                                            <td style="white-space:nowrap;">
                                                <strong><?= htmlspecialchars(date('M d, Y', strtotime((string) $s['exam_date']))) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($s['semester_name'] ?? '—') ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= $s['exam_type'] === 'Final' ? 'badge-exam-final' : 'badge-exam-mid' ?>"><?= htmlspecialchars($s['exam_code']) ?></span><br>
                                                <small style="color:var(--text-secondary);"><?= htmlspecialchars(mb_strimwidth((string) $s['exam_title'], 0, 30, '...')) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars((string) $s['exam_type']) ?><br><small class="text-muted">Sec <?= htmlspecialchars($s['section']) ?></small></td>
                                            <td style="white-space:nowrap;"><?= htmlspecialchars(substr((string) $s['start_time'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr((string) $s['end_time'], 0, 5)) ?></td>
                                            <td><?= htmlspecialchars($s['location']) ?></td>
                                            <td><?= $s['invigilators'] ? htmlspecialchars($s['invigilators']) : '<span class="text-muted">None</span>' ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>examination/schedule/index.php?exam_id=<?= (int) $s['exam_id'] ?>">
                                                    <i class="bi bi-pencil"></i> Reschedule
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-hourglass-split me-2"></i>Exams Awaiting Scheduling (<?= count($awaiting) ?>)</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($awaiting)): ?>
                        <div class="p-4 text-center text-muted">
                            All Mid and Final exams are scheduled. New ones created by teachers will appear here.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Type</th>
                                        <th>Department</th>
                                        <th>Batch</th>
                                        <th>Questions</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($awaiting as $e): ?>
                                        <tr>
                                            <td>
                                                <span class="status-badge <?= $e['exam_type'] === 'Final' ? 'badge-exam-final' : 'badge-exam-mid' ?>"><?= htmlspecialchars($e['exam_code']) ?></span><br>
                                                <small style="color:var(--text-secondary);"><?= htmlspecialchars(mb_strimwidth((string) $e['exam_title'], 0, 30, '...')) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($e['exam_type']) ?></td>
                                            <td><?= $e['department_name'] ? htmlspecialchars($e['department_name']) : '—' ?></td>
                                            <td><?= $e['batch_year'] ? htmlspecialchars((string) $e['batch_year']) : '—' ?></td>
                                            <td><strong><?= (int) $e['q_count'] ?></strong></td>
                                            <td class="text-end">
                                                <?php if ((int) $e['q_count'] > 0): ?>
                                                    <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>examination/schedule/index.php?exam_id=<?= (int) $e['exam_id'] ?>">
                                                        <i class="bi bi-calendar-plus"></i> Schedule
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">No questions</span>
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
    </div>

    <?php if (!empty($genericSchedules)): ?>
    <div class="card" style="margin-top:24px;">
        <div class="card-header"><h5><i class="bi bi-calendar3 me-2"></i>Course Exam Schedules (<?= count($genericSchedules) ?>)</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Exam Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($genericSchedules as $gs): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($gs['course_code']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($gs['course_name']) ?></small>
                                </td>
                                <td><span class="status-badge badge-exam-mid"><?= htmlspecialchars(strtoupper((string) $gs['exam_type'])) ?></span></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime((string) $gs['date']))) ?></td>
                                <td><?= htmlspecialchars(substr((string) $gs['start_time'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr((string) $gs['end_time'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars($gs['room']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var examSelect = document.getElementById('examSelect');
    var semSelect = document.getElementById('semesterSelect');
    var dateInput = document.getElementById('examDate');
    var startInput = document.getElementById('startTime');
    var endInput = document.getElementById('endTime');
    var listBox = document.getElementById('invigilatorsList');
    var infoBox = document.getElementById('invigilatorInfo');

    var PRESELECT_SCHEDULE_ID = <?= (int) $preselectedScheduleId ?>;
    var CURRENT_INVIGILATORS = <?= json_encode($preselectedInvigilators) ?>;
    var PRESELECT_HAS_QUESTIONS = <?= $preselectedQCount > 0 ? 'true' : 'false' ?>;

    function filterSemesters() {
        if (!examSelect || !semSelect) { return; }
        var dept = examSelect.value ? (examSelect.options[examSelect.selectedIndex].dataset.dept || '') : '';
        var hasVisible = false;
        for (var i = 0; i < semSelect.options.length; i++) {
            var opt = semSelect.options[i];
            if (!opt.value) { continue; }
            opt.hidden = dept !== '' && opt.dataset.dept !== dept;
            if (!opt.hidden) { hasVisible = true; }
        }
        if (!hasVisible && dept !== '') {
            for (var j = 0; j < semSelect.options.length; j++) {
                var o = semSelect.options[j];
                if (o.value && o.dataset.dept === '') { o.hidden = false; }
            }
        }
    }

    function loadFreeTeachers() {
        if (!dateInput || !startInput || !endInput || !listBox) { return; }
        var date = dateInput.value;
        var start = startInput.value;
        var end = endInput.value;
        if (!date || !start || !end || start >= end) {
            listBox.innerHTML = '<div class="text-muted small p-2">Choose a valid date and time window to see free teachers.</div>';
            infoBox.textContent = '';
            return;
        }
        listBox.innerHTML = '<div class="text-muted small p-2">Loading free teachers...</div>';
        var url = 'free_teachers.php?date=' + encodeURIComponent(date) + '&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end) + '&exclude_schedule_id=' + PRESELECT_SCHEDULE_ID;
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    listBox.innerHTML = '<div class="text-muted small p-2">' + data.error + '</div>';
                    infoBox.textContent = '';
                    return;
                }
                if (!data.count) {
                    listBox.innerHTML = '<div class="text-muted small p-2">No teachers are free at this time.</div>';
                    infoBox.textContent = '0 teachers free';
                    return;
                }
                var html = '';
                data.teachers.forEach(function (t) {
                    var checked = CURRENT_INVIGILATORS.indexOf(t.id) !== -1;
                    html += '<label class="form-check d-block mb-1">'
                        + '<input class="form-check-input" type="checkbox" name="invigilators[]" value="' + t.id + '"' + (checked ? ' checked' : '') + '> '
                        + '<span class="form-check-label">' + t.name
                        + (t.department ? ' <small class="text-muted">(' + t.department + ')</small>' : '')
                        + '</span></label>';
                });
                listBox.innerHTML = html;
                infoBox.textContent = data.count + ' teacher(s) free at this time';
            })
            .catch(function () {
                listBox.innerHTML = '<div class="text-muted small p-2">Failed to load teachers.</div>';
                infoBox.textContent = '';
            });
    }

    if (examSelect) { examSelect.addEventListener('change', filterSemesters); filterSemesters(); }
    [dateInput, startInput, endInput].forEach(function (el) {
        if (el) {
            el.addEventListener('change', loadFreeTeachers);
            el.addEventListener('input', loadFreeTeachers);
        }
    });

    if (PRESELECT_HAS_QUESTIONS) {
        loadFreeTeachers();
        var card = document.getElementById('schedule-form-card');
        if (card) {
            setTimeout(function () { card.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150);
        }
    }

    var form = document.getElementById('scheduleForm');
    if (form) {
        form.addEventListener('submit', function () {
            var s = startInput.value, e = endInput.value;
            if (s && e && s >= e) {
                alert('End time must be after start time!');
                return false;
            }
            return true;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
