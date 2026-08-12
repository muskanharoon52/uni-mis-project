<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'examination';
$pageTitle = 'Examination Results';

// Get student_id from the students table
$studentStmt = db()->prepare('SELECT student_id FROM students WHERE user_id = ? LIMIT 1');
$studentStmt->execute([(int) $user['id']]);
$studentId = (int) ($studentStmt->fetchColumn() ?: 0);

// Fetch published exam results for this student
$results = [];
if ($studentId > 0) {
    $stmt = db()->prepare(
        'SELECT er.*, es.exam_type, es.date AS exam_date, es.start_time, es.end_time, es.room,
                c.course_code, c.course_title
         FROM exam_results er
         JOIN exam_schedules es ON es.exam_id = er.exam_id
         JOIN courses c ON c.course_id = es.course_id
         WHERE er.student_id = ? AND er.status = \'published\'
         ORDER BY es.date DESC, c.course_code'
    );
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll();
}

// Fetch SBE results published by SSO (approved publish applications)
$sbeResults = [];
if ($studentId > 0) {
    $stmt = db()->prepare(
        'SELECT er.exam_result_id, er.exam_id, er.obtained_marks, er.total_marks, er.percentage,
                er.pass_fail_status, er.remarks, er.published_at,
                e.exam_code, e.title AS exam_title, e.exam_type,
                c.course_code, c.course_title,
                app.id AS app_id, app.transcript_path, app.reviewed_at
         FROM sbe_exam_results er
         JOIN sbe_exams e ON e.exam_id = er.exam_id
         JOIN courses c ON c.course_id = e.course_id
         JOIN result_publish_applications app ON app.exam_result_id = er.exam_result_id AND app.status = \'approved\'
         WHERE er.student_id = ? AND er.status = \'Published\'
         ORDER BY app.reviewed_at DESC, er.exam_result_id DESC'
    );
    $stmt->execute([$studentId]);
    $sbeResults = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>

    <?php if (!$sbeResults && !$results): ?>
        <p class="muted" style="padding: 20px;">No published examination results found.</p>
    <?php endif; ?>

    <?php if ($sbeResults): ?>
        <div style="padding: 16px 20px 0;">
            <h4 style="margin: 0 0 12px;">SBE Examination Results</h4>
            <div class="table-responsive">
                <table>
                    <tr>
                        <th>Course</th>
                        <th>Exam</th>
                        <th>Type</th>
                        <th>Published On</th>
                        <th>Marks</th>
                        <th>Total</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Result</th>
                        <th>Transcript</th>
                    </tr>
                    <?php foreach ($sbeResults as $r): ?>
                        <?php
                        $pct = (float) $r['percentage'];
                        $grade = $pct >= 90 ? 'A+' : ($pct >= 80 ? 'A' : ($pct >= 70 ? 'B' : ($pct >= 60 ? 'C' : ($pct >= 50 ? 'D' : 'F'))));
                        ?>
                        <tr>
                            <td><?= e($r['course_code'] . ' - ' . $r['course_title']) ?></td>
                            <td><?= e($r['exam_code']) ?><br><span class="muted" style="font-size:.8em;"><?= e($r['exam_title']) ?></span></td>
                            <td><?= e($r['exam_type']) ?></td>
                            <td><?= $r['reviewed_at'] ? e(date('d M Y h:i A', strtotime((string) $r['reviewed_at']))) : '-' ?></td>
                            <td><?= e(number_format((float) $r['obtained_marks'], 2)) ?></td>
                            <td><?= e(number_format((float) $r['total_marks'], 2)) ?></td>
                            <td><?= e(number_format($pct, 1)) ?>%</td>
                            <td><strong><?= e($grade) ?></strong></td>
                            <td><strong style="color: <?= $r['pass_fail_status'] === 'Pass' ? 'var(--success, #059669)' : 'var(--error, #dc2626)' ?>;"><?= e($r['pass_fail_status']) ?></strong></td>
                            <td>
                                <?php if ($r['transcript_path']): ?>
                                    <a href="transcript.php?app_id=<?= (int) $r['app_id'] ?>" target="_blank" style="white-space:nowrap;">
                                        &#128196; Download Transcript
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($results): ?>
        <div style="padding: <?= $sbeResults ? '24px 20px 16px' : '16px 20px 0' ?>;">
            <h4 style="margin: 0 0 12px;">Examination Results</h4>
            <div class="table-responsive">
                <table>
                    <tr>
                        <th>Course</th>
                        <th>Exam Type</th>
                        <th>Date</th>
                        <th>Marks Obtained</th>
                        <th>Total Marks</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= e($r['course_code'] . ' - ' . $r['course_title']) ?></td>
                            <td><?= e($r['exam_type']) ?></td>
                            <td><?= e($r['exam_date']) ?></td>
                            <td><?= e(number_format((float) $r['marks_obtained'], 2)) ?></td>
                            <td><?= e(number_format((float) $r['total_marks'], 2)) ?></td>
                            <td><?= e(number_format((float) $r['percentage'], 1)) ?>%</td>
                            <td><strong><?= e($r['grade'] ?: '-') ?></strong></td>
                            <td><?= e($r['remarks'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
