<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Exam Results';
$activePage = 'exam_results';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM exam_results WHERE exam_result_id = :id');
        $stmt->execute([':id' => (int) $_POST['exam_result_id']]);
        $_SESSION['message'] = 'Result removed.';
        redirect('exam-results.php');
    }

    $payload = [
        'student_exam_id' => (int) $_POST['student_exam_id'],
        'exam_id'         => (int) $_POST['exam_id'],
        'student_id'      => (int) $_POST['student_id'],
        'obtained_marks'  => (float) $_POST['obtained_marks'],
        'total_marks'     => (float) $_POST['total_marks'],
        'percentage'      => (float) $_POST['percentage'],
        'pass_fail_status'=> (string) $_POST['pass_fail_status'],
        'rank_position'   => $_POST['rank_position'] === '' ? null : (int) $_POST['rank_position'],
        'remarks'         => trim((string) $_POST['remarks']),
        'status'          => (string) $_POST['status'],
        'published_at'    => $_POST['published_at'] ?: null,
    ];

    if (!empty($_POST['exam_result_id'])) {
        $payload['exam_result_id'] = (int) $_POST['exam_result_id'];
        $stmt = $db->prepare('UPDATE exam_results SET student_exam_id = :student_exam_id, exam_id = :exam_id, student_id = :student_id, obtained_marks = :obtained_marks, total_marks = :total_marks, percentage = :percentage, pass_fail_status = :pass_fail_status, rank_position = :rank_position, remarks = :remarks, status = :status, published_at = :published_at WHERE exam_result_id = :exam_result_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Result updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO exam_results (student_exam_id, exam_id, student_id, obtained_marks, total_marks, percentage, pass_fail_status, rank_position, remarks, status, published_at) VALUES (:student_exam_id, :exam_id, :student_id, :obtained_marks, :total_marks, :percentage, :pass_fail_status, :rank_position, :remarks, :status, :published_at)');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Result saved successfully.';
    }

    redirect('exam-results.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'exam_result_id'  => null,
    'student_exam_id' => '',
    'exam_id'         => '',
    'student_id'      => '',
    'obtained_marks'  => '',
    'total_marks'     => '',
    'percentage'      => '',
    'pass_fail_status'=> 'pass',
    'rank_position'   => '',
    'remarks'         => '',
    'status'          => 'draft',
    'published_at'    => '',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM exam_results WHERE exam_result_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$rows         = $db->query('SELECT * FROM exam_results ORDER BY exam_result_id DESC LIMIT 50')->fetchAll();
$attempts     = $db->query('SELECT student_exam_id, exam_id, student_id FROM student_exams ORDER BY student_exam_id DESC')->fetchAll();
$statusCounts = $db->query('SELECT status, COUNT(*) AS total FROM exam_results GROUP BY status')->fetchAll();
$passCount    = (int) $db->query("SELECT COUNT(*) FROM exam_results WHERE pass_fail_status = 'pass'")->fetchColumn();
$failCount    = (int) $db->query("SELECT COUNT(*) FROM exam_results WHERE pass_fail_status = 'fail'")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Exam Results</h2>
            <p>Publish final result snapshots after grading student attempts.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-exams.php">Attempts</a>
            <a class="btn btn-primary" href="student-answers.php">Answer Review</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <!-- ── Form ── -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['exam_result_id'] ? 'Edit Result' : 'Create Result' ?></h3>
            <p class="small" style="margin:0 0 4px;">Link a result snapshot to a student attempt.</p>

            <form method="post">
                <input type="hidden" name="exam_result_id" value="<?= e((string) old($form, 'exam_result_id', '')) ?>">

                <!-- Group: Identity -->
                <div class="form-group-title">🎓 Identity</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Student Attempt</label>
                        <select name="student_exam_id" required>
                            <option value="">Select attempt</option>
                            <?php foreach ($attempts as $attempt): ?>
                                <option value="<?= (int) $attempt['student_exam_id'] ?>" <?= (string) old($form, 'student_exam_id') === (string) $attempt['student_exam_id'] ? 'selected' : '' ?>>
                                    Attempt #<?= (int) $attempt['student_exam_id'] ?> · Exam #<?= (int) $attempt['exam_id'] ?> · Student #<?= (int) $attempt['student_id'] ?>
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
                </div>

                <!-- Group: Scores -->
                <div class="form-group-title">📊 Scores</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Obtained Marks</label>
                        <input type="number" step="0.01" min="0" name="obtained_marks" required value="<?= e((string) old($form, 'obtained_marks')) ?>">
                    </div>
                    <div class="field">
                        <label>Total Marks</label>
                        <input type="number" step="0.01" min="0" name="total_marks" required value="<?= e((string) old($form, 'total_marks')) ?>">
                    </div>
                    <div class="field">
                        <label>Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="percentage" required value="<?= e((string) old($form, 'percentage')) ?>">
                    </div>
                    <div class="field">
                        <label>Rank Position <span class="small">(optional)</span></label>
                        <input type="number" min="1" name="rank_position" value="<?= e((string) old($form, 'rank_position')) ?>" placeholder="e.g. 1">
                    </div>
                    <div class="field">
                        <label>Pass/Fail</label>
                        <select name="pass_fail_status" required>
                            <option value="pass" <?= old($form, 'pass_fail_status') === 'pass' ? 'selected' : '' ?>>pass</option>
                            <option value="fail" <?= old($form, 'pass_fail_status') === 'fail' ? 'selected' : '' ?>>fail</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status" required>
                            <?php foreach (['draft', 'published', 'archived'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= old($form, 'status') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Group: Publication -->
                <div class="form-group-title">📢 Publication</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Published At</label>
                        <input type="datetime-local" name="published_at" value="<?= e((string) old($form, 'published_at') ? str_replace(' ', 'T', substr((string) old($form, 'published_at'), 0, 16)) : '') ?>">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Remarks <span class="small">(optional)</span></label>
                        <textarea name="remarks" style="min-height:70px;" placeholder="Optional remarks about this result."><?= e((string) old($form, 'remarks')) ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['exam_result_id'] ? 'Update Result' : 'Save Result' ?></button>
                    <?php if ($form['exam_result_id']): ?>
                        <a class="btn btn-ghost" href="exam-results.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── Status card ── -->
        <div class="card">
            <h3 style="margin:0 0 16px;">Result Status</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($statusCounts)): ?>
                        <tr><td colspan="2"><div class="empty-state"><p>No results yet.</p></div></td></tr>
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

    <!-- Result Registry -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Result Registry</h3>
        <p class="small" style="margin:0 0 16px;">All result snapshots stored in <strong>university_sbe</strong>.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Exam</th>
                        <th>Student</th>
                        <th>Score</th>
                        <th>%</th>
                        <th>Pass/Fail</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <span class="empty-icon">📭</span>
                            <p>No results yet. Create one using the form.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="small">Attempt #<?= (int) $row['student_exam_id'] ?></td>
                            <td class="small">Exam #<?= (int) $row['exam_id'] ?></td>
                            <td class="small">Student #<?= (int) $row['student_id'] ?></td>
                            <td class="fw-700"><?= e(number_format((float) $row['obtained_marks'], 2)) ?> / <?= e(number_format((float) $row['total_marks'], 2)) ?></td>
                            <td class="fw-700"><?= e(number_format((float) $row['percentage'], 1)) ?>%</td>
                            <td><span class="badge <?= e($row['pass_fail_status']) ?>"><?= e($row['pass_fail_status']) ?></span></td>
                            <td><span class="badge <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $row['exam_result_id'] ?>">Edit</a>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this result?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="exam_result_id" value="<?= (int) $row['exam_result_id'] ?>">
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
