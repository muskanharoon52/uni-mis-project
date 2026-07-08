<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['student']);

$db = db();
$student = current_user();
$studentId = (int) $student['login_id'];
$scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

$scheduleStmt = $db->prepare('SELECT es.*, e.exam_code, e.title, e.duration_minutes, e.total_questions, e.total_marks, e.passing_marks, e.allow_review, e.selection_mode, e.status AS exam_status FROM exam_schedule es INNER JOIN exams e ON e.exam_id = es.exam_id WHERE es.schedule_id = :id');
$scheduleStmt->execute([':id' => $scheduleId]);
$schedule = $scheduleStmt->fetch();

if (!$schedule || $schedule['exam_status'] !== 'published' || $schedule['status'] !== 'ongoing') {
    $_SESSION['message'] = 'This exam session is not currently active. The exam must be published and set to ongoing before you can proceed.';
    redirect('student-start-exam.php');
}

$questionsStmt = $db->prepare('SELECT eq.question_order, qb.question_id, qb.topic, qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option, qb.marks FROM exam_questions eq INNER JOIN question_bank qb ON qb.question_id = eq.question_id WHERE eq.exam_id = :exam_id AND qb.status = "active" ORDER BY eq.question_order ASC');
$questionsStmt->execute([':exam_id' => (int) $schedule['exam_id']]);
$questionRows = $questionsStmt->fetchAll();

if (empty($questionRows)) {
    $_SESSION['message'] = 'This exam does not have active questions mapped yet.';
    redirect('student-start-exam.php');
}

function question_snapshot(array $question): string
{
    return json_encode([
        'topic'          => $question['topic'],
        'question_text'  => $question['question_text'],
        'option_a'       => $question['option_a'],
        'option_b'       => $question['option_b'],
        'option_c'       => $question['option_c'],
        'option_d'       => $question['option_d'],
        'correct_option' => $question['correct_option'],
        'marks'          => (float) $question['marks'],
    ], JSON_UNESCAPED_SLASHES);
}

function find_or_create_attempt(PDO $db, array $schedule, int $studentId): array
{
    $stmt = $db->prepare('SELECT * FROM student_exams WHERE schedule_id = :schedule_id AND student_id = :student_id ORDER BY attempt_no DESC LIMIT 1');
    $stmt->execute([
        ':schedule_id' => (int) $schedule['schedule_id'],
        ':student_id'  => $studentId,
    ]);
    $attempt = $stmt->fetch();

    if ($attempt && $attempt['status'] === 'in_progress') {
        return $attempt;
    }

    if ($attempt && in_array($attempt['status'], ['submitted', 'auto_submitted'], true)) {
        return $attempt;
    }

    $attemptNo = $attempt ? ((int) $attempt['attempt_no'] + 1) : 1;
    $expiresAt = (new DateTimeImmutable('now'))->modify('+' . (int) $schedule['duration_minutes'] . ' minutes')->format('Y-m-d H:i:s');

    $insert = $db->prepare('INSERT INTO student_exams (schedule_id, exam_id, student_id, attempt_no, status, started_at, expires_at) VALUES (:schedule_id, :exam_id, :student_id, :attempt_no, "in_progress", NOW(), :expires_at)');
    $insert->execute([
        ':schedule_id' => (int) $schedule['schedule_id'],
        ':exam_id'     => (int) $schedule['exam_id'],
        ':student_id'  => $studentId,
        ':attempt_no'  => $attemptNo,
        ':expires_at'  => $expiresAt,
    ]);

    $stmt = $db->prepare('SELECT * FROM student_exams WHERE student_exam_id = :id');
    $stmt->execute([':id' => (int) $db->lastInsertId()]);
    return $stmt->fetch();
}

$attempt = find_or_create_attempt($db, $schedule, $studentId);
$result = null;

