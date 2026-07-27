<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'examination';
$pageTitle = 'Examination Results';

$teacherId = (int) $user['teacher_id'];

// Fetch exam results for courses owned by this teacher
$results = [];
if ($teacherId > 0) {
    $stmt = db()->prepare(
        'SELECT er.*, es.exam_type, es.date AS exam_date,
                c.course_code, c.course_title,
                s.full_name AS student_name, s.roll_no
         FROM exam_results er
         JOIN exam_schedules es ON es.exam_id = er.exam_id
         JOIN courses c ON c.course_id = es.course_id
         JOIN students s ON s.student_id = er.student_id
         WHERE c.teacher_id = ? AND er.status = \'published\'
         ORDER BY c.course_code, s.full_name, es.date'
    );
    $stmt->execute([$teacherId]);
    $results = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (!$results): ?>
        <p class="muted" style="padding: 20px;">No published examination results found for your courses.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table>
            <tr>
                <th>Course</th>
                <th>Student</th>
                <th>Roll No</th>
                <th>Exam Type</th>
                <th>Date</th>
                <th>Marks</th>
                <th>Total</th>
                <th>Grade</th>
            </tr>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= e($r['course_code']) ?></td>
                    <td><?= e($r['student_name']) ?></td>
                    <td><?= e($r['roll_no'] ?? '-') ?></td>
                    <td><?= e($r['exam_type']) ?></td>
                    <td><?= e($r['exam_date']) ?></td>
                    <td><?= e(number_format((float) $r['marks_obtained'], 2)) ?></td>
                    <td><?= e(number_format((float) $r['total_marks'], 2)) ?></td>
                    <td><strong><?= e($r['grade'] ?: '-') ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
