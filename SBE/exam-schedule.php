<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Exam Schedule';
$activePage = 'exam_schedule';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM exam_schedule WHERE schedule_id = :id');
        $stmt->execute([':id' => (int) $_POST['schedule_id']]);
        $_SESSION['message'] = 'Schedule removed.';
        redirect('exam-schedule.php');
    }

    $payload = [
        'exam_id'                        => (int) $_POST['exam_id'],
        'class_id'                       => (int) $_POST['class_id'],
        'exam_date'                      => $_POST['exam_date'],
        'start_time'                     => $_POST['start_time'],
        'end_time'                       => $_POST['end_time'],
        'late_submission_grace_minutes'  => $_POST['late_submission_grace_minutes'] === '' ? null : (int) $_POST['late_submission_grace_minutes'],
        'location'                       => trim((string) $_POST['location']),
        'remarks'                        => trim((string) $_POST['remarks']),
        'status'                         => (string) $_POST['status'],
    ];

    if (!empty($_POST['schedule_id'])) {
        $payload['schedule_id'] = (int) $_POST['schedule_id'];
        $stmt = $db->prepare('UPDATE exam_schedule SET exam_id = :exam_id, class_id = :class_id, exam_date = :exam_date, start_time = :start_time, end_time = :end_time, late_submission_grace_minutes = :late_submission_grace_minutes, location = :location, remarks = :remarks, status = :status WHERE schedule_id = :schedule_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Schedule updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO exam_schedule (exam_id, class_id, exam_date, start_time, end_time, late_submission_grace_minutes, location, remarks, status) VALUES (:exam_id, :class_id, :exam_date, :start_time, :end_time, :late_submission_grace_minutes, :location, :remarks, :status)');
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
    'class_id'                       => '',
    'exam_date'                      => date('Y-m-d'),
    'start_time'                     => '09:00:00',
    'end_time'                       => '10:00:00',
    'late_submission_grace_minutes'  => '',
    'location'                       => '',
    'remarks'                        => '',
    'status'                         => 'scheduled',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM exam_schedule WHERE schedule_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$exams        = $db->query('SELECT exam_id, exam_code, title, selection_mode FROM exams ORDER BY exam_id DESC')->fetchAll();
$schedules    = $db->query('SELECT es.*, e.exam_code, e.title AS exam_title FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id ORDER BY es.schedule_id DESC LIMIT 50')->fetchAll();
$statusCounts = $db->query('SELECT status, COUNT(*) AS total FROM exam_schedule GROUP BY status')->fetchAll();
$classCounts  = $db->query('SELECT class_id, COUNT(*) AS total FROM exam_schedule GROUP BY class_id ORDER BY total DESC LIMIT 5')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Exam Schedule</h2>
            <p>Manage class-specific delivery windows, locations, and schedule lifecycle.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exams.php">← Exams</a>
            <a class="btn btn-primary" href="exam-questions.php">Manual Mapping</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <!-- ── Form ── -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['schedule_id'] ? 'Edit Schedule' : 'Add Schedule' ?></h3>
            <p class="small" style="margin:0 0 4px;">Class IDs reference SSO — no FK constraints during initial development.</p>

            <form method="post">
                <input type="hidden" name="schedule_id" value="<?= e((string) old($form, 'schedule_id', '')) ?>">

                <!-- Group: Exam & Class -->
                <div class="form-group-title">📝 Exam & Class</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Exam</label>
                        <select name="exam_id" required>
                            <option value="">Select exam</option>
                            <?php foreach ($exams as $exam): ?>
                                <option value="<?= (int) $exam['exam_id'] ?>" <?= (string) old($form, 'exam_id') === (string) $exam['exam_id'] ? 'selected' : '' ?>>
                                    <?= e($exam['exam_code']) ?> — <?= e($exam['title']) ?> (<?= e($exam['selection_mode']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Class ID</label>
                        <input type="number" name="class_id" required value="<?= e((string) old($form, 'class_id')) ?>" placeholder="e.g. 1001">
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['scheduled', 'ongoing', 'completed', 'cancelled'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Group: Timing -->
                <div class="form-group-title">🕐 Date & Time</div>
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

                <!-- Group: Location -->
                <div class="form-group-title">📍 Location & Notes</div>
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

        <!-- ── Stats card ── -->
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
                                <td><span class="badge <?= e($count['status']) ?>"><?= e($count['status']) ?></span></td>
                                <td class="fw-700"><?= (int) $count['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr class="divider">

            <h3 style="margin:0 0 16px;">Top Class Batches</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Class ID</th><th>Schedules</th></tr></thead>
                    <tbody>
                    <?php if (empty($classCounts)): ?>
                        <tr><td colspan="2"><div class="empty-state"><p>No data.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($classCounts as $count): ?>
                            <tr>
                                <td><span class="badge manual">Class <?= (int) $count['class_id'] ?></span></td>
                                <td class="fw-700"><?= (int) $count['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Schedule Registry -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Schedule Registry</h3>
        <p class="small" style="margin:0 0 16px;">All class-specific schedules in <strong>university_sbe</strong>.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Class</th>
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
                            <span class="empty-icon">📭</span>
                            <p>No schedules yet. Add one using the form.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td>
                                <span class="badge manual"><?= e($schedule['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($schedule['exam_title']) ?></div>
                            </td>
                            <td class="small">Class <?= (int) $schedule['class_id'] ?></td>
                            <td class="small"><?= e($schedule['exam_date']) ?></td>
                            <td class="small"><?= e(substr((string) $schedule['start_time'], 0, 5)) ?> – <?= e(substr((string) $schedule['end_time'], 0, 5)) ?></td>
                            <td class="small"><?= e($schedule['location']) ?></td>
                            <td><span class="badge <?= e($schedule['status']) ?>"><?= e($schedule['status']) ?></span></td>
                            <td>
                                <div class="actions">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
