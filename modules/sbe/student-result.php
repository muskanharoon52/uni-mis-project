<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Student Result';
$activePage = 'view_results';
$db = db();

$studentId = !empty($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$courseId  = !empty($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($studentId <= 0 || $courseId <= 0) {
    redirect('view-results.php');
}

$studentStmt = $db->prepare(
    'SELECT s.student_id, s.roll_no, s.full_name, s.father_name, s.batch_year, s.status,
            sem.semester_name, sec.section_name, p.program_name
     FROM students s
     LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
     LEFT JOIN sections sec ON sec.section_id = s.section_id
     LEFT JOIN programs p ON p.program_id = s.program_id
     WHERE s.student_id = :id'
);
$studentStmt->execute([':id' => $studentId]);
$student = $studentStmt->fetch();

$courseStmt = $db->prepare('SELECT course_id, course_code, course_title FROM courses WHERE course_id = :id');
$courseStmt->execute([':id' => $courseId]);
$course = $courseStmt->fetch();

if (!$student || !$course) {
    redirect('view-results.php');
}

$resultsStmt = $db->prepare(
    'SELECT er.*, e.exam_code, e.title AS exam_title, e.exam_type, e.duration_minutes,
            se.attempt_no, se.status AS attempt_status, se.started_at, se.submitted_at, se.time_taken_seconds
     FROM sbe_exam_results er
     JOIN sbe_exams e ON e.exam_id = er.exam_id
     LEFT JOIN sbe_student_exams se ON se.student_exam_id = er.student_exam_id
     WHERE er.student_id = :sid AND e.course_id = :cid
     ORDER BY er.created_at DESC'
);
$resultsStmt->execute([':sid' => $studentId, ':cid' => $courseId]);
$results = $resultsStmt->fetchAll();

$questionsStmt = $db->prepare(
    'SELECT sa.question_order, sa.selected_option, sa.is_correct, sa.marks_awarded, sa.question_snapshot, sa.answered_at,
            qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option, qb.marks
     FROM sbe_student_answers sa
     LEFT JOIN sbe_question_bank qb ON qb.question_id = sa.question_id
     WHERE sa.student_exam_id = :seid
     ORDER BY sa.question_order ASC'
);

$questionsByResult = [];
foreach ($results as $resultRow) {
    $questionsStmt->execute([':seid' => (int) $resultRow['student_exam_id']]);
    $questionsByResult[(int) $resultRow['exam_result_id']] = $questionsStmt->fetchAll();
}

if (!function_exists('grade_letter')) {
    function grade_letter(?float $pct): string
    {
        if ($pct === null) {
            return '-';
        }
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        return 'F';
    }
}

if (!function_exists('format_duration')) {
    function format_duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $m > 0 ? sprintf('%d min %d sec', $m, $s) : sprintf('%d sec', $s);
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <div class="page-head">
        <div>
            <h2>Student Result</h2>
            <p>Full result of <?= e($course['course_code']) ?> &mdash; <?= e($course['course_title']) ?> for <?= e($student['full_name']) ?>.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="view-results.php">&larr; Back to View Results</a>
        </div>
    </div>

    <div class="card page-section">
        <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">
            <div style="flex:1; min-width:220px;">
                <h3 style="margin:0;"><?= e($student['full_name']) ?></h3>
                <p class="small" style="margin:4px 0 0;">
                    Roll: <strong><?= e((string) $student['roll_no']) ?></strong>
                    &middot; Student ID: <strong><?= (int) $student['student_id'] ?></strong>
                    <?= $student['father_name'] ? '&middot; Father: ' . e($student['father_name']) : '' ?>
                </p>
            </div>
            <div style="flex:1; min-width:220px; display:flex; gap:8px; flex-wrap:wrap;">
                <?php if ($student['program_name']): ?><span class="badge badge-teacher"><?= e($student['program_name']) ?></span><?php endif; ?>
                <?php if ($student['section_name']): ?><span class="badge badge-scheduled">Section <?= e($student['section_name']) ?></span><?php endif; ?>
                <?php if ($student['semester_name']): ?><span class="badge badge-scheduled"><?= e($student['semester_name']) ?></span><?php endif; ?>
                <?php if ($student['batch_year']): ?><span class="badge badge-archived">Batch <?= e((string) $student['batch_year']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($results)): ?>
        <div class="empty-state page-section">
            <div class="empty-icon">&#128202;</div>
            <h3>No results for this subject</h3>
            <p>This student has no SBE result recorded for <?= e($course['course_code']) ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($results as $resultRow): ?>
            <?php $questions = $questionsByResult[(int) $resultRow['exam_result_id']] ?? []; ?>
            <?php $correctCount = 0; foreach ($questions as $q) { if ((int) $q['is_correct'] === 1) { $correctCount++; } } ?>
            <div class="card page-section">
                <div class="card-header">
                    <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
                        <div>
                            <h3 style="margin:0;">
                                <span class="badge badge-<?= e(strtolower((string) $resultRow['exam_type'])) ?>"><?= e($resultRow['exam_type']) ?></span>
                                &nbsp;<?= e($resultRow['exam_code']) ?> &mdash; <?= e(mb_strimwidth((string) $resultRow['exam_title'], 0, 60, '...')) ?>
                            </h3>
                            <p class="small" style="margin:4px 0 0;">
                                Attempt #<?= (int) $resultRow['attempt_no'] ?>
                                &middot; Result: <strong><?= e($resultRow['status']) ?></strong>
                                <?= $resultRow['published_at'] ? '&middot; Published ' . e(date('M d, Y h:i A', strtotime((string) $resultRow['published_at']))) : '' ?>
                            </p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.6rem; font-weight:800; color:var(--text-strong);">
                                <?= number_format((float) $resultRow['obtained_marks'], 2) ?>
                                <span style="font-size:.9rem; color:var(--text-muted);">/ <?= number_format((float) $resultRow['total_marks'], 2) ?></span>
                            </div>
                            <div>
                                <span class="badge badge-<?= $resultRow['pass_fail_status'] === 'Pass' ? 'pass' : 'fail' ?>">
                                    <?= e($resultRow['pass_fail_status']) ?> &middot; <?= grade_letter((float) $resultRow['percentage']) ?>
                                </span>
                                <span class="muted" style="font-size:.85rem;"><?= number_format((float) $resultRow['percentage'], 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-card" style="margin:0; border:none; border-radius:0;">
                    <div style="padding:10px 16px; font-size:.9rem; color:var(--text-secondary); display:flex; gap:20px; flex-wrap:wrap; background:var(--bg-panel); border-bottom:1px solid var(--border);">
                        <span><strong><?= count($questions) ?></strong> questions</span>
                        <span><strong><?= $correctCount ?></strong> correct</span>
                        <span>Time taken: <strong><?= format_duration(isset($resultRow['time_taken_seconds']) ? (int) $resultRow['time_taken_seconds'] : null) ?></strong></span>
                        <?php if ($resultRow['started_at']): ?><span>Started: <strong><?= e(date('M d, Y h:i A', strtotime((string) $resultRow['started_at']))) ?></strong></span><?php endif; ?>
                        <?php if ($resultRow['submitted_at']): ?><span>Submitted: <strong><?= e(date('M d, Y h:i A', strtotime((string) $resultRow['submitted_at']))) ?></strong></span><?php endif; ?>
                        <?php if ($resultRow['rank_position']): ?><span>Rank: <strong>#<?= (int) $resultRow['rank_position'] ?></strong></span><?php endif; ?>
                        <?php if ($resultRow['remarks']): ?><span>Remarks: <?= e($resultRow['remarks']) ?></span><?php endif; ?>
                    </div>

                    <?php if (empty($questions)): ?>
                        <div style="padding:18px 16px; color:var(--text-muted);">No answer detail recorded for this attempt.</div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:44px;">#</th>
                                    <th>Question</th>
                                    <th>Options</th>
                                    <th>Your Answer</th>
                                    <th>Correct</th>
                                    <th>Marks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $q): ?>
                                    <?php
                                    $snap = [];
                                    if ($q['question_snapshot']) {
                                        $decoded = json_decode((string) $q['question_snapshot'], true);
                                        if (is_array($decoded)) {
                                            $snap = $decoded;
                                        }
                                    }
                                    $qText     = $snap['question_text'] ?? $q['question_text'] ?? '';
                                    $opts      = [
                                        'A' => $snap['option_a'] ?? $q['option_a'] ?? '',
                                        'B' => $snap['option_b'] ?? $q['option_b'] ?? '',
                                        'C' => $snap['option_c'] ?? $q['option_c'] ?? '',
                                        'D' => $snap['option_d'] ?? $q['option_d'] ?? '',
                                    ];
                                    $correctOpt = $snap['correct_option'] ?? $q['correct_option'] ?? '';
                                    $marksPossible = (float) ($snap['marks'] ?? $q['marks'] ?? 0);
                                    $selected = (string) $q['selected_option'];
                                    $isCorrect = (int) $q['is_correct'] === 1;
                                    ?>
                                    <tr>
                                        <td><?= (int) $q['question_order'] ?></td>
                                        <td style="min-width:220px;"><?= e((string) $qText) ?></td>
                                        <td style="min-width:240px;">
                                            <?php foreach ($opts as $key => $text): ?>
                                                <?php if ($text === '' && $key === $correctOpt) { continue; } ?>
                                                <div style="padding:2px 0; <?= $key === $correctOpt ? 'color:var(--success); font-weight:600;' : '' ?>">
                                                    <strong><?= $key ?>.</strong> <?= e((string) $text) ?>
                                                    <?= $key === $correctOpt ? '&#10003;' : '' ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td>
                                            <?php if ($selected === ''): ?>
                                                <span class="badge badge-inactive">Not Answered</span>
                                            <?php else: ?>
                                                <span class="badge <?= $isCorrect ? 'badge-pass' : 'badge-fail' ?>">
                                                    <?= e($selected) ?><?= $isCorrect ? ' &#10003;' : ' &#10007;' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e((string) $correctOpt) ?></td>
                                        <td>
                                            <strong><?= number_format((float) $q['marks_awarded'], 2) ?></strong>
                                            <span class="muted">/ <?= number_format($marksPossible, 2) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
