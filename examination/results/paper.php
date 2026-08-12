<?php
// examination/results/paper.php - Drill into a single student's SBE exam paper.

$page_title = 'Student Paper';

require_once '../../config/db_connect.php';
require_once '../../modules/sbe/config/database.php';
require_once '../../modules/sbe/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = db();

$examResultId = !empty($_GET['exam_result_id']) ? (int) $_GET['exam_result_id'] : 0;

if ($examResultId <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare(
    'SELECT er.*, e.exam_code, e.title AS exam_title, e.exam_type, e.course_id, e.duration_minutes,
            se.attempt_no, se.started_at, se.submitted_at, se.time_taken_seconds,
            s.student_id, s.roll_no, s.full_name, s.father_name, s.batch_year,
            sem.semester_name, sec.section_name, p.program_name,
            app.id AS app_id, app.status AS app_status, app.reviewer_remarks, app.transcript_path
     FROM sbe_exam_results er
     JOIN sbe_exams e ON e.exam_id = er.exam_id
     LEFT JOIN sbe_student_exams se ON se.student_exam_id = er.student_exam_id
     JOIN students s ON s.student_id = er.student_id
     LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
     LEFT JOIN sections sec ON sec.section_id = s.section_id
     LEFT JOIN programs p ON p.program_id = s.program_id
     LEFT JOIN result_publish_applications app ON app.exam_result_id = er.exam_result_id
     WHERE er.exam_result_id = :id'
);
$stmt->execute([':id' => $examResultId]);
$result = $stmt->fetch();

if (!$result) {
    header('Location: index.php');
    exit;
}

$questionsStmt = $db->prepare(
    'SELECT sa.question_order, sa.selected_option, sa.is_correct, sa.marks_awarded, sa.question_snapshot, sa.answered_at,
            qb.question_text, qb.option_a, qb.option_b, qb.option_c, qb.option_d, qb.correct_option, qb.marks
     FROM sbe_student_answers sa
     LEFT JOIN sbe_question_bank qb ON qb.question_id = sa.question_id
     WHERE sa.student_exam_id = :seid
     ORDER BY sa.question_order ASC'
);
$questionsStmt->execute([':seid' => (int) $result['student_exam_id']]);
$questions = $questionsStmt->fetchAll();

$correctCount = 0;
foreach ($questions as $q) {
    if ((int) $q['is_correct'] === 1) $correctCount++;
}

if (!function_exists('grade_letter')) {
    function grade_letter(?float $pct): string
    {
        if ($pct === null) return '-';
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
        if ($seconds === null) return '-';
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $m > 0 ? sprintf('%d min %d sec', $m, $s) : sprintf('%d sec', $s);
    }
}

$canRequest = $result['status'] === 'Draft'
    && ($result['app_status'] === null || $result['app_status'] === 'rejected');

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Student Paper</h4>
            <p style="color:var(--text-secondary);font-size:13px;margin:2px 0 0;">
                <?= htmlspecialchars($result['exam_code']) ?> &mdash; <?= htmlspecialchars($result['exam_title']) ?> for <?= htmlspecialchars($result['full_name']) ?>
            </p>
        </div>
        <div class="page-header-actions">
            <?php if ($canRequest): ?>
                <form method="post" action="accept.php" style="display:inline;" onsubmit="return confirm('Accept this result and send a publish request to SSO?')">
                    <input type="hidden" name="exam_result_id" value="<?= (int) $examResultId ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Accept &amp; Send to SSO
                    </button>
                </form>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Results
            </a>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-content">
            <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">
                <div style="flex:1; min-width:220px;">
                    <h5 style="margin:0;"><?= htmlspecialchars($result['full_name']) ?></h5>
                    <p class="small" style="margin:4px 0 0; color:var(--text-secondary);">
                        Roll: <strong><?= htmlspecialchars((string) $result['roll_no']) ?></strong>
                        &middot; Student ID: <strong><?= (int) $result['student_id'] ?></strong>
                        <?= $result['father_name'] ? '&middot; Father: ' . htmlspecialchars($result['father_name']) : '' ?>
                    </p>
                </div>
                <div style="flex:1; min-width:220px; display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if ($result['program_name']): ?><span class="status-badge badge-exam-quiz"><?= htmlspecialchars($result['program_name']) ?></span><?php endif; ?>
                    <?php if ($result['section_name']): ?><span class="status-badge badge-exam-mid">Section <?= htmlspecialchars($result['section_name']) ?></span><?php endif; ?>
                    <?php if ($result['semester_name']): ?><span class="status-badge badge-exam-mid"><?= htmlspecialchars($result['semester_name']) ?></span><?php endif; ?>
                    <?php if ($result['batch_year']): ?><span class="status-badge badge-exam-final">Batch <?= (int) $result['batch_year'] ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    $pct = (float) $result['percentage'];
    $gst = $result['pass_fail_status'] === 'Pass'
        ? 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);'
        : 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);';
    ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon stat-card-primary">&#128202;</div>
            <div class="stat-number"><?= number_format((float) $result['obtained_marks'], 2) ?> <span style="font-size:.9rem;color:var(--text-secondary);">/ <?= number_format((float) $result['total_marks'], 2) ?></span></div>
            <div class="stat-label">Obtained Marks</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-info">&#128200;</div>
            <div class="stat-number"><?= number_format($pct, 1) ?>%</div>
            <div class="stat-label">Percentage</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-card-success">&#127891;</div>
            <div class="stat-number"><?= grade_letter($pct) ?></div>
            <div class="stat-label">Grade</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?= $result['pass_fail_status'] === 'Pass' ? 'stat-card-success' : 'stat-card-warning' ?>">&#128203;</div>
            <div class="stat-number"><span class="status-badge" style="<?= $gst ?>"><?= htmlspecialchars($result['pass_fail_status']) ?></span></div>
            <div class="stat-label">Result Status</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
                <div>
                    <h5 style="margin:0;">
                        <?= htmlspecialchars($result['exam_code']) ?> &mdash; <?= htmlspecialchars(mb_strimwidth((string) $result['exam_title'], 0, 60, '...')) ?>
                    </h5>
                    <p class="small" style="margin:4px 0 0;color:var(--text-secondary);">
                        Attempt #<?= (int) $result['attempt_no'] ?>
                        &middot; Result: <strong><?= htmlspecialchars($result['status']) ?></strong>
                        <?php if ($result['published_at']): ?>&middot; Published <?= date('M d, Y h:i A', strtotime((string) $result['published_at'])) ?><?php endif; ?>
                    </p>
                </div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;">
                    <span><strong><?= count($questions) ?></strong> questions</span>
                    <span><strong><?= $correctCount ?></strong> correct</span>
                    <span>Time: <strong><?= format_duration($result['time_taken_seconds'] !== null ? (int) $result['time_taken_seconds'] : null) ?></strong></span>
                </div>
            </div>
        </div>
        <div class="card-content" style="padding:0;">
            <?php if (empty($questions)): ?>
                <div style="padding:24px; color:var(--text-secondary);">No answer detail recorded for this attempt.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
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
                                    if (is_array($decoded)) $snap = $decoded;
                                }
                                $qText      = $snap['question_text'] ?? $q['question_text'] ?? '';
                                $opts       = [
                                    'A' => $snap['option_a'] ?? $q['option_a'] ?? '',
                                    'B' => $snap['option_b'] ?? $q['option_b'] ?? '',
                                    'C' => $snap['option_c'] ?? $q['option_c'] ?? '',
                                    'D' => $snap['option_d'] ?? $q['option_d'] ?? '',
                                ];
                                $correctOpt = $snap['correct_option'] ?? $q['correct_option'] ?? '';
                                $marksPossible = (float) ($snap['marks'] ?? $q['marks'] ?? 0);
                                $selected   = (string) $q['selected_option'];
                                $isCorrect  = (int) $q['is_correct'] === 1;
                                ?>
                                <tr>
                                    <td><?= (int) $q['question_order'] ?></td>
                                    <td style="min-width:220px;"><?= htmlspecialchars((string) $qText) ?></td>
                                    <td style="min-width:240px;">
                                        <?php foreach ($opts as $key => $text): ?>
                                            <?php if ($text === '' && $key === $correctOpt) { continue; } ?>
                                            <div style="padding:2px 0; <?= $key === $correctOpt ? 'color:var(--success);font-weight:600;' : '' ?>">
                                                <strong><?= $key ?>.</strong> <?= htmlspecialchars((string) $text) ?>
                                                <?= $key === $correctOpt ? '&#10003;' : '' ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php if ($selected === ''): ?>
                                            <span class="status-badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">Not Answered</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="<?= $isCorrect ? 'background:var(--success-bg);color:#065f46;border:1px solid var(--success-border);' : 'background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);' ?>">
                                                <?= htmlspecialchars($selected) ?><?= $isCorrect ? ' &#10003;' : ' &#10007;' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string) $correctOpt) ?></td>
                                    <td>
                                        <strong><?= number_format((float) $q['marks_awarded'], 2) ?></strong>
                                        <span style="color:var(--text-secondary);">/ <?= number_format($marksPossible, 2) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($result['reviewer_remarks']): ?>
        <div class="alert" style="background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border);margin-top:20px;">
            <strong>SSO Reviewer Remarks:</strong> <?= htmlspecialchars($result['reviewer_remarks']) ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
