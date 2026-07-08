<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'assignments';
$pageTitle = 'Grading';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        if (!teacher_owns_submission((int) $user['id'], $submissionId)) {
            throw new RuntimeException('You cannot grade this submission.');
        }
        $grade = $_POST['grade'] === '' ? null : (float) $_POST['grade'];
        if ($grade !== null && ($grade < 0 || $grade > 100)) {
            throw new RuntimeException('Grade must be between 0 and 100.');
        }
        $stmt = db()->prepare('UPDATE submissions SET grade = ?, feedback = ? WHERE id = ?');
        $stmt->execute([$grade, trim((string) ($_POST['feedback'] ?? '')), $submissionId]);
        $message = 'Submission graded.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$submissionsStmt = db()->prepare(
    'SELECT s.*, a.title, c.code, u.name AS student_name
     FROM submissions s
     JOIN assignments a ON a.id = s.assignment_id
     JOIN courses c ON c.id = a.course_id
     JOIN users u ON u.id = s.student_id
     WHERE c.teacher_id = ?
     ORDER BY s.submitted_at DESC'
);
$submissionsStmt->execute([$user['id']]);
$submissions = $submissionsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Grading</h1></div>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="table-card">
    <table>
        <tr><th>Course</th><th>Assignment</th><th>Student</th><th>Submission</th><th>Grade</th></tr>
        <?php foreach ($submissions as $submission): ?>
            <tr>
                <td><?= e($submission['code']) ?></td>
                <td><?= e($submission['title']) ?></td>
                <td><?= e($submission['student_name']) ?></td>
                <td>
                    <?php if ($submission['submission_file']): ?><a href="<?= app_url($submission['submission_file']) ?>" target="_blank">Download file</a><?php else: ?><span class="muted">No file</span><?php endif; ?>
                    <?php if ($submission['content']): ?><br><span class="muted"><?= e($submission['content']) ?></span><?php endif; ?>
                </td>
                <td>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>">
                        <input name="grade" type="number" step="0.01" min="0" max="100" value="<?= e((string) $submission['grade']) ?>" placeholder="Grade">
                        <input name="feedback" value="<?= e($submission['feedback']) ?>" placeholder="Feedback">
                        <button class="btn" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$submissions): ?>
            <tr><td colspan="5" class="muted text-center">No assignment submissions uploaded yet.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
