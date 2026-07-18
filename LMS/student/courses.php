<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'courses';
$pageTitle = 'Courses';

$coursesStmt = db()->prepare(
    'SELECT c.*, u.name AS teacher_name
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     LEFT JOIN users u ON u.id = c.teacher_id
     WHERE e.student_id = ?
     ORDER BY c.code'
);
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();

$currentCourse = null;
if ($courses) {
    $selectedCourseId = (int) ($_GET['course_id'] ?? $courses[0]['id']);
    foreach ($courses as $course) {
        if ((int) $course['id'] === $selectedCourseId) {
            $currentCourse = $course;
            break;
        }
    }
    $currentCourse = $currentCourse ?: $courses[0];
}

$view = (string) ($_GET['view'] ?? 'overview');
$view = in_array($view, ['overview', 'lectures', 'assignment'], true) ? $view : 'overview';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!$currentCourse) {
            throw new RuntimeException('No course is selected.');
        }

        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        if (!$assignmentId || !$courseId || (int) $currentCourse['id'] !== $courseId) {
            throw new RuntimeException('Invalid submission request.');
        }

        $allowedStmt = db()->prepare(
            'SELECT COUNT(*)
             FROM assignments a
             JOIN enrollments e ON e.course_id = a.course_id
             WHERE a.id = ? AND e.student_id = ?'
        );
        $allowedStmt->execute([$assignmentId, $user['id']]);
        if ((int) $allowedStmt->fetchColumn() === 0) {
            throw new RuntimeException('This assignment is not available for your account.');
        }

        $filePath = save_uploaded_file('submission_file', 'submissions', ['pdf', 'doc', 'docx', 'zip']);
        if (!$filePath) {
            throw new RuntimeException('Please upload a PDF, Word document, or ZIP file.');
        }

        $stmt = db()->prepare(
            'INSERT INTO submissions (assignment_id, student_id, content, submission_file)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE content = VALUES(content), submission_file = VALUES(submission_file), submitted_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$assignmentId, $user['id'], 'Assignment submission', $filePath]);
        $message = 'Assignment uploaded.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="course-layout">
    <aside class="course-list-panel">
        <div class="course-list-head">My Courses</div>
        <?php foreach ($courses as $course): ?>
            <a class="course-list-item <?= $currentCourse && (int) $currentCourse['id'] === (int) $course['id'] ? 'active' : '' ?>" href="<?= app_url('student/courses.php?course_id=' . (int) $course['id']) ?>">
                <strong><?= e($course['code']) ?></strong>
                <span><?= e($course['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="course-detail-panel">
        <?php if (!$currentCourse): ?>
            <div class="card">
                <h1>My Courses</h1>
                <p class="muted">No enrolled courses found.</p>
            </div>
        <?php else: ?>
            <div class="course-summary card">
                <h1><?= e($currentCourse['code'] . ' - ' . $currentCourse['title']) ?></h1>
                <p class="muted">Teacher: <?= e($currentCourse['teacher_name'] ?: 'Not assigned') ?></p>
                <p><?= e($currentCourse['description'] ?: 'No course description available.') ?></p>
            </div>

            <div class="course-tabs">
                <a class="course-tab <?= $view === 'overview' ? 'active' : '' ?>" href="<?= app_url('student/courses.php?course_id=' . (int) $currentCourse['id'] . '&view=overview') ?>">Overview</a>
                <a class="course-tab <?= $view === 'lectures' ? 'active' : '' ?>" href="<?= app_url('student/courses.php?course_id=' . (int) $currentCourse['id'] . '&view=lectures') ?>">Lectures</a>
                <a class="course-tab <?= $view === 'assignment' ? 'active' : '' ?>" href="<?= app_url('student/courses.php?course_id=' . (int) $currentCourse['id'] . '&view=assignment') ?>">Assignment</a>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <?php if ($view === 'overview'): ?>
                <div class="card">
                    <h2>Course Overview</h2>
                    <p class="muted">Use the tabs above to view lectures or submit assignments.</p>
                </div>
            <?php elseif ($view === 'lectures'): ?>
                <div class="card">
                    <div class="card-header"><h3>Lectures</h3></div>
                    <div class="table-responsive">
                    <table>
                        <tr><th>Lecture</th><th>Date</th><th>File</th></tr>
                        <?php $lectureStmt = db()->prepare('SELECT * FROM lectures WHERE course_id = ? ORDER BY id DESC'); $lectureStmt->execute([(int) $currentCourse['id']]); $lectures = $lectureStmt->fetchAll(); ?>
                        <?php foreach ($lectures as $lecture): ?>
                            <tr><td><?= e($lecture['title']) ?></td><td><?= e($lecture['lecture_date']) ?></td><td><a href="<?= app_url($lecture['file_path']) ?>" target="_blank">Download</a></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$lectures): ?>
                            <tr><td colspan="3" class="muted text-center">No lectures uploaded for this course yet.</td></tr>
                        <?php endif; ?>
                    </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header"><h3>Assignments</h3></div>
                    <div class="table-responsive">
                    <table>
                        <tr><th>Assignment</th><th>Due</th><th>Status</th><th>Grade</th><th>Feedback</th><th>Teacher File</th><th>Upload</th></tr>
                        <?php
                        $assignmentsStmt = db()->prepare(
                             'SELECT a.*, s.submission_file, s.submitted_at, s.grade, s.feedback
                              FROM assignments a
                              LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
                              WHERE a.course_id = ?
                              ORDER BY a.due_date'
                        );
                        $assignmentsStmt->execute([$user['id'], $currentCourse['id']]);
                        $assignments = $assignmentsStmt->fetchAll();
                        ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $hasSubmission = !empty($assignment['submitted_at']); $isLate = !$hasSubmission && $assignment['due_date'] < date('Y-m-d'); ?>
                            <tr>
                                <td><?= e($assignment['title']) ?></td>
                                <td><?= e($assignment['due_date']) ?></td>
                                <td>
                                    <?php if ($hasSubmission): ?><span class="badge badge-active">Submitted</span><?php elseif ($isLate): ?><span class="badge badge-inactive">Missing</span><?php else: ?><span class="badge badge-draft">Pending</span><?php endif; ?>
                                </td>
                                <td><?= $assignment['grade'] !== null ? e((string) $assignment['grade']) : e('Not graded') ?></td>
                                <td><?= e($assignment['feedback'] ?: 'No feedback yet') ?></td>
                                <td><?php if ($assignment['file_path']): ?><a href="<?= app_url($assignment['file_path']) ?>" target="_blank">Download</a><?php else: ?><span class="muted">No file</span><?php endif; ?></td>
                                <td>
                                    <form class="assignment-upload" method="post" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                        <input type="hidden" name="course_id" value="<?= (int) $currentCourse['id'] ?>">
                                        <input name="submission_file" type="file" accept=".pdf,.doc,.docx,.zip" required>
                                        <button class="btn btn-primary btn-sm" type="submit"><?= $assignment['submission_file'] ? 'Update' : 'Upload' ?></button>
                                        <?php if ($assignment['submission_file']): ?><a href="<?= app_url($assignment['submission_file']) ?>" target="_blank">Current file</a><?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$assignments): ?>
                            <tr><td colspan="7" class="muted text-center">No assignments assigned for this course.</td></tr>
                        <?php endif; ?>
                    </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
