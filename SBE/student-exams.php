<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Student Exams';
$activePage = 'student_exams';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM student_exams WHERE student_exam_id = :id');
        $stmt->execute([':id' => (int) $_POST['student_exam_id']]);
        $_SESSION['message'] = 'Attempt removed.';
        redirect('student-exams.php');
    }

    $payload = [
        'schedule_id'        => (int) $_POST['schedule_id'],
        'exam_id'            => (int) $_POST['exam_id'],
        'student_id'         => (int) $_POST['student_id'],
        'attempt_no'         => (int) $_POST['attempt_no'],
        'status'             => (string) $_POST['status'],
        'started_at'         => $_POST['started_at'],
        'expires_at'         => $_POST['expires_at'],
        'submitted_at'       => $_POST['submitted_at'] ?: null,
        'time_taken_seconds' => $_POST['time_taken_seconds'] === '' ? null : (int) $_POST['time_taken_seconds'],
        'obtained_marks'     => $_POST['obtained_marks'] === '' ? null : (float) $_POST['obtained_marks'],
        'percentage'         => $_POST['percentage'] === '' ? null : (float) $_POST['percentage'],
        'pass_fail_status'   => $_POST['pass_fail_status'] ?: null,
    ];

    if (!empty($_POST['student_exam_id'])) {
        $payload['student_exam_id'] = (int) $_POST['student_exam_id'];
        $stmt = $db->prepare('UPDATE student_exams SET schedule_id = :schedule_id, exam_id = :exam_id, student_id = :student_id, attempt_no = :attempt_no, status = :status, started_at = :started_at, expires_at = :expires_at, submitted_at = :submitted_at, time_taken_seconds = :time_taken_seconds, obtained_marks = :obtained_marks, percentage = :percentage, pass_fail_status = :pass_fail_status WHERE student_exam_id = :student_exam_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Attempt updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO student_exams (schedule_id, exam_id, student_id, attempt_no, status, started_at, expires_at, submitted_at, time_taken_seconds, obtained_marks, percentage, pass_fail_status) VALUES (:schedule_id, :exam_id, :student_id, :attempt_no, :status, :started_at, :expires_at, :submitted_at, :time_taken_seconds, :obtained_marks, :percentage, :pass_fail_status)');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Attempt created successfully.';
    }

    redirect('student-exams.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'student_exam_id'    => null,
    'schedule_id'        => '',
    'exam_id'            => '',
    'student_id'         => '',
    'attempt_no'         => '1',
    'status'             => 'in_progress',
    'started_at'         => date('Y-m-d H:i:s'),
    'expires_at'         => date('Y-m-d H:i:s', time() + 3600),
    'submitted_at'       => '',
    'time_taken_seconds' => '',
    'obtained_marks'     => '',
    'percentage'         => '',
    'pass_fail_status'   => '',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM student_exams WHERE student_exam_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$attempts     = $db->query('SELECT * FROM student_exams ORDER BY student_exam_id DESC LIMIT 50')->fetchAll();
$schedules    = $db->query('SELECT es.schedule_id, es.exam_id, e.exam_code, es.class_id, es.exam_date FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id ORDER BY es.schedule_id DESC')->fetchAll();
$statusCounts = $db->query('SELECT status, COUNT(*) AS total FROM student_exams GROUP BY status')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Student Exams</h2>
            <p>Track every attempt, auto-submit deadline, score snapshot, and result state.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exam-schedule.php">Schedules</a>
            <a class="btn btn-primary" href="student-answers.php">Answer Details</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <!-- ── Form ── -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['student_exam_id'] ? 'Edit Attempt' : 'Create Attempt' ?></h3>
            <p class="small" style="margin:0 0 4px;">Admin-level attempt management.</p>

            <form method="post">
                <input type="hidden" name="student_exam_id" value="<?= e((string) old($form, 'student_exam_id', '')) ?>">

                <!-- Group: Identity -->
                <div class="form-group-title">🎓 Identity</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Schedule</label>
                        <select name="schedule_id" required>
                            <option value="">Select schedule</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?= (int) $schedule['schedule_id'] ?>" <?= (string) old($form, 'schedule_id') === (string) $schedule['schedule_id'] ? 'selected' : '' ?>>
                                    #<?= (int) $schedule['schedule_id'] ?> · <?= e($schedule['exam_code']) ?> · Class <?= (int) $schedule['class_id'] ?> · <?= e($schedule['exam_date']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Exam ID</label>
                        <input type="number" name="exam_id" required value="<?= e((string) old($form, 'exam_id')) ?>">
                    </div>
                    <div class="field">
                        <label>Student ID</label>
                        <input type="number" name="student_id" required value="<?= e((string) old($form, 'student_id')) ?>">
                    </div>
                    <div class="field">
                        <label>Attempt No.</label>
                        <input type="number" min="1" name="attempt_no" required value="<?= e((string) old($form, 'attempt_no')) ?>">
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['in_progress', 'submitted', 'auto_submitted', 'expired', 'cancelled'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Pass/Fail</label>
                        <select name="pass_fail_status">
                            <option value="">— Not yet —</option>
                            <option value="pass" <?= old($form, 'pass_fail_status') === 'pass' ? 'selected' : '' ?>>pass</option>
                            <option value="fail" <?= old($form, 'pass_fail_status') === 'fail' ? 'selected' : '' ?>>fail</option>
                        </select>
                    </div>
                </div>

                <!-- Group: Timing -->
                <div class="form-group-title">🕐 Timing</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Started At</label>
                        <input type="datetime-local" name="started_at" required value="<?= e(str_replace(' ', 'T', substr((string) old($form, 'started_at'), 0, 16))) ?>">
                    </div>
                    <div class="field">
                        <label>Expires At</label>
                        <input type="datetime-local" name="expires_at" required value="<?= e(str_replace(' ', 'T', substr((string) old($form, 'expires_at'), 0, 16))) ?>">
                    </div>
                    <div class="field">
                        <label>Submitted At</label>
                        <input type="datetime-local" name="submitted_at" value="<?= e((string) old($form, 'submitted_at') ? str_replace(' ', 'T', substr((string) old($form, 'submitted_at'), 0, 16)) : '') ?>">
                    </div>
                    <div class="field">
                        <label>Time Taken (seconds)</label>
                        <input type="number" min="0" name="time_taken_seconds" value="<?= e((string) old($form, 'time_taken_seconds')) ?>" placeholder="e.g. 1800">
                    </div>
                </div>

                <!-- Group: Results -->
                <div class="form-group-title">📊 Results</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Obtained Marks</label>
                        <input type="number" step="0.01" min="0" name="obtained_marks" value="<?= e((string) old($form, 'obtained_marks')) ?>">
                    </div>
                    <div class="field">
                        <label>Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="percentage" value="<?= e((string) old($form, 'percentage')) ?>">
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['student_exam_id'] ? 'Update Attempt' : 'Save Attempt' ?></button>
                    <?php if ($form['student_exam_id']): ?>
                        <a class="btn btn-ghost" href="student-exams.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── Status distribution ── -->
        <div class="card">
            <h3 style="margin:0 0 16px;">Status Distribution</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($statusCounts)): ?>
                        <tr><td colspan="2"><div class="empty-state"><p>No attempts yet.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($statusCounts as $count): ?>
                            <tr>
                                <td><span class="badge <?= e($count['status']) ?>"><?= e($count['status']) ?></span></td>
                                <td class="fw-700"><?= (int) $count['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Attempt Registry -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Attempt Registry</h3>
        <p class="small" style="margin:0 0 16px;">All student attempts stored in <strong>university_sbe</strong>.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Schedule</th>
                        <th>Exam ID</th>
                        <th>Student ID</th>
                        <th>Status</th>
                        <th>Marks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($attempts)): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <span class="empty-icon">📭</span>
                            <p>No attempts yet.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($attempts as $attempt): ?>
                        <tr>
                            <td class="small">#<?= (int) $attempt['attempt_no'] ?></td>
                            <td class="small">Sched #<?= (int) $attempt['schedule_id'] ?></td>
                            <td class="small">Exam #<?= (int) $attempt['exam_id'] ?></td>
                            <td class="small">Student #<?= (int) $attempt['student_id'] ?></td>
                            <td><span class="badge <?= e($attempt['status']) ?>"><?= e($attempt['status']) ?></span></td>
                            <td class="fw-700"><?= e(number_format((float) ($attempt['obtained_marks'] ?? 0), 2)) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $attempt['student_exam_id'] ?>">Edit</a>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this attempt?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_exam_id" value="<?= (int) $attempt['student_exam_id'] ?>">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
