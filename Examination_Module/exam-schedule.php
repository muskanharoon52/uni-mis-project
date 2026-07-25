<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM sbe_exam_schedule WHERE schedule_id = :id');
        $stmt->execute([':id' => (int) $_POST['schedule_id']]);
        $_SESSION['message'] = 'Schedule removed.';
        redirect('exam-schedule.php');
    }

    if ($action === 'start_exam') {
        $sid = (int) $_POST['schedule_id'];
        $checkStmt = $db->prepare('SELECT es.exam_id, (SELECT COUNT(*) FROM sbe_exam_questions eq WHERE eq.exam_id = es.exam_id) AS q_count FROM sbe_exam_schedule es WHERE es.schedule_id = :id');
        $checkStmt->execute([':id' => $sid]);
        $check = $checkStmt->fetch();
        if (!$check || (int) $check['q_count'] === 0) {
            $_SESSION['message'] = 'Cannot start — this exam has no questions mapped. Ask the teacher to map questions first.';
            redirect('exam-schedule.php');
        }
        $db->prepare("UPDATE sbe_exam_schedule SET status = 'Ongoing' WHERE schedule_id = :id")->execute([':id' => $sid]);
        $db->prepare("UPDATE sbe_exams SET status = 'Published' WHERE exam_id = :eid AND status != 'Published'")->execute([':eid' => (int) $check['exam_id']]);
        $_SESSION['message'] = 'Exam started! Students can now see and take this exam.';
        redirect('exam-schedule.php');
    }

    if ($action === 'stop_exam') {
        $sid = (int) $_POST['schedule_id'];
        $db->prepare("UPDATE sbe_exam_schedule SET status = 'Scheduled' WHERE schedule_id = :id")->execute([':id' => $sid]);
        $_SESSION['message'] = 'Exam stopped. Students can no longer start new attempts.';
        redirect('exam-schedule.php');
    }

    $payload = [
        'exam_id'                        => (int) $_POST['exam_id'],
        'section'                        => trim((string) $_POST['section']),
        'semester_id'                    => (int) $_POST['semester_id'],
        'exam_date'                      => $_POST['exam_date'],
        'start_time'                     => $_POST['start_time'],
        'end_time'                       => $_POST['end_time'],
        'late_submission_grace_minutes'  => $_POST['late_submission_grace_minutes'] === '' ? 0 : (int) $_POST['late_submission_grace_minutes'],
        'location'                       => trim((string) $_POST['location']),
        'remarks'                        => trim((string) $_POST['remarks']),
        'status'                         => (string) $_POST['status'],
    ];

    if (!empty($_POST['schedule_id'])) {
        $payload['schedule_id'] = (int) $_POST['schedule_id'];
        $stmt = $db->prepare('UPDATE sbe_exam_schedule SET exam_id = :exam_id, section = :section, semester_id = :semester_id, exam_date = :exam_date, start_time = :start_time, end_time = :end_time, late_submission_grace_minutes = :late_submission_grace_minutes, location = :location, remarks = :remarks, status = :status WHERE schedule_id = :schedule_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Schedule updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO sbe_exam_schedule (exam_id, section, semester_id, exam_date, start_time, end_time, late_submission_grace_minutes, location, remarks, status) VALUES (:exam_id, :section, :semester_id, :exam_date, :start_time, :end_time, :late_submission_grace_minutes, :location, :remarks, :status)');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Schedule added successfully.';
    }

    redirect('exam-schedule.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'schedule_id'                    => null,
    'exam_id'                        => '',
    'section'                        => '',
    'semester_id'                    => '',
    'exam_date'                      => date('Y-m-d'),
    'start_time'                     => '09:00:00',
    'end_time'                       => '10:00:00',
    'late_submission_grace_minutes'  => '',
    'location'                       => '',
    'remarks'                        => '',
    'status'                         => 'Scheduled',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM sbe_exam_schedule WHERE schedule_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$exams        = $db->query('SELECT exam_id, exam_code, title, selection_mode, duration_minutes FROM sbe_exams ORDER BY exam_id DESC')->fetchAll();
$semesters    = $db->query('SELECT MIN(semester_id) AS semester_id, semester_name, semester_number FROM semesters GROUP BY semester_number, semester_name ORDER BY semester_number ASC')->fetchAll();
$schedules    = $db->query('SELECT es.*, e.exam_code, e.title AS exam_title FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id ORDER BY es.schedule_id DESC LIMIT 50')->fetchAll();
$statusCounts = $db->query('SELECT status, COUNT(*) AS total FROM sbe_exam_schedule GROUP BY status')->fetchAll();
$sectionCounts = $db->query('SELECT section, COUNT(*) AS total FROM sbe_exam_schedule GROUP BY section ORDER BY total DESC LIMIT 5')->fetchAll();

$pageTitle = 'Exam Schedule';
$activePage = 'exam_schedule';
require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Exam Schedule</h2>
            <p>Manage class-specific delivery windows, locations, and schedule lifecycle.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="view-results.php">&larr; Results</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['schedule_id'] ? 'Edit Schedule' : 'Add Schedule' ?></h3>
            <p class="small" style="margin:0 0 4px;">Section and semester reference your ERP SSO system.</p>

            <form method="post">
                <input type="hidden" name="schedule_id" value="<?= e((string) old($form, 'schedule_id', '')) ?>">

                <div class="form-group-title">Exam & Class</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Exam</label>
                        <select name="exam_id" id="schedule_exam_id" required data-durations='<?= e(json_encode(array_column($exams, 'duration_minutes', 'exam_id'))) ?>'>
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= (int) $exam['exam_id'] ?>" <?= (string) old($form, 'exam_id') === (string) $exam['exam_id'] ? 'selected' : '' ?>>
                                    <?= e($exam['exam_code']) ?> &mdash; <?= e($exam['title']) ?> (<?= e($exam['selection_mode']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Section</label>
                        <input type="text" name="section" required value="<?= e((string) old($form, 'section')) ?>" placeholder="e.g. CS-A">
                    </div>
                    <div class="field">
                        <label>Semester</label>
                        <select name="semester_id" required>
                            <option value="">Select semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= (int) $sem['semester_id'] ?>" <?= (string) old($form, 'semester_id') === (string) $sem['semester_id'] ? 'selected' : '' ?>>
                                    <?= e($sem['semester_name']) ?> (Sem <?= (int) $sem['semester_number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['Scheduled', 'Ongoing', 'Completed', 'Cancelled'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-title">Date & Time</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Exam Date</label>
                        <input type="date" name="exam_date" required value="<?= e((string) old($form, 'exam_date')) ?>">
                    </div>
                    <div class="field">
                        <label>Grace Minutes</label>
                        <input type="number" min="0" name="late_submission_grace_minutes" value="<?= e((string) old($form, 'late_submission_grace_minutes')) ?>" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required value="<?= e(substr((string) old($form, 'start_time'), 0, 8)) ?>">
                    </div>
                    <div class="field">
                        <label>End Time</label>
                        <input type="time" name="end_time" required value="<?= e(substr((string) old($form, 'end_time'), 0, 8)) ?>">
                    </div>
                </div>

                <div class="form-group-title">Location & Notes</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Location</label>
                        <input type="text" name="location" required value="<?= e((string) old($form, 'location')) ?>" placeholder="e.g. Lab A-1">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Remarks <span class="small">(optional)</span></label>
                        <textarea name="remarks" style="min-height:70px;"><?= e((string) old($form, 'remarks')) ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['schedule_id'] ? 'Update Schedule' : 'Save Schedule' ?></button>
                    <?php if ($form['schedule_id']): ?>
                        <a class="btn btn-ghost" href="exam-schedule.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin:0 0 16px;">Status Breakdown</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($statusCounts)): ?>
                        <tr><td colspan="2"><div class="empty-state"><p>No data.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($statusCounts as $count): ?>
                            <tr>
                                <td><span class="badge badge-<?= e(strtolower($count['status'])) ?>"><?= e($count['status']) ?></span></td>
                                <td class="fw-700"><?= (int) $count['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr class="divider">

            <h3 style="margin:0 0 16px;">Top Sections</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Section</th><th>Schedules</th></tr></thead>
                    <tbody>
                    <?php if (empty($sectionCounts)): ?>
                        <tr><td colspan="2"><div class="empty-state"><p>No data.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($sectionCounts as $count): ?>
                            <tr>
                                <td><span class="badge badge-manual"><?= e($count['section']) ?></span></td>
                                <td class="fw-700"><?= (int) $count['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Schedule Registry</h3>
        <p class="small" style="margin:0 0 16px;">All class-specific schedules in <strong>university_mis</strong>.</p>
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
                            <p>No schedules yet. Add one using the form.</p>
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
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $schedule['schedule_id'] ?>">Edit</a>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this schedule?');">
                                        <input type="hidden" name="action" value="delete">
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
    var startTime = document.querySelector('[name="start_time"]');
    var endTime = document.querySelector('[name="end_time"]');
    if (!examSel || !startTime || !endTime) return;
    var durations = {};
    try { durations = JSON.parse(examSel.dataset.durations || '{}'); } catch(e) {}

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
    examSel.addEventListener('change', calcEndTime);
    startTime.addEventListener('change', calcEndTime);
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
