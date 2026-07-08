<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'attendance';
$pageTitle = 'Attendance';

$coursesStmt = db()->prepare(
    'SELECT c.id, c.code, c.title
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     WHERE e.student_id = ?
     ORDER BY c.code'
);
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : ((int) ($courses[0]['id'] ?? 0));
$attendanceRows = [];

if ($courseId > 0 && student_enrolled_in_course((int) $user['id'], $courseId)) {
    $attendanceStmt = db()->prepare(
        'SELECT a.class_date, a.status, c.code, c.title, c.semester
         FROM attendance a
         JOIN courses c ON c.id = a.course_id
         WHERE a.student_id = ? AND a.course_id = ?
         ORDER BY a.class_date DESC'
    );
    $attendanceStmt->execute([$user['id'], $courseId]);
    $attendanceRows = $attendanceStmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Attendance</h1>
        <p class="muted">View your attendance by course.</p>
    </div>
</div>

<section class="course-layout">
    <aside class="course-list-panel">
        <div class="course-list-head">Courses</div>
        <?php foreach ($courses as $course): ?>
            <a class="course-list-item <?= (int) $course['id'] === $courseId ? 'active' : '' ?>" href="<?= app_url('student/attendance.php?course_id=' . (int) $course['id']) ?>">
                <strong><?= e($course['code']) ?></strong>
                <span><?= e($course['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="course-detail-panel">
        <div class="table-card">
            <h2>Attendance Record</h2>
            <table>
                <tr><th>Date</th><th>Status</th><th>Course</th><th>Semester</th></tr>
                <?php foreach ($attendanceRows as $row): ?>
                    <tr>
                        <td><?= e($row['class_date']) ?></td>
                        <td><span class="badge <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
                        <td><?= e($row['code'] . ' - ' . $row['title']) ?></td>
                        <td><?= e($row['semester']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$attendanceRows): ?>
                    <tr><td colspan="4" class="muted">No attendance records found for this course.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </section>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
