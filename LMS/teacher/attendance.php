<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'attendance';
$pageTitle = 'Attendance';
$message = '';
$error = '';

$coursesStmt = db()->prepare('SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_code');
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();
$selectedCourseId = (int) ($_POST['course_id'] ?? $_GET['course_id'] ?? ($courses[0]['course_id'] ?? 0));
$selectedDate = (string) ($_POST['class_date'] ?? $_GET['class_date'] ?? date('Y-m-d'));

if ($selectedCourseId && !teacher_owns_course((int) $user['id'], $selectedCourseId)) {
    $selectedCourseId = (int) ($courses[0]['course_id'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!$selectedCourseId || !teacher_owns_course((int) $user['id'], $selectedCourseId)) {
            throw new RuntimeException('Select one of your courses first.');
        }

        $stmt = db()->prepare(
            'INSERT INTO attendance (course_id, student_id, class_date, status)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)'
        );

        foreach (($_POST['status'] ?? []) as $studentId => $status) {
            $studentId = (int) $studentId;
            $status = (string) $status;
            if (in_array($status, ['Present', 'Late', 'Absent'], true) && student_enrolled_in_course($studentId, $selectedCourseId)) {
                $stmt->execute([$selectedCourseId, $studentId, $selectedDate, $status]);
            }
        }
        $message = 'Attendance saved.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$roster = [];
if ($selectedCourseId) {
    $rosterStmt = db()->prepare(
        'SELECT u.user_id, u.full_name, u.email, COALESCE(a.status, "Present") AS status
         FROM lms_enrollments e
         JOIN users u ON u.user_id = e.student_user_id
         LEFT JOIN attendance a ON a.course_id = e.course_id AND a.student_id = e.student_user_id AND a.class_date = ?
         WHERE e.course_id = ?
         ORDER BY u.full_name'
    );
    $rosterStmt->execute([$selectedDate, $selectedCourseId]);
    $roster = $rosterStmt->fetchAll();
}

$records = db()->prepare(
    'SELECT a.*, c.course_code, u.full_name AS student_name
     FROM attendance a
     JOIN courses c ON c.course_id = a.course_id
     JOIN users u ON u.user_id = a.student_id
     WHERE c.teacher_id = ?
     ORDER BY a.class_date DESC, c.course_code, u.full_name
     LIMIT 40'
);
$records->execute([$user['id']]);

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Class Attendance</h3></div>
    <div class="inline-form-row" style="padding:0 0 0 0;">
        <form class="inline-form-row" method="get" style="width:100%;">
            <div>
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?= (int) $course['course_id'] ?>" <?= (int) $course['course_id'] === $selectedCourseId ? 'selected' : '' ?>>
                        <?= e($course['course_code'] . ' - ' . $course['course_title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="class_date">Date</label>
                <input id="class_date" name="class_date" type="date" value="<?= e($selectedDate) ?>" required>
            </div>
            <button class="btn btn-outline" type="submit">Load Roster</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="course_id" value="<?= (int) $selectedCourseId ?>">
        <input type="hidden" name="class_date" value="<?= e($selectedDate) ?>">
        <table>
            <tr><th>Student</th><th>Email</th><th>Status</th></tr>
            <?php foreach ($roster as $student): ?>
                <tr>
                    <td><?= e($student['full_name']) ?></td>
                    <td><?= e($student['email']) ?></td>
                    <td>
                        <select name="status[<?= (int) $student['user_id'] ?>]">
                            <option value="Present" <?= $student['status'] === 'Present' ? 'selected' : '' ?>>Present</option>
                            <option value="Late" <?= $student['status'] === 'Late' ? 'selected' : '' ?>>Late</option>
                            <option value="Absent" <?= $student['status'] === 'Absent' ? 'selected' : '' ?>>Absent</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$roster): ?><tr><td colspan="3" class="muted">No students are enrolled in this course yet.</td></tr><?php endif; ?>
        </table>
        <?php if ($roster): ?><button class="btn btn-primary" type="submit">Save Attendance</button><?php endif; ?>
    </form>
</div>

<div class="card mt-4">
    <div class="card-header"><h3>Recent Records</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Date</th><th>Course</th><th>Student</th><th>Status</th></tr>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?= e($record['class_date']) ?></td>
                    <td><?= e($record['course_code']) ?></td>
                    <td><?= e($record['student_name']) ?></td>
                    <td><span class="badge badge-<?= $record['status'] === 'Present' ? 'active' : ($record['status'] === 'Late' ? 'draft' : 'inactive') ?>"><?= e($record['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
