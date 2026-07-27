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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (!$results): ?>
        <p class="muted" style="padding: 20px;">No published examination results found.</p>
    <?php else: ?>
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
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
