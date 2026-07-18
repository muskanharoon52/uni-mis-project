<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Question Mapping';
$activePage = 'exam_questions';
$db = db();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM sbe_exam_questions WHERE exam_question_id = :id');
        $stmt->execute([':id' => (int) $_POST['exam_question_id']]);
        $_SESSION['message'] = 'Question removed from the exam.';
        redirect('exam-questions.php');
    }

    if ($action === 'delete_all_for_exam') {
        $examId = (int) $_POST['exam_id'];
        $db->prepare('DELETE FROM sbe_exam_questions WHERE exam_id = :id')->execute([':id' => $examId]);
        $_SESSION['message'] = 'All questions removed from this exam.';
        redirect('exam-questions.php');
    }

    if ($action === 'bulk_add') {
        $examId = (int) $_POST['exam_id'];
        $selectedIds = $_POST['question_ids'] ?? [];

        if (empty($selectedIds)) {
            $_SESSION['message'] = 'No questions selected.';
            redirect('exam-questions.php');
        }

        $examStmt = $db->prepare('SELECT exam_id, selection_mode FROM sbe_exams WHERE exam_id = :id');
        $examStmt->execute([':id' => $examId]);
        $exam = $examStmt->fetch();

        if (!$exam || $exam['selection_mode'] !== 'Manual') {
            $_SESSION['message'] = 'Invalid or non-manual exam.';
            redirect('exam-questions.php');
        }

        $maxOrderStmt = $db->prepare('SELECT COALESCE(MAX(question_order), 0) FROM sbe_exam_questions WHERE exam_id = :id');
        $maxOrderStmt->execute([':id' => $examId]);
        $nextOrder = (int) $maxOrderStmt->fetchColumn() + 1;

        $insert = $db->prepare('INSERT IGNORE INTO sbe_exam_questions (exam_id, question_id, question_order) VALUES (:exam_id, :question_id, :question_order)');
        $added = 0;
        foreach ($selectedIds as $qId) {
            $insert->execute([
                ':exam_id'        => $examId,
                ':question_id'    => (int) $qId,
                ':question_order' => $nextOrder++,
            ]);
            $added++;
        }

        $_SESSION['message'] = $added . ' question(s) added to the exam.';
        redirect('exam-questions.php');
    }
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$filterExamId = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : 0;

$manualExams = $db->query("SELECT e.exam_id, e.exam_code, e.title, e.total_questions, COUNT(eq.exam_question_id) AS mapped FROM sbe_exams e LEFT JOIN sbe_exam_questions eq ON eq.exam_id = e.exam_id WHERE e.selection_mode = 'Manual' GROUP BY e.exam_id, e.exam_code, e.title, e.total_questions ORDER BY e.exam_id DESC")->fetchAll();

$questionBank = $db->query('SELECT qb.question_id, qb.topic, qb.question_text, qb.difficulty_level, qb.marks, qb.status, c.course_code FROM sbe_question_bank qb LEFT JOIN courses c ON c.course_id = qb.course_id WHERE qb.status = \'Active\' ORDER BY qb.question_id DESC')->fetchAll();

