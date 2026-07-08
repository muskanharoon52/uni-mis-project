<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'assignments';
$pageTitle = 'Assignments';
$message = '';
$error = '';

$coursesStmt = db()->prepare('SELECT * FROM courses WHERE teacher_id = ? ORDER BY code');
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $courseId = (int) ($_POST['course_id'] ?? 0);
        if (!teacher_owns_course((int) $user['id'], $courseId)) {
            throw new RuntimeException('Select one of your courses.');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Assignment title is required.');
        }

        $filePath = save_uploaded_file('assignment_file', 'assignments', ['pdf', 'doc', 'docx', 'zip']);
        $stmt = db()->prepare('INSERT INTO assignments (course_id, title, description, file_path, due_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$courseId, $title, trim((string) ($_POST['description'] ?? '')), $filePath, (string) $_POST['due_date']]);

        $courseStmt = db()->prepare('SELECT code FROM courses WHERE id = ? LIMIT 1');
        $courseStmt->execute([$courseId]);
        $courseCode = (string) $courseStmt->fetchColumn();
        notify_course_students($courseId, 'New assignment posted', 'An assignment titled "' . $title . '" has been uploaded for ' . $courseCode . '.', app_url('student/courses.php?course_id=' . $courseId . '&view=assignment'));
        $message = 'Assignment created.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$assignmentsStmt = db()->prepare(
    'SELECT a.*, c.code,
        COUNT(DISTINCT e.student_id) AS enrolled_count,
        COUNT(DISTINCT s.id) AS submitted_count,
        SUM(s.grade IS NULL AND s.id IS NOT NULL) AS ungraded_count
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     LEFT JOIN enrollments e ON e.course_id = c.id
     LEFT JOIN submissions s ON s.assignment_id = a.id
     WHERE c.teacher_id = ?
     GROUP BY a.id
     ORDER BY a.due_date'
);
$assignmentsStmt->execute([$user['id']]);
$assignments = $assignmentsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Assignments</h1></div>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<section class="grid">
    <form class="card" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <h2>Create Assignment</h2>
        <label for="course_id">Course</label>
        <select id="course_id" name="course_id" required>
            <?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>"><?= e($course['code'] . ' - ' . $course['title']) ?></option><?php endforeach; ?>
        </select>
        <label for="title">Title</label>
        <input id="title" name="title" required>
        <label for="description">Description</label>
        <textarea id="description" name="description"></textarea>
        <label for="assignment_file">Assignment File</label>
        <input id="assignment_file" name="assignment_file" type="file" accept=".pdf,.doc,.docx,.zip">
        <label for="due_date">Due Date</label>
        <input id="due_date" name="due_date" type="date" required>
        <button class="btn" type="submit">Create</button>
    </form>
    <div class="table-card">
        <h2>Assignment List</h2>
        <table>
            <tr><th>Course</th><th>Title</th><th>Submissions</th><th>Ungraded</th><th>File</th><th>Due</th></tr>
            <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td><?= e($assignment['code']) ?></td>
                    <td><?= e($assignment['title']) ?></td>
                    <td><?= (int) $assignment['submitted_count'] ?> / <?= (int) $assignment['enrolled_count'] ?></td>
                    <td><?= (int) $assignment['ungraded_count'] ?></td>
                    <td>
                        <?php if ($assignment['file_path']): ?><a href="<?= app_url($assignment['file_path']) ?>" target="_blank">Download</a><?php else: ?><span class="muted">No file</span><?php endif; ?>
                    </td>
                    <td><?= e($assignment['due_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$assignments): ?>
                <tr><td colspan="6" class="muted text-center">No assignments created yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
