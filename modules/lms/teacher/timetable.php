<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'timetable';
$pageTitle = 'Class Timetable';

$teacherId = (int) $user['teacher_id'];

$timetable = [];
if ($teacherId > 0) {
    $stmt = db()->prepare(
        "SELECT t.day_of_week, t.start_time, t.end_time, t.room_no,
                c.course_code, c.course_title
         FROM timetable t
         JOIN courses c ON c.course_id = t.course_id
         WHERE t.teacher_id = ?
         ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time"
    );
    $stmt->execute([$teacherId]);
    $timetable = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (empty($timetable)): ?>
        <p class="muted" style="padding: 20px;">No timetable entries found for your assigned courses.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Course</th>
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
                    <td><?= e($row['room_no']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>