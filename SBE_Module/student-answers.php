<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Student Answers';
$activePage = 'student_answers';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM sbe_student_answers WHERE student_answer_id = :id');
        $stmt->execute([':id' => (int) $_POST['student_answer_id']]);
        $_SESSION['message'] = 'Answer row removed.';
        redirect('student-answers.php');
    }

    $payload = [
        'student_exam_id'   => (int) $_POST['student_exam_id'],
        'question_id'       => (int) $_POST['question_id'],
        'question_order'    => (int) $_POST['question_order'],
        'question_snapshot' => trim((string) $_POST['question_snapshot']),
        'selected_option'   => $_POST['selected_option'] ?: null,
        'answered_at'       => $_POST['answered_at'] ?: null,
        'is_correct'        => $_POST['is_correct'] === '' ? null : (int) $_POST['is_correct'],
        'marks_awarded'     => $_POST['marks_awarded'] === '' ? null : (float) $_POST['marks_awarded'],
    ];

    if (!empty($_POST['student_answer_id'])) {
        $payload['student_answer_id'] = (int) $_POST['student_answer_id'];
        $stmt = $db->prepare('UPDATE sbe_student_answers SET student_exam_id = :student_exam_id, question_id = :question_id, question_order = :question_order, question_snapshot = :question_snapshot, selected_option = :selected_option, answered_at = :answered_at, is_correct = :is_correct, marks_awarded = :marks_awarded WHERE student_answer_id = :student_answer_id');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Answer updated successfully.';
    } else {
        $stmt = $db->prepare('INSERT INTO sbe_student_answers (student_exam_id, question_id, question_order, question_snapshot, selected_option, answered_at, is_correct, marks_awarded) VALUES (:student_exam_id, :question_id, :question_order, :question_snapshot, :selected_option, :answered_at, :is_correct, :marks_awarded)');
        $stmt->execute($payload);
        $_SESSION['message'] = 'Answer saved successfully.';
    }

    redirect('student-answers.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$form = [
    'student_answer_id' => null,
    'student_exam_id'   => '',
    'question_id'       => '',
    'question_order'    => '1',
    'question_snapshot' => '{"topic":"","question_text":"","option_a":"","option_b":"","option_c":"","option_d":"","correct_option":""}',
    'selected_option'   => '',
    'answered_at'       => '',
    'is_correct'        => '',
    'marks_awarded'     => '',
];

if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM sbe_student_answers WHERE student_answer_id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) { $form = array_merge($form, $row); }
}

