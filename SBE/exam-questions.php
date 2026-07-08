<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Exam Questions';
$activePage = 'exam_questions';
$db = db();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM exam_questions WHERE exam_question_id = :id');
        $stmt->execute([':id' => (int) $_POST['exam_question_id']]);
        $_SESSION['message'] = 'Question removed from the exam.';
        redirect('exam-questions.php');
    }

    $examId        = (int) $_POST['exam_id'];
    $questionId    = (int) $_POST['question_id'];
    $questionOrder = (int) $_POST['question_order'];

    $examStmt = $db->prepare('SELECT exam_id, exam_code, title, selection_mode FROM exams WHERE exam_id = :id');
    $examStmt->execute([':id' => $examId]);
    $exam = $examStmt->fetch();

    if (!$exam) {
        $_SESSION['message'] = 'Selected exam was not found.';
        redirect('exam-questions.php');
    }

    if ($exam['selection_mode'] !== 'manual') {
        $_SESSION['message'] = 'Only manual exams can use this mapping table.';
        redirect('exam-questions.php');
    }

    $questionStmt = $db->prepare('SELECT question_id, topic, question_text FROM question_bank WHERE question_id = :id');
    $questionStmt->execute([':id' => $questionId]);
    $question = $questionStmt->fetch();

    if (!$question) {
        $_SESSION['message'] = 'Selected question was not found.';
        redirect('exam-questions.php');
    }

    $insert = $db->prepare('INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)');

    try {
        $insert->execute([
            ':exam_id'        => $examId,
            ':question_id'    => $questionId,
            ':question_order' => $questionOrder,
        ]);
        $_SESSION['message'] = 'Question added to the manual exam.';
    } catch (Throwable $exception) {
        $_SESSION['message'] = 'Could not add question — the same question or order may already exist for this exam.';
    }

    redirect('exam-questions.php');
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$manualExams     = $db->query("SELECT exam_id, exam_code, title, exam_type FROM exams WHERE selection_mode = 'manual' ORDER BY exam_id DESC")->fetchAll();
$questionBank    = $db->query('SELECT question_id, topic, question_text, difficulty_level, status FROM question_bank ORDER BY question_id DESC')->fetchAll();
$rows            = $db->query('SELECT eq.*, e.exam_code, e.title AS exam_title, qb.topic, qb.question_text FROM exam_questions eq INNER JOIN exams e ON e.exam_id = eq.exam_id INNER JOIN question_bank qb ON qb.question_id = eq.question_id ORDER BY eq.exam_id DESC, eq.question_order ASC')->fetchAll();
$examSummary     = $db->query("SELECT e.exam_id, e.exam_code, e.title, COUNT(eq.exam_question_id) AS mapped_questions FROM exams e LEFT JOIN exam_questions eq ON eq.exam_id = e.exam_id WHERE e.selection_mode = 'manual' GROUP BY e.exam_id, e.exam_code, e.title ORDER BY e.exam_id DESC LIMIT 6")->fetchAll();
$questionSummary = $db->query('SELECT topic, COUNT(*) AS total_questions FROM question_bank GROUP BY topic ORDER BY total_questions DESC, topic ASC LIMIT 6')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Exam Questions</h2>
            <p>Manual exam mapping — compose paper layouts from the question bank.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exams.php">Browse Exams</a>
            <a class="btn btn-primary" href="question-bank.php">Question Bank</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= str_contains($message, 'Could not') ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- Form + Topic summary side by side -->
    <div class="grid-2">

        <!-- ── Mapping form ── -->
        <div class="form-card">
            <h3 style="margin:0 0 4px;">Add Question to Manual Exam</h3>
            <p class="small" style="margin:0 0 4px;">Each selected question gets an order position inside the paper.</p>

            <form method="post">
                <div class="form-group-title">📝 Select Exam & Question</div>
                <div class="form-grid">
                    <div class="field">
                        <label>Manual Exam</label>
                        <select name="exam_id" required>
                            <option value="">Select exam</option>
                            <?php foreach ($manualExams as $exam): ?>
                                <option value="<?= (int) $exam['exam_id'] ?>"><?= e($exam['exam_code']) ?> — <?= e($exam['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Question Order</label>
                        <input type="number" name="question_order" min="1" required placeholder="1">
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Question</label>
                        <select name="question_id" required>
                            <option value="">Select question</option>
                            <?php foreach ($questionBank as $question): ?>
                                <option value="<?= (int) $question['question_id'] ?>">
                                    #<?= (int) $question['question_id'] ?> · <?= e($question['topic']) ?> · <?= e(mb_strimwidth($question['question_text'], 0, 72, '…')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="actions" style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit">Add to Exam</button>
                </div>
            </form>
        </div>

        <!-- ── Topic summary ── -->
        <div class="table-card">
            <h3 style="margin:0 0 4px;">Question Bank Topics</h3>
            <p class="small" style="margin:0 0 16px;">Useful for planning manual paper composition.</p>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Topic</th><th>Questions</th></tr></thead>
                    <tbody>
                    <?php if (empty($questionSummary)): ?>
                        <tr><td colspan="2"><div class="empty-state"><span class="empty-icon">📭</span><p>No topics yet.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($questionSummary as $summary): ?>
                            <tr>
                                <td style="font-weight:600; color:var(--text-strong);"><?= e($summary['topic']) ?></td>
                                <td><span class="badge manual"><?= (int) $summary['total_questions'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Manual exam summary -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Manual Exam Summary</h3>
        <p class="small" style="margin:0 0 16px;">How many questions are mapped to each manual exam.</p>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Exam</th><th>Mode</th><th>Mapped Questions</th></tr></thead>
                <tbody>
                <?php if (empty($examSummary)): ?>
                    <tr><td colspan="3"><div class="empty-state"><span class="empty-icon">📭</span><p>No manual exams yet.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($examSummary as $summary): ?>
                        <tr>
                            <td>
                                <span class="badge manual"><?= e($summary['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($summary['title']) ?></div>
                            </td>
                            <td><span class="badge manual">manual</span></td>
                            <td class="fw-700"><?= (int) $summary['mapped_questions'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Current mapping table -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Current Paper Mapping</h3>
        <p class="small" style="margin:0 0 16px;">Order position controls question appearance in the manual exam.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Exam</th>
                        <th>Question</th>
                        <th>Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5">
                        <div class="empty-state">
                            <span class="empty-icon">📭</span>
                            <p>No questions mapped yet. Use the form above to add some.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="small">#<?= (int) $row['exam_question_id'] ?></td>
                            <td>
                                <span class="badge manual"><?= e($row['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e($row['exam_title']) ?></div>
                            </td>
                            <td>
                                <span style="font-weight:600; color:var(--text-strong);"><?= e($row['topic']) ?></span>
                                <div class="small" style="margin-top:2px;"><?= e(mb_strimwidth($row['question_text'], 0, 66, '…')) ?></div>
                            </td>
                            <td><span class="badge active">#<?= (int) $row['question_order'] ?></span></td>
                            <td>
                                <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Remove this question from the exam?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="exam_question_id" value="<?= (int) $row['exam_question_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                                </form>
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