if (in_array((string) $attempt['status'], ['submitted', 'auto_submitted'], true)) {
    $resultStmt = $db->prepare('SELECT * FROM exam_results WHERE student_exam_id = :student_exam_id LIMIT 1');
    $resultStmt->execute([':student_exam_id' => (int) $attempt['student_exam_id']]);
    $result = $resultStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ((int) ($_POST['student_exam_id'] ?? 0) !== (int) $attempt['student_exam_id'] || $attempt['status'] !== 'in_progress') {
        $_SESSION['message'] = 'This attempt has already been submitted or is no longer available.';
        redirect('student-start-exam.php');
    }

    $answers = $_POST['answers'] ?? [];
    $allowedOptions = ['A', 'B', 'C', 'D'];
    $obtainedMarks = 0.0;
    $totalMarks = 0.0;

    try {
        $db->beginTransaction();

        $answerSql = 'INSERT INTO student_answers (student_exam_id, question_id, question_order, question_snapshot, selected_option, answered_at, is_correct, marks_awarded)
            VALUES (:student_exam_id, :question_id, :question_order, :question_snapshot, :selected_option, NOW(), :is_correct, :marks_awarded)
            ON DUPLICATE KEY UPDATE question_snapshot = VALUES(question_snapshot), selected_option = VALUES(selected_option), answered_at = VALUES(answered_at), is_correct = VALUES(is_correct), marks_awarded = VALUES(marks_awarded)';
        $answerStmt = $db->prepare($answerSql);

        foreach ($questionRows as $question) {
            $questionId = (int) $question['question_id'];
            $selected = strtoupper(trim((string) ($answers[$questionId] ?? '')));
            $selected = in_array($selected, $allowedOptions, true) ? $selected : null;
            $marks = (float) $question['marks'];
            $isCorrect = $selected !== null && $selected === strtoupper((string) $question['correct_option']);
            $marksAwarded = $isCorrect ? $marks : 0.0;

            $totalMarks += $marks;
            $obtainedMarks += $marksAwarded;

            $answerStmt->execute([
                ':student_exam_id'   => (int) $attempt['student_exam_id'],
                ':question_id'       => $questionId,
                ':question_order'    => (int) $question['question_order'],
                ':question_snapshot' => question_snapshot($question),
                ':selected_option'   => $selected,
                ':is_correct'        => $isCorrect ? 1 : 0,
                ':marks_awarded'     => $marksAwarded,
            ]);
        }

        $percentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.0;
        $passingMarks = $schedule['passing_marks'] !== null ? (float) $schedule['passing_marks'] : ($totalMarks * 0.5);
        $passFail = $obtainedMarks >= $passingMarks ? 'pass' : 'fail';

        $attemptUpdate = $db->prepare('UPDATE student_exams SET status = "submitted", submitted_at = NOW(), time_taken_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()), obtained_marks = :obtained_marks, percentage = :percentage, pass_fail_status = :pass_fail_status WHERE student_exam_id = :student_exam_id');
        $attemptUpdate->execute([
            ':obtained_marks'   => $obtainedMarks,
            ':percentage'       => $percentage,
            ':pass_fail_status' => $passFail,
            ':student_exam_id'  => (int) $attempt['student_exam_id'],
        ]);

        $resultSql = 'INSERT INTO exam_results (student_exam_id, exam_id, student_id, obtained_marks, total_marks, percentage, pass_fail_status, rank_position, remarks, status, published_at)
            VALUES (:student_exam_id, :exam_id, :student_id, :obtained_marks, :total_marks, :percentage, :pass_fail_status, NULL, :remarks, "published", NOW())
            ON DUPLICATE KEY UPDATE obtained_marks = VALUES(obtained_marks), total_marks = VALUES(total_marks), percentage = VALUES(percentage), pass_fail_status = VALUES(pass_fail_status), remarks = VALUES(remarks), status = "published", published_at = NOW()';
        $resultStmt = $db->prepare($resultSql);
        $resultStmt->execute([
            ':student_exam_id'   => (int) $attempt['student_exam_id'],
            ':exam_id'           => (int) $schedule['exam_id'],
            ':student_id'        => $studentId,
            ':obtained_marks'    => $obtainedMarks,
            ':total_marks'       => $totalMarks,
            ':percentage'        => $percentage,
            ':pass_fail_status'  => $passFail,
            ':remarks'           => 'Auto-graded MCQ result submitted from student exam room.',
        ]);

        $db->commit();
        $_SESSION['message'] = 'Exam submitted successfully. Your result has been sent to the teacher.';
        redirect('student-home.php');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'Could not submit exam. Please try again.';
    }
}

