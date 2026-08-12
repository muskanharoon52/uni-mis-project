<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Schedule Exam';
$activePage = 'exam_schedule';
$db = db();
$teacher = current_user();
$teacherId = (int) ($teacher['teacher_id'] ?? 0);

if ($teacherId === 0) {
    $fallbackTeacher = $db->query("SELECT teacher_id FROM teachers WHERE status = 'Active' LIMIT 1")->fetch();
    if ($fallbackTeacher) {
        $teacherId = (int) $fallbackTeacher['teacher_id'];
    }
}

$schedulableTypes = ['Quiz', 'Assignment Test', 'Practice'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'apply';

    if ($action === 'delete_application') {
        $applicationId = (int) $_POST['application_id'];
        $db->prepare('DELETE FROM sbe_applications WHERE application_id = :id AND teacher_id = :tid AND status IN (\'Pending\', \'Rejected\')')
            ->execute([':id' => $applicationId, ':tid' => $teacherId]);
        $_SESSION['message'] = 'Application removed.';
        redirect('schedule.php');
    }

    if ($action === 'delete_schedule') {
        $db->prepare('DELETE FROM sbe_exam_schedule WHERE schedule_id = :id')->execute([':id' => (int) $_POST['schedule_id']]);
        $_SESSION['message'] = 'Schedule removed.';
        redirect('schedule.php');
    }

    if ($action === 'start_exam') {
        $sid = (int) $_POST['schedule_id'];
        $checkStmt = $db->prepare('SELECT exam_id FROM sbe_exam_schedule WHERE schedule_id = :id');
        $checkStmt->execute([':id' => $sid]);
        $check = $checkStmt->fetch();
        if ($check) {
            $db->prepare("UPDATE sbe_exam_schedule SET status = 'Ongoing' WHERE schedule_id = :id")->execute([':id' => $sid]);
            $db->prepare("UPDATE sbe_exams SET status = 'Published' WHERE exam_id = :eid AND status != 'Published'")->execute([':eid' => (int) $check['exam_id']]);
            $_SESSION['message'] = 'Exam started! Students can now see and take this exam.';
        }
        redirect('schedule.php');
    }

    if ($action === 'stop_exam') {
        $db->prepare("UPDATE sbe_exam_schedule SET status = 'Scheduled' WHERE schedule_id = :id")->execute([':id' => (int) $_POST['schedule_id']]);
        $_SESSION['message'] = 'Exam stopped. Students can no longer start new attempts.';
        redirect('schedule.php');
    }

    if ($action === 'apply') {
        $examId     = (int) $_POST['exam_id'];
        $section    = trim((string) ($_POST['section'] ?? ''));
        $semesterId = (int) ($_POST['semester_id'] ?? 0);
        $examDate   = (string) ($_POST['exam_date'] ?? '');
        $startTime  = (string) ($_POST['start_time'] ?? '');
        $endTime    = (string) ($_POST['end_time'] ?? '');
        $location   = trim((string) ($_POST['location'] ?? ''));
        $message    = trim((string) ($_POST['message'] ?? ''));

        if ($examId <= 0 || $semesterId <= 0 || $section === '' || $examDate === '' || $startTime === '' || $endTime === '' || $location === '') {
            $_SESSION['message'] = 'Exam, section, semester, date, time window and location are required.';
            redirect('schedule.php');
        }

        $examStmt = $db->prepare('SELECT e.exam_id, e.exam_code, e.title, e.exam_type, e.duration_minutes, e.status, (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS q_count FROM sbe_exams e WHERE e.exam_id = :id');
        $examStmt->execute([':id' => $examId]);
        $exam = $examStmt->fetch();

        if (!$exam || !in_array($exam['exam_type'], $schedulableTypes, true)) {
            $_SESSION['message'] = 'This exam cannot be scheduled. Only Quiz, Assignment Test and Practice exams can be scheduled.';
            redirect('schedule.php');
        }

        if ((int) $exam['q_count'] === 0) {
            $_SESSION['message'] = 'Cannot schedule — this exam has no questions yet. Add questions to the exam first.';
            redirect('schedule.php');
        }

        $existingStmt = $db->prepare('SELECT application_id, status FROM sbe_applications WHERE exam_id = :id');
        $existingStmt->execute([':id' => $examId]);
        $existing = $existingStmt->fetch();

        if ($existing && $existing['status'] === 'Approved') {
            $_SESSION['message'] = 'This exam is already approved and scheduled.';
            redirect('schedule.php');
        }

        $applicationPayload = [
            ':exam_id'       => $examId,
            ':semester_id'   => $semesterId,
            ':teacher_id'    => $teacherId,
            ':requested_date'=> $examDate,
            ':start_time'    => $startTime,
            ':end_time'      => $endTime,
            ':location'      => $location,
            ':message'       => mb_strimwidth($message, 0, 500, '...'),
        ];

        if ($existing) {
            $updatePayload = $applicationPayload;
            unset($updatePayload[':exam_id']);
            $updatePayload[':application_id'] = (int) $existing['application_id'];
            $db->prepare("UPDATE sbe_applications SET semester_id = :semester_id, teacher_id = :teacher_id, requested_date = :requested_date, start_time = :start_time, end_time = :end_time, location = :location, message = :message, status = 'Pending', review_remarks = NULL, reviewed_by = NULL, reviewed_at = NULL WHERE application_id = :application_id")
                ->execute($updatePayload);
        } else {
            $db->prepare("INSERT INTO sbe_applications (exam_id, semester_id, teacher_id, requested_date, start_time, end_time, location, message, status) VALUES (:exam_id, :semester_id, :teacher_id, :requested_date, :start_time, :end_time, :location, :message, 'Pending')")
                ->execute($applicationPayload);
        }

        $_SESSION['message'] = 'Schedule application submitted to SSO for review. Students will be notified once it is approved.';
        redirect('schedule.php');
    }
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if ($teacherId > 0) {
    $teacherFilter = ' AND e.teacher_id = ' . $teacherId;
} else {
    $teacherFilter = '';
}

$exams = $db->query('SELECT e.exam_id, e.exam_code, e.title, e.exam_type, e.duration_minutes, e.status, s.section_name, c.course_code, c.course_title, (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = e.exam_id) AS q_count, (SELECT COUNT(*) FROM sbe_applications ap WHERE ap.exam_id = e.exam_id AND ap.status = \'Approved\') AS approved FROM sbe_exams e LEFT JOIN sections s ON s.section_id = e.section_id LEFT JOIN courses c ON c.course_id = e.course_id WHERE e.exam_type IN (\'Quiz\', \'Assignment Test\', \'Practice\')' . $teacherFilter . ' ORDER BY e.exam_id DESC LIMIT 100')->fetchAll();

$semesters = $db->query('SELECT semester_id, semester_name, semester_number FROM semesters ORDER BY semester_number ASC')->fetchAll();

$applications = $db->query('SELECT ap.*, e.exam_code, e.title AS exam_title, e.exam_type, s.section_name FROM sbe_applications ap INNER JOIN sbe_exams e ON e.exam_id = ap.exam_id LEFT JOIN sections s ON s.section_id = e.section_id WHERE ap.teacher_id = ' . $teacherId . ' ORDER BY ap.created_at DESC LIMIT 50')->fetchAll();

$schedules = $db->query('SELECT es.*, e.exam_code, e.title AS exam_title FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE es.exam_id IN (SELECT exam_id FROM sbe_exams' . ($teacherFilter ? ' WHERE teacher_id = ' . $teacherId : '') . ') ORDER BY es.schedule_id DESC LIMIT 50')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Schedule Exams</h2>
            <p>Choose a Quiz, Assignment Test or Practice exam, pick a date and time window, then submit an application to SSO for approval. Approved schedules show below and students are notified automatically.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exams.php">&larr; Exams</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;">Send Application to SSO</h3>
            <p class="small" style="margin:0 0 4px;">Only <strong>Quiz</strong>, <strong>Assignment Test</strong> and <strong>Practice</strong> exams can be scheduled. Mid and Final exams are managed by the examination section.</p>

            <form method="post" style="margin-top:16px;">
                <input type="hidden" name="action" value="apply">

                <div class="form-group-title">Exam & Class</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Exam</label>
                        <select name="exam_id" id="schedule_exam_id" required data-durations='<?= e(json_encode(array_column($exams, 'duration_minutes', 'exam_id'))) ?>' data-sections='<?= e(json_encode(array_column($exams, 'section_name', 'exam_id'))) ?>'>
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= (int) $exam['exam_id'] ?>" <?= (int) $exam['approved'] === 1 ? 'disabled' : '' ?>>
                                    <?= e($exam['exam_code']) ?> &mdash; <?= e(mb_strimwidth($exam['title'], 0, 40, '...')) ?> (<?= e($exam['exam_type']) ?>)<?= (int) $exam['approved'] === 1 ? ' &mdash; already scheduled' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="small" style="margin-top:4px;">Exams without questions or already approved are not selectable.</p>
                    </div>
                    <div class="field">
                        <label>Section</label>
                        <input type="text" name="section" id="schedule_section" required placeholder="e.g. A">
                    </div>
                    <div class="field">
                        <label>Semester</label>
                        <select name="semester_id" required>
                            <option value="">Select semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= (int) $sem['semester_id'] ?>">
                                    <?= e($sem['semester_name']) ?> (Sem <?= (int) $sem['semester_number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-title">Date & Time</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Exam Date</label>
                        <input type="date" name="exam_date" required value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
                    </div>
                    <div class="field">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="schedule_start_time" required value="09:00">
                    </div>
                    <div class="field">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="schedule_end_time" required value="10:00">
                    </div>
                    <div class="field">
                        <label>Location</label>
                        <input type="text" name="location" required placeholder="e.g. Lab A-1">
                    </div>
                </div>

                <div class="form-group-title">Message to SSO</div>
                <div class="field">
                    <textarea name="message" style="min-height:70px;" placeholder="Optional note for the examiner about this schedule request..."></textarea>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit">Send Application to SSO</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin:0 0 16px;">How it works</h3>
            <ol style="margin:0; padding-left:20px; line-height:2; font-size:.9rem; color:var(--text-strong);">
                <li>Pick a schedulable exam and set the date, time window, section and location.</li>
                <li>Click <strong>Send Application to SSO</strong>.</li>
                <li>SSO reviews the application in the <strong>SBE Applications</strong> module.</li>
                <li>On approval the exam is scheduled, its status is set to <strong>Published</strong>, and <strong>students are notified</strong> automatically.</li>
            </ol>
            <hr class="divider">
            <h3 style="margin:0 0 10px; font-size:.85rem; color:var(--text-muted);">Quick Links</h3>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <a href="exam-questions.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128450; Add / Manage Questions</a>
                <a href="exams.php" class="btn btn-ghost" style="justify-content:flex-start;">&#128221; Exam Registry</a>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">My Applications</h3>
        <p class="small" style="margin:0 0 16px;">Schedule requests you have sent to SSO for review.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Time Window</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>SSO Remarks</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($applications)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <span class="empty-icon">&#128197;</span>
                            <p>No applications yet. Use the form to send one.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <span class="badge badge-manual"><?= e($app['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($app['exam_title']) ?></div>
                            </td>
                            <td class="small"><?= e($app['section_name'] ?? '—') ?></td>
                            <td class="small"><?= e($app['requested_date'] ?? '—') ?></td>
                            <td class="small"><?= e(substr((string) $app['start_time'], 0, 5)) ?> &ndash; <?= e(substr((string) $app['end_time'], 0, 5)) ?></td>
                            <td class="small"><?= e($app['location'] ?? '—') ?></td>
                            <td><span class="badge badge-<?= e(strtolower($app['status'])) ?>"><?= e($app['status']) ?></span></td>
                            <td class="small"><?= e($app['review_remarks'] ?? '—') ?></td>
                            <td>
                                <?php if ($app['status'] === 'Pending' || $app['status'] === 'Rejected'): ?>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Remove this application?');">
                                        <input type="hidden" name="action" value="delete_application">
                                        <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Scheduled Exams</h3>
        <p class="small" style="margin:0 0 16px;">Approved schedules. Use Start Exam to open the exam for students.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Time Window</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <span class="empty-icon">&#128233;</span>
                            <p>No approved schedules yet. Send an application above.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td>
                                <span class="badge badge-manual"><?= e($schedule['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($schedule['exam_title']) ?></div>
                            </td>
                            <td class="small"><?= e($schedule['section']) ?></td>
                            <td class="small"><?= e($schedule['exam_date']) ?></td>
                            <td class="small"><?= e(substr((string) $schedule['start_time'], 0, 5)) ?> &ndash; <?= e(substr((string) $schedule['end_time'], 0, 5)) ?></td>
                            <td class="small"><?= e($schedule['location']) ?></td>
                            <td><span class="badge badge-<?= e(strtolower($schedule['status'])) ?>"><?= e($schedule['status']) ?></span></td>
                            <td>
                                <div class="actions" style="gap:4px;">
                                    <?php if ($schedule['status'] === 'Scheduled'): ?>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="start_exam">
                                            <input type="hidden" name="schedule_id" value="<?= (int) $schedule['schedule_id'] ?>">
                                            <button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('Start this exam? Students will be able to begin their attempts.');">Start Exam</button>
                                        </form>
                                    <?php elseif ($schedule['status'] === 'Ongoing'): ?>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="stop_exam">
                                            <input type="hidden" name="schedule_id" value="<?= (int) $schedule['schedule_id'] ?>">
                                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Stop this exam? Students will no longer be able to start new attempts.');">Stop Exam</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this schedule?');">
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $schedule['schedule_id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
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
document.addEventListener('DOMContentLoaded', function() {
    var examSel = document.getElementById('schedule_exam_id');
    var sectionIn = document.getElementById('schedule_section');
    var startTime = document.getElementById('schedule_start_time');
    var endTime = document.getElementById('schedule_end_time');
    if (!examSel || !startTime || !endTime) return;
    var durations = {};
    var sections = {};
    try { durations = JSON.parse(examSel.dataset.durations || '{}'); } catch(e) {}
    try { sections = JSON.parse(examSel.dataset.sections || '{}'); } catch(e) {}

    function onExamChange() {
        var examId = examSel.value;
        if (sectionIn && sections[examId]) sectionIn.value = sections[examId];
        calcEndTime();
    }

    function calcEndTime() {
        var examId = examSel.value;
        var dur = durations[examId];
        if (!dur || !startTime.value) return;
        var parts = startTime.value.split(':');
        var mins = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10) + parseInt(dur, 10);
        var h = Math.floor(mins / 60) % 24;
        var m = mins % 60;
        endTime.value = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }
    examSel.addEventListener('change', onExamChange);
    startTime.addEventListener('change', calcEndTime);
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
