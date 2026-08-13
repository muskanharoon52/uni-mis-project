<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/pdf_extractor.php';

require_login(['Teacher']);

$pageTitle = 'Add Questions';
$activePage = 'exam_questions';
$db = db();
$teacher = current_user();
$teacherId = (int) ($teacher['teacher_id'] ?? 0);

if ($teacherId === 0) {
    $fallbackTeacher = $db->query("SELECT teacher_id FROM teachers WHERE status = 'Active' LIMIT 1")->fetch();
    if ($fallbackTeacher) {
        $teacherId = (int) $fallbackTeacher['teacher_id'];
    }
}

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare('DELETE FROM sbe_exam_questions WHERE exam_question_id = :id')->execute([':id' => (int) $_POST['exam_question_id']]);
        $_SESSION['message'] = 'Question removed from the exam.';
        redirect('exam-questions.php?exam_id=' . (int) ($_POST['exam_id'] ?? 0));
    }

    if ($action === 'save_exam') {
        $examId = (int) $_POST['exam_id'];
        $countStmt = $db->prepare('SELECT COUNT(*) FROM sbe_exam_questions WHERE exam_id = :id');
        $countStmt->execute([':id' => $examId]);
        $count = (int) $countStmt->fetchColumn();

        $typeStmt = $db->prepare('SELECT exam_type FROM sbe_exams WHERE exam_id = :id');
        $typeStmt->execute([':id' => $examId]);
        $examType = (string) $typeStmt->fetchColumn();

        $savedMsg = 'Exam saved' . ($count > 0 ? ' with ' . $count . ' question(s)' : '. Add questions to complete it.');
        if (in_array($examType, ['Mid', 'Final'], true)) {
            $_SESSION['message'] = $savedMsg . ' This ' . $examType . ' exam has been sent to the Examination section for scheduling.';
            redirect('exams.php');
        }

        $_SESSION['message'] = $savedMsg . ' You can now schedule it.';
        redirect('schedule.php?exam_id=' . $examId);
    }

    if ($action === 'manual_add') {
        $examId = (int) $_POST['exam_id'];
        $questionText = trim((string) ($_POST['question_text'] ?? ''));
        $correct = strtoupper((string) ($_POST['correct_option'] ?? ''));

        if ($examId <= 0 || $questionText === '' || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $_SESSION['message'] = 'Question text and correct option are required.';
            redirect('exam-questions.php?exam_id=' . $examId);
        }

        $examStmt = $db->prepare('SELECT course_id FROM sbe_exams WHERE exam_id = :id');
        $examStmt->execute([':id' => $examId]);
        $exam = $examStmt->fetch();
        if (!$exam) {
            $_SESSION['message'] = 'Exam not found.';
            redirect('exam-questions.php');
        }

        $insert = $db->prepare('INSERT INTO sbe_question_bank (course_id, teacher_id, topic, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, difficulty_level, status) VALUES (:course_id, :teacher_id, :topic, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option, :explanation, :marks, :difficulty_level, :status)');
        $insert->execute([
            ':course_id'      => (int) $exam['course_id'],
            ':teacher_id'     => $teacherId,
            ':topic'          => trim((string) ($_POST['topic'] ?? '') ?: 'General'),
            ':question_text'  => $questionText,
            ':option_a'       => trim((string) ($_POST['option_a'] ?? '')),
            ':option_b'       => trim((string) ($_POST['option_b'] ?? '')),
            ':option_c'       => trim((string) ($_POST['option_c'] ?? '')),
            ':option_d'       => trim((string) ($_POST['option_d'] ?? '')),
            ':correct_option' => $correct,
            ':explanation'    => trim((string) ($_POST['explanation'] ?? '')),
            ':marks'          => (float) ($_POST['marks'] ?? 1),
            ':difficulty_level' => in_array($_POST['difficulty_level'] ?? '', ['Easy', 'Medium', 'Hard'], true) ? $_POST['difficulty_level'] : 'Medium',
            ':status'         => 'Active',
        ]);
        $questionId = (int) $db->lastInsertId();

        $orderStmt = $db->prepare('SELECT COALESCE(MAX(question_order), 0) FROM sbe_exam_questions WHERE exam_id = :id');
        $orderStmt->execute([':id' => $examId]);
        $db->prepare('INSERT INTO sbe_exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)')
            ->execute([':exam_id' => $examId, ':question_id' => $questionId, ':question_order' => (int) $orderStmt->fetchColumn() + 1]);
        $db->prepare("UPDATE sbe_exams SET question_source = CASE WHEN question_source = 'PDF' THEN 'Mixed' ELSE question_source END WHERE exam_id = :id")->execute([':id' => $examId]);

        $_SESSION['message'] = 'Question added to the exam.';
        redirect('exam-questions.php?exam_id=' . $examId);
    }

    if ($action === 'import_pdf') {
        $examId = (int) $_POST['exam_id'];
        if ($examId <= 0 || empty($_FILES['question_pdf']['tmp_name']) || !is_uploaded_file($_FILES['question_pdf']['tmp_name'])) {
            $_SESSION['message'] = 'Please choose a PDF file to upload.';
            redirect('exam-questions.php?exam_id=' . $examId);
        }

        $pdfData = file_get_contents($_FILES['question_pdf']['tmp_name']);
        if ($pdfData === false || strpos($pdfData, '%PDF') !== 0) {
            $_SESSION['message'] = 'Invalid PDF file.';
            redirect('exam-questions.php?exam_id=' . $examId);
        }

        $examStmt = $db->prepare('SELECT course_id, title FROM sbe_exams WHERE exam_id = :id');
        $examStmt->execute([':id' => $examId]);
        $exam = $examStmt->fetch();
        if (!$exam) {
            $_SESSION['message'] = 'Exam not found.';
            redirect('exam-questions.php');
        }

        $text = pdf_extract_text($pdfData);
        if (trim($text) === '') {
            $_SESSION['message'] = 'Could not read any text from this PDF. Make sure the PDF contains text (not scanned images) and try again, or add questions manually.';
            redirect('exam-questions.php?exam_id=' . $examId);
        }

        $questions = parse_mcq_text($text);
        if (empty($questions)) {
            $_SESSION['message'] = 'No questions could be detected in the PDF. Use the format: 1. question text, then A) option, B) option, C) option, D) option and Answer: B.';
            redirect('exam-questions.php?exam_id=' . $examId);
        }

        $insert = $db->prepare('INSERT INTO sbe_question_bank (course_id, teacher_id, topic, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, difficulty_level, status) VALUES (:course_id, :teacher_id, :topic, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option, :explanation, :marks, :difficulty_level, :status)');
        $link = $db->prepare('INSERT INTO sbe_exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)');
        $orderStmt = $db->prepare('SELECT COALESCE(MAX(question_order), 0) FROM sbe_exam_questions WHERE exam_id = :id');
        $orderStmt->execute([':id' => $examId]);
        $nextOrder = (int) $orderStmt->fetchColumn() + 1;

        $added = 0;
        foreach ($questions as $q) {
            $options = $q['options'];
            $insert->execute([
                ':course_id'        => (int) $exam['course_id'],
                ':teacher_id'       => $teacherId,
                ':topic'            => mb_strimwidth($exam['title'], 0, 150, '...'),
                ':question_text'    => $q['text'],
                ':option_a'         => (string) ($options['A'] ?? ''),
                ':option_b'         => (string) ($options['B'] ?? ''),
                ':option_c'         => (string) ($options['C'] ?? ''),
                ':option_d'         => (string) ($options['D'] ?? ''),
                ':correct_option'   => $q['correct'] ?? 'A',
                ':explanation'      => '',
                ':marks'            => 1,
                ':difficulty_level' => 'Medium',
                ':status'           => 'Active',
            ]);
            $link->execute([':exam_id' => $examId, ':question_id' => (int) $db->lastInsertId(), ':question_order' => $nextOrder++]);
            $added++;
        }

        $db->prepare("UPDATE sbe_exams SET question_source = CASE WHEN question_source = 'Manual' THEN 'PDF' ELSE question_source END WHERE exam_id = :id")->execute([':id' => $examId]);

        $_SESSION['message'] = $added . ' question(s) scanned and added from the PDF. Review them below before publishing.';
        redirect('exam-questions.php?exam_id=' . $examId);
    }
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$filterExamId = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;
$pageError = '';

