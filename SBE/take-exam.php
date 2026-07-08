<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['student']);

$db = db();
$student = current_user();
$scheduleId = (int) ($_GET['schedule_id'] ?? 0);

$scheduleStmt = $db->prepare('SELECT es.*, e.exam_code, e.title, e.duration_minutes, e.total_questions, e.total_marks, e.allow_review, e.selection_mode, e.status AS exam_status FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id WHERE es.schedule_id = :id');
$scheduleStmt->execute([':id' => $scheduleId]);
$schedule = $scheduleStmt->fetch();

if (!$schedule || $schedule['exam_status'] !== 'published' || $schedule['status'] !== 'ongoing') {
    $_SESSION['message'] = 'This exam session is not currently active. The exam must be published and set to ongoing by the faculty before you can proceed.';
    redirect('student-start-exam.php');
}

$questions = $db->prepare('SELECT eq.question_order, qb.question_id, qb.topic, qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option FROM exam_questions eq INNER JOIN question_bank qb ON qb.question_id = eq.question_id WHERE eq.exam_id = :exam_id ORDER BY eq.question_order ASC');
$questions->execute([':exam_id' => (int) $schedule['exam_id']]);
$questionRows = $questions->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="page-head">
        <div>
            <h2>Take Exam</h2>
            <p><?= e($schedule['exam_code']) ?> — <?= e($schedule['title']) ?>. Answer the questions below.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-start-exam.php">← Back</a>
            <a class="btn btn-primary" href="student-home.php">Student Dashboard</a>
        </div>
    </div>

    <!-- Exam details -->
    <div class="hero page-section">
        <h3>Exam Details</h3>
        <p class="muted" style="margin-bottom:0;">
            Duration: <strong><?= (int) $schedule['duration_minutes'] ?> minutes</strong> &nbsp;|&nbsp; 
            Total Questions: <strong><?= (int) $schedule['total_questions'] ?></strong> &nbsp;|&nbsp; 
            Total Marks: <strong><?= e(number_format((float) $schedule['total_marks'], 2)) ?></strong>
        </p>
    </div>

    <!-- Question paper card -->
    <div class="table-card page-section">
        <h3 style="margin:0 0 16px;">Question Paper</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Order</th>
                        <th>Question Detail</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($questionRows)): ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No questions have been configured for this manual exam paper.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questionRows as $question): ?>
                        <tr>
                            <td><span class="badge manual">Q<?= (int) $question['question_order'] ?></span></td>
                            <td>
                                <strong style="color:var(--text-strong);"><?= e($question['topic']) ?></strong>
                                <div style="margin-top:6px; line-height:1.5; color:var(--text);"><?= e($question['question_text']) ?></div>
                            </td>
                            <td>
                                <div style="display:grid; gap:6px; padding:4px 0;">
                                    <div class="small"><span style="color:var(--accent); font-weight:700; margin-right:4px;">🅐</span> <?= e($question['option_a']) ?></div>
                                    <div class="small"><span style="color:var(--accent); font-weight:700; margin-right:4px;">🅑</span> <?= e($question['option_b']) ?></div>
                                    <div class="small"><span style="color:var(--accent); font-weight:700; margin-right:4px;">🅒</span> <?= e($question['option_c']) ?></div>
                                    <div class="small"><span style="color:var(--accent); font-weight:700; margin-right:4px;">🅓</span> <?= e($question['option_d']) ?></div>
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
