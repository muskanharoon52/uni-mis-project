<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'datesheet';
$pageTitle = 'Exam Datesheet';

// Get enrolled course IDs for this student
$enrolledStmt = db()->prepare('SELECT course_id FROM lms_enrollments WHERE student_user_id = ?');
$enrolledStmt->execute([(int) $user['id']]);
$enrolledCourseIds = $enrolledStmt->fetchAll(PDO::FETCH_COLUMN);

$datesheets = [];
if (!empty($enrolledCourseIds)) {
    $placeholders = implode(',', array_fill(0, count($enrolledCourseIds), '?'));
    $dsStmt = db()->prepare(
        "SELECT d.*, c.course_code, c.course_title
         FROM lms_datesheets d
         JOIN courses c ON c.course_id = d.course_id
         WHERE d.course_id IN ($placeholders)
         ORDER BY d.exam_date ASC, d.start_time ASC"
    );
    $dsStmt->execute($enrolledCourseIds);
    $datesheets = $dsStmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (empty($datesheets)): ?>
        <p class="muted" style="padding: 20px;">No upcoming exams in your datesheet.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table>
            <tr>
                <th>Course</th>
                <th>Exam Type</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Room</th>
            </tr>
            <?php foreach ($datesheets as $ds): ?>
                <tr>
                    <td><?= e($ds['course_code'] . ' - ' . $ds['course_title']) ?></td>
                    <td><?= e(ucfirst($ds['exam_type'])) ?></td>
                    <td><?= e($ds['exam_date']) ?></td>
                    <td><?= e($ds['start_time']) ?></td>
                    <td><?= e($ds['end_time']) ?></td>
                    <td><?= e($ds['room']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
