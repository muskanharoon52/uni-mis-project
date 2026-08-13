<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher', 'Student']);

function sbe_log(string $message): void
{
    error_log('[SBE][student-result] ' . $message);
}

$pageTitle = 'Student Answers';
$activePage = 'student_answers';
$user = current_user();
$db = db();

$isTeacher = ($user['role'] ?? '') === 'Teacher';
$isStudent = ($user['role'] ?? '') === 'Student';

// Students may ONLY ever view their own answers. The student_id from the URL is
// ignored for students so nobody can browse another student's paper by hand.
$forcedStudentId = $isStudent ? (int) ($user['student_id'] ?? 0) : 0;
$studentId = $forcedStudentId > 0 ? $forcedStudentId : (int) ($_GET['student_id'] ?? 0);
$courseId  = (int) ($_GET['course_id'] ?? 0);

sbe_log("page load: role={$user['role']} forcedStudentId={$forcedStudentId} studentId={$studentId} courseId={$courseId}");

$pageError = '';
$student = null;
$course = null;
$results = [];
$questionsByResult = [];
$subjects = [];
$roster = [];
$recentResults = [];

try {
    if ($isStudent && $forcedStudentId <= 0) {
        $pageError = 'Your SBE account is not linked to a student profile. Contact the administrator.';
        sbe_log('student role without linked student profile');
    } else {
        if ($studentId > 0) {
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
            $student = $studentStmt->fetch() ?: null;
            if (!$student) {
                sbe_log("student not found: student_id={$studentId}");
            }
        }

        if ($courseId > 0) {
            $courseStmt = $db->prepare('SELECT course_id, course_code, course_title FROM courses WHERE course_id = :id');
            $courseStmt->execute([':id' => $courseId]);
            $course = $courseStmt->fetch() ?: null;
            if (!$course) {
                sbe_log("course not found: course_id={$courseId}");
            }
        }

        // ---------- DETAIL VIEW: student + course both resolved ----------
        if ($courseId > 0 && $course && $student) {
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
            if (empty($results)) {
                sbe_log("no results: student_id={$studentId} course_id={$courseId}");
            }

            $questionsStmt = $db->prepare(
                'SELECT sa.question_order, sa.selected_option, sa.is_correct, sa.marks_awarded, sa.question_snapshot, sa.answered_at,
                        qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option, qb.marks
                 FROM sbe_student_answers sa
                 LEFT JOIN sbe_question_bank qb ON qb.question_id = sa.question_id
                 WHERE sa.student_exam_id = :seid
                 ORDER BY sa.question_order ASC'
            );
            foreach ($results as $resultRow) {
                $questionsStmt->execute([':seid' => (int) $resultRow['student_exam_id']]);
                $questionsByResult[(int) $resultRow['exam_result_id']] = $questionsStmt->fetchAll();
            }
        }

        // ---------- TEACHER: course picked but no student -> roster ----------
        if ($isTeacher && $courseId > 0 && $course && !$student) {
            $rosterStmt = $db->prepare(
                'SELECT s.student_id, s.roll_no, s.full_name, sec.section_name,
                        r.exam_result_id, r.obtained_marks, r.total_marks, r.percentage, r.pass_fail_status, r.published_at,
                        e.exam_code, e.title AS exam_title
                 FROM sbe_exam_results r
                 JOIN students s ON s.student_id = r.student_id
                 LEFT JOIN sections sec ON sec.section_id = s.section_id
                 JOIN sbe_exams e ON e.exam_id = r.exam_id
                 WHERE e.course_id = :cid
                 ORDER BY s.roll_no, r.exam_result_id DESC'
            );
            $rosterStmt->execute([':cid' => $courseId]);
            $roster = $rosterStmt->fetchAll();
            if (empty($roster)) {
                sbe_log("no roster rows: course_id={$courseId}");
            }
        }

        // ---------- OVERVIEW: no valid course selected ----------
        if ($courseId <= 0 || ($courseId > 0 && !$course)) {
            if ($student) {
                // Student (or teacher browsing one student): subjects that student sat.
                $subStmt = $db->prepare(
                    'SELECT c.course_id, c.course_code, c.course_title,
                            COUNT(DISTINCT r.exam_result_id) AS attempts,
                            MAX(r.percentage) AS best_pct, MAX(r.published_at) AS last_published
                     FROM sbe_exam_results r
                     JOIN sbe_exams e ON e.exam_id = r.exam_id
                     JOIN courses c ON c.course_id = e.course_id
                     WHERE r.student_id = :sid AND r.status = :status
                     GROUP BY c.course_id, c.course_code, c.course_title
                     ORDER BY c.course_code'
                );
                $subStmt->execute([':sid' => $studentId, ':status' => 'Published']);
                $subjects = $subStmt->fetchAll();
                sbe_log('overview(student): subjects=' . count($subjects));
            } else {
                // Teacher: every subject that has SBE results + recent results.
                $subStmt = $db->prepare(
                    'SELECT c.course_id, c.course_code, c.course_title,
                            COUNT(DISTINCT r.student_id) AS students,
                            COUNT(DISTINCT r.exam_result_id) AS results
                     FROM sbe_exam_results r
                     JOIN sbe_exams e ON e.exam_id = r.exam_id
                     JOIN courses c ON c.course_id = e.course_id
                     GROUP BY c.course_id, c.course_code, c.course_title
                     ORDER BY c.course_code'
                );
                $subStmt->execute();
                $subjects = $subStmt->fetchAll();

                $recentStmt = $db->prepare(
                    'SELECT r.exam_result_id, r.student_id, r.obtained_marks, r.total_marks, r.percentage,
                            r.pass_fail_status, r.published_at,
                            s.full_name, s.roll_no, c.course_id, c.course_code, c.course_title, e.exam_code
                     FROM sbe_exam_results r
                     JOIN students s ON s.student_id = r.student_id
                     JOIN sbe_exams e ON e.exam_id = r.exam_id
                     JOIN courses c ON c.course_id = e.course_id
                     ORDER BY r.exam_result_id DESC
                     LIMIT 50'
                );
                $recentStmt->execute();
                $recentResults = $recentStmt->fetchAll();
                sbe_log('overview(teacher): subjects=' . count($subjects) . ' recent=' . count($recentResults));
            }
        }
    }
} catch (Throwable $e) {
    sbe_log('EXCEPTION: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $pageError = 'Something went wrong while loading this page. Please try again.';
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

    <?php if ($pageError !== ''): ?>
        <div class="card page-section">
            <div class="empty-state">
                <div class="empty-icon">&#9888;&#65039;</div>
                <h3>Access restricted</h3>
                <p><?= e($pageError) ?></p>
                <div style="margin-top:14px;">
                    <?php if ($isStudent): ?>
                        <a class="btn btn-ghost" href="student-home.php">&larr; Back to Dashboard</a>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="view-results.php">&larr; Back to View Results</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($courseId > 0 && $course && $student): ?>
        <?php /* ------------------- DETAIL VIEW ------------------- */ ?>
        <div class="page-head">
            <div>
                <h2>Student Result</h2>
                <p>Full answer sheet of <?= e($course['course_code']) ?> &mdash; <?= e($course['course_title']) ?> for <?= e($student['full_name']) ?>.</p>
            </div>
            <div class="actions">
                <a class="btn btn-ghost" href="<?= $isTeacher ? 'view-results.php' : 'student-result.php' ?>">&larr; <?= $isTeacher ? 'Back to View Results' : 'Back to My Results' ?></a>
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
                <h3>No answers submitted yet</h3>
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

    <?php elseif ($isTeacher && $courseId > 0 && $course && !$student): ?>
        <?php /* ------------------- TEACHER: COURSE ROSTER ------------------- */ ?>
        <div class="page-head">
            <div>
                <h2><?= e($course['course_code']) ?> &mdash; <?= e($course['course_title']) ?></h2>
                <p>Students with SBE results in this subject. Click a student to view their answer sheet.</p>
            </div>
            <div class="actions">
                <a class="btn btn-ghost" href="student-result.php">&larr; All Subjects</a>
            </div>
        </div>

        <?php if (empty($roster)): ?>
            <div class="empty-state page-section">
                <div class="empty-icon">&#128202;</div>
                <h3>No answers submitted yet</h3>
                <p>No student has a result recorded for <?= e($course['course_code']) ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-card page-section">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student</th>
                            <th>Section</th>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th>%</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $row): ?>
                            <tr>
                                <td><?= e((string) $row['roll_no']) ?></td>
                                <td><strong><?= e($row['full_name']) ?></strong></td>
                                <td><?= $row['section_name'] ? e($row['section_name']) : '-' ?></td>
                                <td>
                                    <span class="badge badge-manual"><?= e($row['exam_code']) ?></span>
                                    <small class="muted" style="display:block;"><?= e(mb_strimwidth((string) $row['exam_title'], 0, 30, '...')) ?></small>
                                </td>
                                <td><strong><?= number_format((float) $row['obtained_marks'], 2) ?></strong> / <?= number_format((float) $row['total_marks'], 2) ?></td>
                                <td><?= number_format((float) $row['percentage'], 1) ?>%</td>
                                <td><span class="badge badge-<?= $row['pass_fail_status'] === 'Pass' ? 'pass' : 'fail' ?>"><?= grade_letter((float) $row['percentage']) ?></span></td>
                                <td><span class="badge badge-<?= $row['pass_fail_status'] === 'Pass' ? 'pass' : 'fail' ?>"><?= e($row['pass_fail_status']) ?></span></td>
                                <td>
                                    <a class="btn btn-ghost btn-sm" href="student-result.php?student_id=<?= (int) $row['student_id'] ?>&course_id=<?= (int) $courseId ?>">View Answers</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <?php /* ------------------- OVERVIEW ------------------- */ ?>
        <div class="page-head">
            <div>
                <h2><?= $isStudent ? 'My Results' : 'Student Answers' ?></h2>
                <p>
                    <?= $isStudent
                        ? 'Your submitted SBE answers across all subjects.'
                        : 'Browse subjects and students to inspect submitted answer sheets.' ?>
                </p>
            </div>
            <div class="actions">
                <?php if (!$isStudent && !$student): ?>
                    <a class="btn btn-ghost" href="view-results.php">Open View Results</a>
                <?php elseif ($student): ?>
                    <a class="btn btn-ghost" href="student-result.php">&larr; <?= $isStudent ? 'My Results' : 'All Subjects' ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-section">
            <?php if (empty($subjects) && empty($recentResults)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#128202;</div>
                    <h3>No answers submitted yet</h3>
                    <p><?= $isStudent ? 'You have not submitted any SBE exam yet.' : 'No SBE results have been recorded yet.' ?></p>
                </div>
            <?php else: ?>
                <div class="table-card">
                    <h3 style="margin:0 0 12px;">Subjects</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <?php if ($student): ?>
                                    <th>Attempts</th>
                                    <th>Best %</th>
                                    <th>Last Published</th>
                                <?php else: ?>
                                    <th>Students</th>
                                    <th>Results</th>
                                <?php endif; ?>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $sub): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($sub['course_code']) ?></strong>
                                        <small class="muted" style="display:block;"><?= e($sub['course_title']) ?></small>
                                    </td>
                                    <?php if ($student): ?>
                                        <td><?= (int) $sub['attempts'] ?></td>
                                        <td>
                                            <?php if ($sub['best_pct'] !== null): ?>
                                                <span class="badge badge-pass"><?= number_format((float) $sub['best_pct'], 1) ?>%</span>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= $sub['last_published'] ? e(date('M d, Y', strtotime((string) $sub['last_published']))) : '&mdash;' ?></td>
                                    <?php else: ?>
                                        <td><?= (int) $sub['students'] ?></td>
                                        <td><?= (int) $sub['results'] ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <a class="btn btn-ghost btn-sm" href="student-result.php?course_id=<?= (int) $sub['course_id'] ?>">
                                            <?= $student ? 'View My Answers' : 'Browse Students' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($isTeacher && !empty($recentResults)): ?>
                    <div class="table-card page-section">
                        <h3 style="margin:0 0 12px;">Recent Results</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Roll No</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentResults as $rc): ?>
                                    <tr>
                                        <td><strong><?= e($rc['full_name']) ?></strong></td>
                                        <td><?= e((string) $rc['roll_no']) ?></td>
                                        <td class="small"><?= e($rc['course_code']) ?> &mdash; <?= e(mb_strimwidth((string) $rc['course_title'], 0, 30, '...')) ?></td>
                                        <td><span class="badge badge-manual"><?= e($rc['exam_code']) ?></span></td>
                                        <td><strong><?= number_format((float) $rc['obtained_marks'], 2) ?></strong> / <?= number_format((float) $rc['total_marks'], 2) ?></td>
                                        <td><?= number_format((float) $rc['percentage'], 1) ?>%</td>
                                        <td><span class="badge badge-<?= $rc['pass_fail_status'] === 'Pass' ? 'pass' : 'fail' ?>"><?= e($rc['pass_fail_status']) ?></span></td>
                                        <td>
                                            <a class="btn btn-ghost btn-sm" href="student-result.php?student_id=<?= (int) $rc['student_id'] ?>&course_id=<?= (int) $rc['course_id'] ?>">View Answers</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
console.log('[SBE][student-result]', {
    role: <?= json_encode($user['role'] ?? '') ?>,
    studentId: <?= (int) $studentId ?>,
    courseId: <?= (int) $courseId ?>,
    resultsLoaded: <?= count($results) ?>,
    questionsLoaded: <?= array_sum(array_map('count', $questionsByResult)) ?>,
    subjects: <?= count($subjects) ?>,
    pageError: <?= json_encode($pageError) ?>
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