$rows          = $db->query('SELECT * FROM sbe_student_answers ORDER BY student_answer_id DESC LIMIT 50')->fetchAll();
$attempts      = $db->query('SELECT student_exam_id, exam_id, student_id FROM sbe_student_exams ORDER BY student_exam_id DESC')->fetchAll();
$questions     = $db->query('SELECT question_id, topic, question_text FROM sbe_question_bank ORDER BY question_id DESC')->fetchAll();
$correctCount  = (int) $db->query('SELECT COUNT(*) FROM sbe_student_answers WHERE is_correct = 1')->fetchColumn();
$answeredCount = (int) $db->query('SELECT COUNT(*) FROM sbe_student_answers WHERE answered_at IS NOT NULL')->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Student Answers</h2>
            <p>Answer rows per question per attempt &mdash; includes question snapshot JSON for audit.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-exams.php">Attempts</a>
            <a class="btn btn-primary" href="question-bank.php">Question Bank</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <div class="form-card">
            <h3 style="margin:0 0 4px;"><?= $form['student_answer_id'] ? 'Edit Answer Row' : 'Add Answer Row' ?></h3>
            <p class="small" style="margin:0 0 4px;">Admin-level answer management for testing and correction.</p>

            <form method="post">
                <input type="hidden" name="student_answer_id" value="<?= e((string) old($form, 'student_answer_id', '')) ?>">

                <div class="form-group-title">Attempt & Question</div>
                <div class="form-grid">
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Student Attempt</label>
                        <select name="student_exam_id" required>
                            <option value="">Select attempt</option>
                            <?php foreach ($attempts as $attempt): ?>
                                <option value="<?= (int) $attempt['student_exam_id'] ?>" <?= (string) old($form, 'student_exam_id') === (string) $attempt['student_exam_id'] ? 'selected' : '' ?>>
                                    Attempt #<?= (int) $attempt['student_exam_id'] ?> &middot; Exam #<?= (int) $attempt['exam_id'] ?> &middot; Student #<?= (int) $attempt['student_id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Question</label>
                        <select name="question_id" required>
                            <option value="">Select question</option>
                            <?php foreach ($questions as $question): ?>
                                <option value="<?= (int) $question['question_id'] ?>" <?= (string) old($form, 'question_id') === (string) $question['question_id'] ? 'selected' : '' ?>>
                                    #<?= (int) $question['question_id'] ?> &middot; <?= e($question['topic']) ?> &middot; <?= e(mb_strimwidth($question['question_text'], 0, 52, '...')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Question Order</label>
                        <input type="number" min="1" name="question_order" required value="<?= e((string) old($form, 'question_order')) ?>">
                    </div>
                </div>

                <div class="form-group-title">Answer Details</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Selected Option</label>
                        <select name="selected_option">
                            <option value="">Skip / Blank</option>
                            <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= old($form, 'selected_option') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Answered At</label>
                        <input type="datetime-local" name="answered_at" value="<?= e((string) old($form, 'answered_at') ? str_replace(' ', 'T', substr((string) old($form, 'answered_at'), 0, 16)) : '') ?>">
                    </div>
                    <div class="field">
                        <label>Is Correct <span class="small">(0 or 1)</span></label>
                        <input type="number" min="0" max="1" name="is_correct" value="<?= e((string) old($form, 'is_correct')) ?>" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Marks Awarded</label>
                        <input type="number" step="0.01" min="0" name="marks_awarded" value="<?= e((string) old($form, 'marks_awarded')) ?>" placeholder="0.00">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Question Snapshot JSON</label>
                        <textarea name="question_snapshot" required style="min-height:90px; font-family: monospace; font-size:.82rem;"><?= e((string) old($form, 'question_snapshot')) ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit"><?= $form['student_answer_id'] ? 'Update Answer' : 'Save Answer' ?></button>
                    <?php if ($form['student_answer_id']): ?>
                        <a class="btn btn-ghost" href="student-answers.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin:0 0 16px;">Data Health</h3>
            <p class="small" style="margin:0 0 16px;">Each answer row must stay tied to exactly one attempt and one question.</p>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Metric</th><th>Total</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>Questions in Bank</td>
                            <td class="fw-700"><?= number_format(count($questions)) ?></td>
                        </tr>
                        <tr>
                            <td>Unique Attempts</td>
                            <td class="fw-700"><?= number_format(count($attempts)) ?></td>
                        </tr>
                        <tr>
                            <td>Answered Rows</td>
                            <td class="fw-700"><?= number_format($answeredCount) ?></td>
                        </tr>
                        <tr>
                            <td>Correct Rows</td>
                            <td class="fw-700" style="color:#6fefbc;"><?= number_format($correctCount) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Answer Registry</h3>
        <p class="small" style="margin:0 0 16px;">All stored answer rows from student attempts.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Attempt</th>
                        <th>Question</th>
                        <th>Order</th>
                        <th>Selected</th>
                        <th>Correct?</th>
                        <th>Marks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <span class="empty-icon">&#128233;</span>
                            <p>No answer rows yet.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="small">Attempt #<?= (int) $row['student_exam_id'] ?></td>
                            <td class="small">Question #<?= (int) $row['question_id'] ?></td>
                            <td class="small"><?= (int) $row['question_order'] ?></td>
                            <td>
                                <?php if ($row['selected_option']): ?>
                                    <span class="badge badge-manual"><?= e((string) $row['selected_option']) ?></span>
                                <?php else: ?>
                                    <span class="small" style="color:var(--muted);">skipped</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['is_correct'] === null): ?>
                                    <span class="small" style="color:var(--muted);">&mdash;</span>
                                <?php elseif ((int) $row['is_correct'] === 1): ?>
                                    <span class="correct-badge">Yes</span>
                                <?php else: ?>
                                    <span class="wrong-badge">No</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-700"><?= e(number_format((float) ($row['marks_awarded'] ?? 0), 2)) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $row['student_answer_id'] ?>">Edit</a>
                                    <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Delete this answer row?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_answer_id" value="<?= (int) $row['student_answer_id'] ?>">
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