try {
    $allExams = $db->query("SELECT e.exam_id, e.exam_code, e.title, e.total_questions, e.status, d.department_name, s.section_name, e.batch_year, c.course_code, c.course_title, COUNT(eq.exam_question_id) AS mapped FROM sbe_exams e LEFT JOIN departments d ON d.department_id = e.department_id LEFT JOIN sections s ON s.section_id = e.section_id LEFT JOIN courses c ON c.course_id = e.course_id LEFT JOIN sbe_exam_questions eq ON eq.exam_id = e.exam_id GROUP BY e.exam_id ORDER BY e.exam_id DESC")->fetchAll();

    $currentExam = null;
    $rows = [];
    if ($filterExamId) {
        foreach ($allExams as $e) {
            if ((int) $e['exam_id'] === $filterExamId) { $currentExam = $e; break; }
        }
        if ($currentExam) {
            $stmt = $db->prepare('SELECT eq.exam_question_id, eq.question_order, qb.question_id, qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option, qb.marks, qb.topic FROM sbe_exam_questions eq INNER JOIN sbe_question_bank qb ON qb.question_id = eq.question_id WHERE eq.exam_id = :exam_id ORDER BY eq.question_order ASC');
            $stmt->execute([':exam_id' => $filterExamId]);
            $rows = $stmt->fetchAll();
        }
    }
} catch (Throwable $e) {
    error_log('[SBE][exam-questions] EXCEPTION: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $pageError = 'Something went wrong while loading the question bank. Please try again.';
}

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Add Questions to Exam</h2>
            <p>Choose an exam, then upload a question bank PDF (questions are scanned out of it) or add questions manually, then save the exam.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exams.php">&larr; Exams</a>
            <a class="btn btn-primary" href="exams.php">+ Create Exam</a>
        </div>
    </div>

    <?php if ($pageError): ?>
        <div class="card page-section">
            <div class="empty-state">
                <div class="empty-icon">&#9888;&#65039;</div>
                <h3>Question bank unavailable</h3>
                <p><?= e($pageError) ?></p>
                <div style="margin-top:14px;">
                    <a class="btn btn-ghost" href="exam-questions.php">Retry</a>
                    <a class="btn btn-ghost" href="exams.php">&larr; Back to Exams</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="card page-section">
        <h3 style="margin:0 0 10px;">Select Exam</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Exam</th><th>Dept</th><th>Section</th><th>Batch</th><th>Subject</th><th>Questions</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($allExams)): ?>
                    <tr><td colspan="8"><div class="empty-state"><p>No exams yet. <a href="exams.php">Create one first</a>.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($allExams as $e): ?>
                        <tr style="<?= $filterExamId === (int) $e['exam_id'] ? 'background:var(--bg-hover);' : '' ?>">
                            <td>
                                <span class="badge badge-manual"><?= e($e['exam_code']) ?></span>
                                <div class="small" style="margin-top:3px;"><?= e(mb_strimwidth($e['title'],0,34,'...')) ?></div>
                            </td>
                            <td class="small"><?= e($e['department_name'] ?? '—') ?></td>
                            <td class="small"><?= e($e['section_name'] ?? '—') ?></td>
                            <td class="small"><?= $e['batch_year'] ? (int) $e['batch_year'] : '—' ?></td>
                            <td class="small"><?= e($e['course_code'] ?? '') ?></td>
                            <td class="fw-700"><?= (int) $e['mapped'] ?> / <?= (int) $e['total_questions'] ?></td>
                            <td><span class="badge badge-<?= e(strtolower($e['status'])) ?>"><?= e($e['status']) ?></span></td>
                            <td>
                                <a class="btn btn-<?= $filterExamId === (int) $e['exam_id'] ? 'primary' : 'ghost' ?> btn-sm" href="?exam_id=<?= (int) $e['exam_id'] ?>"><?= $filterExamId === (int) $e['exam_id'] ? 'Selected' : 'Add Questions' ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($currentExam): ?>
    <div class="grid-2 page-section">

        <div class="card" style="border-left: 4px solid var(--accent);">
            <div class="card-header">
                <h3>Option 1 — Upload Question Bank PDF</h3>
                <p>Questions are scanned out of the PDF automatically.</p>
            </div>
            <div style="padding:20px;">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_pdf">
                    <input type="hidden" name="exam_id" value="<?= $filterExamId ?>">
                    <div class="field">
                        <label>Question Bank PDF</label>
                        <input type="file" name="question_pdf" accept="application/pdf,.pdf" required>
                        <p class="small" style="margin-top:6px;">Expected format inside the PDF:<br>
                            <code>1. Which device is used for...<br>A) Keyboard<br>B) Monitor<br>C) Mouse<br>D) CPU<br>Answer: B</code></p>
                    </div>
                    <button class="btn btn-primary" type="submit">Scan &amp; Import Questions</button>
                </form>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--success);">
            <div class="card-header">
                <h3>Option 2 — Add Question Manually</h3>
                <p>Enter one question at a time.</p>
            </div>
            <div style="padding:20px;">
                <form method="post">
                    <input type="hidden" name="action" value="manual_add">
                    <input type="hidden" name="exam_id" value="<?= $filterExamId ?>">
                    <div class="field">
                        <label>Question</label>
                        <textarea name="question_text" rows="2" required placeholder="Type the question..."></textarea>
                    </div>
                    <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                        <div class="field"><label>Option A</label><input type="text" name="option_a" required></div>
                        <div class="field"><label>Option B</label><input type="text" name="option_b" required></div>
                        <div class="field"><label>Option C</label><input type="text" name="option_c" required></div>
                        <div class="field"><label>Option D</label><input type="text" name="option_d" required></div>
                    </div>
                    <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
                        <div class="field">
                            <label>Correct Option</label>
                            <select name="correct_option" required>
                                <option value="">Select</option>
                                <?php foreach (['A','B','C','D'] as $o): ?><option value="<?= $o ?>"><?= $o ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Marks</label>
                            <input type="number" name="marks" value="1" min="0.5" step="0.5">
                        </div>
                        <div class="field">
                            <label>Difficulty</label>
                            <select name="difficulty_level">
                                <option value="Easy">Easy</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Hard">Hard</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Add Question</button>
                </form>
            </div>
        </div>

    </div>

    <div class="table-card page-section">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h3 style="margin:0 0 4px;">Questions in <?= e($currentExam['title']) ?></h3>
                <p class="small" style="margin:0;">
                    <?= (int) $currentExam['mapped'] ?> of <?= (int) $currentExam['total_questions'] ?> questions added
                    &middot; Source: <span class="badge badge-manual"><?= e($currentExam['question_source'] ?? 'Manual') ?></span>
                </p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="save_exam">
                <input type="hidden" name="exam_id" value="<?= $filterExamId ?>">
                <button class="btn btn-primary" type="submit">Save Exam</button>
            </form>
        </div>
        <div class="table-wrapper" style="margin-top:14px;">
            <table>
                <thead><tr><th>#</th><th>Question</th><th>Options</th><th>Answer</th><th>Marks</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <span class="empty-icon">&#128218;</span>
                            <p>No questions yet. Upload a PDF or add manually.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><span class="badge active">#<?= (int) $row['question_order'] ?></span></td>
                            <td class="small fw-700"><?= e(mb_strimwidth($row['question_text'], 0, 90, '...')) ?></td>
                            <td class="small">
                                <div>A: <?= e(mb_strimwidth($row['option_a'],0,30,'...')) ?></div>
                                <div>B: <?= e(mb_strimwidth($row['option_b'],0,30,'...')) ?></div>
                                <?php if ($row['option_c']): ?><div>C: <?= e(mb_strimwidth($row['option_c'],0,30,'...')) ?></div><?php endif; ?>
                                <?php if ($row['option_d']): ?><div>D: <?= e(mb_strimwidth($row['option_d'],0,30,'...')) ?></div><?php endif; ?>
                            </td>
                            <td><span class="badge active"><?= e($row['correct_option']) ?></span></td>
                            <td class="small"><?= number_format((float) $row['marks'], 1) ?></td>
                            <td>
                                <form method="post" style="display:inline; margin:0;" onsubmit="return confirm('Remove this question from the exam?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="exam_question_id" value="<?= (int) $row['exam_question_id'] ?>">
                                    <input type="hidden" name="exam_id" value="<?= $filterExamId ?>">
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
    <?php endif; ?>

</div>

<script>
console.log('[SBE][exam-questions]', {
    teacherId: <?= (int) $teacherId ?>,
    filterExamId: <?= (int) $filterExamId ?>,
    examsLoaded: <?= is_countable($allExams) ? count($allExams) : 0 ?>,
    mappedQuestions: <?= count($rows) ?>,
    pageError: <?= json_encode($pageError) ?>
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