if ($filterExamId) {
    $rows = $db->prepare('SELECT eq.*, e.exam_code, e.title AS exam_title, qb.topic, qb.question_text FROM sbe_exam_questions eq INNER JOIN sbe_exams e ON e.exam_id = eq.exam_id INNER JOIN sbe_question_bank qb ON qb.question_id = eq.question_id WHERE eq.exam_id = :exam_id ORDER BY eq.question_order ASC');
    $rows->execute([':exam_id' => $filterExamId]);
    $rows = $rows->fetchAll();
} else {
    $rows = $db->query('SELECT eq.*, e.exam_code, e.title AS exam_title, qb.topic, qb.question_text FROM sbe_exam_questions eq INNER JOIN sbe_exams e ON e.exam_id = eq.exam_id INNER JOIN sbe_question_bank qb ON qb.question_id = eq.question_id ORDER BY eq.exam_id DESC, eq.question_order ASC LIMIT 100')->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="page">

    <div class="page-head">
        <div>
            <h2>Question Mapping</h2>
            <p>Select an exam, then check multiple questions to add them at once.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="exams.php">&larr; Exams</a>
            <a class="btn btn-primary" href="question-bank.php">Question Bank</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Exam Summary</h3>
        <p class="small" style="margin:0 0 16px;">Click an exam to filter the mapping table below. Green = fully mapped.</p>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Exam</th><th>Required</th><th>Mapped</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($manualExams)): ?>
                    <tr><td colspan="4"><div class="empty-state"><p>No manual exams. <a href="exams.php">Create one first</a>.</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($manualExams as $summary): ?>
                        <?php
                        $mapped = (int) $summary['mapped'];
                        $required = (int) $summary['total_questions'];
                        $complete = $required > 0 && $mapped >= $required;
                        ?>
                        <tr style="<?= $filterExamId === (int) $summary['exam_id'] ? 'background:var(--bg-hover);' : '' ?>">
                            <td>
                                <a href="?exam_id=<?= (int) $summary['exam_id'] ?>" style="text-decoration:none;">
                                    <span class="badge badge-manual"><?= e($summary['exam_code']) ?></span>
                                    <div class="small" style="margin-top:3px;"><?= e($summary['title']) ?></div>
                                </a>
                            </td>
                            <td class="fw-700"><?= $required ?></td>
                            <td class="fw-700"><?= $mapped ?></td>
                            <td>
                                <?php if ($complete): ?>
                                    <span class="badge active">Ready</span>
                                <?php elseif ($mapped > 0): ?>
                                    <span class="badge draft">Partial</span>
                                <?php else: ?>
                                    <span class="badge inactive">Empty</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($filterExamId): ?>
    <?php
        $currentExam = null;
        foreach ($manualExams as $me) {
            if ((int) $me['exam_id'] === $filterExamId) { $currentExam = $me; break; }
        }
    ?>
    <div class="card page-section" style="border-left: 4px solid var(--accent);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h3 style="margin:0;">Bulk Add Questions</h3>
                <p class="small" style="margin:4px 0 0;">
                    Exam: <strong><?= e(($currentExam['exam_code'] ?? '?') . ' — ' . ($currentExam['title'] ?? '')) ?></strong>
                    &middot; Required: <?= (int) ($currentExam['total_questions'] ?? 0) ?>
                    &middot; Mapped: <?= (int) ($currentExam['mapped'] ?? 0) ?>
                </p>
            </div>
            <div class="actions">
                <a class="btn btn-ghost btn-sm" href="?">Clear Filter</a>
            </div>
        </div>

        <form method="post" style="margin-top:16px;">
            <input type="hidden" name="action" value="bulk_add">
            <input type="hidden" name="exam_id" value="<?= $filterExamId ?>">

            <div class="form-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:8px;">
                <?php if (empty($questionBank)): ?>
                    <p class="small" style="grid-column:1/-1; color:var(--text-muted);">No active questions in the bank. <a href="question-bank.php">Add some first</a>.</p>
                <?php else: ?>
                    <?php foreach ($questionBank as $q): ?>
                        <label style="display:flex; align-items:flex-start; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:var(--bg-card); transition: background 0.15s;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='var(--bg-card)'">
                            <input type="checkbox" name="question_ids[]" value="<?= (int) $q['question_id'] ?>" style="margin-top:3px;">
                            <div style="min-width:0;">
                                <div style="font-weight:600; font-size:0.82rem; color:var(--text-strong);">
                                    #<?= (int) $q['question_id'] ?>
                                    <span class="badge badge-manual" style="font-size:0.7rem;"><?= e($q['course_code'] ?? '?') ?></span>
                                    <?= e($q['topic']) ?>
                                </div>
                                <div class="small" style="color:var(--text-muted); margin-top:2px;"><?= e(mb_strimwidth($q['question_text'], 0, 80, '...')) ?></div>
                                <div style="margin-top:3px;">
                                    <span class="badge badge-<?= e(strtolower($q['difficulty_level'])) ?>" style="font-size:0.68rem;"><?= e($q['difficulty_level']) ?></span>
                                    <span class="small" style="color:var(--text-muted);"><?= number_format((float) $q['marks'], 1) ?> marks</span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="actions" style="margin-top:16px;">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Add selected questions to this exam?');">Add Selected Questions</button>
                <span class="small" style="align-self:center; color:var(--text-muted);">Questions are appended in order. Remove individual ones from the table below.</span>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Mapped Questions</h3>
        <p class="small" style="margin:0 0 16px;">
            <?php if ($filterExamId): ?>
                Showing questions for the selected exam. <a href="?">View all</a>
            <?php else: ?>
                Showing up to 100 mappings across all exams. Select an exam above to filter.
            <?php endif; ?>
        </p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Exam</th>
                        <th>Question</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4">
                        <div class="empty-state">
                            <span class="empty-icon">&#128233;</span>
                            <p>No questions mapped yet. Select an exam above and check questions to add them.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><span class="badge active">#<?= (int) $row['question_order'] ?></span></td>
                            <td>
                                <a href="?exam_id=<?= (int) $row['exam_id'] ?>" style="text-decoration:none;">
                                    <span class="badge badge-manual"><?= e($row['exam_code']) ?></span>
                                </a>
                            </td>
                            <td>
                                <span style="font-weight:600; color:var(--text-strong);"><?= e($row['topic']) ?></span>
                                <div class="small" style="margin-top:2px;"><?= e(mb_strimwidth($row['question_text'], 0, 66, '...')) ?></div>
                            </td>
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
