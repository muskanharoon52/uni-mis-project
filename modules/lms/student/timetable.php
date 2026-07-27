<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'timetable';
$pageTitle = 'Class Timetable';

$enrolledStmt = db()->prepare('SELECT course_id FROM lms_enrollments WHERE student_user_id = ?');
$enrolledStmt->execute([(int) $user['id']]);
$enrolledCourseIds = $enrolledStmt->fetchAll(PDO::FETCH_COLUMN);

$timetable = [];
if (!empty($enrolledCourseIds)) {
    $placeholders = implode(',', array_fill(0, count($enrolledCourseIds), '?'));
    $ttStmt = db()->prepare(
        "SELECT t.day_of_week, t.start_time, t.end_time, t.room_no,
                c.course_code, c.course_title, te.teacher_name
         FROM timetable t
         JOIN courses c ON c.course_id = t.course_id
         JOIN teachers te ON te.teacher_id = t.teacher_id
         WHERE t.course_id IN ($placeholders)
         ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time"
    );
    $ttStmt->execute($enrolledCourseIds);
    $timetable = $ttStmt->fetchAll();
}

$dayOrder = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (empty($timetable)): ?>
        <p class="muted" style="padding: 20px;">No timetable entries found for your enrolled courses.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $currentDay = '';
            foreach ($timetable as $row):
                $dayClass = $row['day_of_week'] !== $currentDay;
                $currentDay = $row['day_of_week'];
            ?>
                <tr>
                    <?php if ($dayClass): ?>
                        <td style="font-weight:600; background:var(--primary-bg, #eef2ff);"><?= e($row['day_of_week']) ?></td>
                    <?php else: ?>
                        <td style="background:var(--primary-bg, #eef2ff);"></td>
                    <?php endif; ?>
                    <td><?= e(substr($row['start_time'], 0, 5) . ' – ' . substr($row['end_time'], 0, 5)) ?></td>
                    <td><strong><?= e($row['course_code']) ?></strong> – <?= e($row['course_title']) ?></td>
                    <td><?= e($row['teacher_name']) ?></td>
                    <td><?= e($row['room_no']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>