$pageTitle = 'Take Exam';
$activePage = 'student_start_exam';
require __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="page-head">
        <div>
            <h2>Take Exam</h2>
            <p><?= e($schedule['exam_code']) ?> - <?= e($schedule['title']) ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-start-exam.php">Back</a>
            <a class="btn btn-primary" href="student-home.php">Student Dashboard</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="card page-section" style="border-left: 5px solid <?= $result['pass_fail_status'] === 'pass' ? '#10b981' : '#ef4444' ?>;">
            <h3 style="margin:0 0 8px;">Attempt Already Submitted</h3>
            <p class="small" style="margin:0 0 16px;">Your teacher can now view this result in Exam Results.</p>
            <div class="stats-grid" style="margin-top:0;">
                <div class="stat-card-v2">
                    <div class="stat-label">Score</div>
                    <div class="stat-value"><?= e(number_format((float) $result['obtained_marks'], 2)) ?> / <?= e(number_format((float) $result['total_marks'], 2)) ?></div>
                </div>
                <div class="stat-card-v2">
                    <div class="stat-label">Percentage</div>
                    <div class="stat-value"><?= e(number_format((float) $result['percentage'], 1)) ?>%</div>
                </div>
                <div class="stat-card-v2">
                    <div class="stat-label">Status</div>
                    <div class="stat-value"><?= e(strtoupper((string) $result['pass_fail_status'])) ?></div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form method="post" onsubmit="return confirm('Submit this exam now? You cannot change answers after submission.');">
            <input type="hidden" name="schedule_id" value="<?= (int) $scheduleId ?>">
            <input type="hidden" name="student_exam_id" value="<?= (int) $attempt['student_exam_id'] ?>">

            <div class="hero page-section">
                <h3>Exam Details</h3>
                <p class="muted" style="margin-bottom:0;">
                    Duration: <strong><?= (int) $schedule['duration_minutes'] ?> minutes</strong> &nbsp;|&nbsp;
                    Questions: <strong><?= count($questionRows) ?></strong> &nbsp;|&nbsp;
                    Total Marks: <strong><?= e(number_format(array_sum(array_map(static fn ($q) => (float) $q['marks'], $questionRows)), 2)) ?></strong>
                </p>
            </div>

            <div class="page-section" style="display:grid; gap:16px;">
                <?php foreach ($questionRows as $question): ?>
                    <div class="form-card" style="padding:22px;">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px;">
                            <div>
                                <span class="badge manual">Q<?= (int) $question['question_order'] ?></span>
                                <span class="badge active"><?= e(number_format((float) $question['marks'], 2)) ?> mark</span>
                            </div>
                            <span class="small"><?= e($question['topic']) ?></span>
                        </div>
                        <h3 style="margin:0 0 16px; font-size:1rem; line-height:1.5;"><?= e($question['question_text']) ?></h3>
                        <div style="display:grid; gap:10px;">
                            <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $option => $field): ?>
                                <label style="display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid var(--border); border-radius:8px; background:var(--panel); cursor:pointer;">
                                    <input type="radio" name="answers[<?= (int) $question['question_id'] ?>]" value="<?= e($option) ?>" style="margin-top:3px;">
                                    <span><strong><?= e($option) ?>.</strong> <?= e($question[$field]) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card page-section" style="display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap;">
                <p class="small" style="margin:0;">Your answers will be auto-graded and sent to the teacher after submission.</p>
                <button class="btn btn-primary" type="submit">Submit Exam</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
