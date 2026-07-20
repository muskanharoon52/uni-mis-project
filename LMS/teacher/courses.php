<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'courses';
$pageTitle = 'Courses';
$message = '';
$error = '';
$view = (string) ($_GET['view'] ?? 'overview');
$view = in_array($view, ['overview', 'assignments', 'lectures'], true) ? $view : 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add_course') {
            $code = trim((string) ($_POST['code'] ?? ''));
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($code === '' || $title === '') {
                throw new RuntimeException('Course code and title are required.');
            }
            $stmt = db()->prepare('INSERT INTO courses (course_code, course_title, description, credit_hours, teacher_id, semester_name) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$code, $title, trim((string) ($_POST['description'] ?? '')), (int) ($_POST['credit_hours'] ?? 3), $user['id'], trim((string) ($_POST['semester'] ?? 'Spring 2026'))]);
            $message = 'Course added.';
        } else {
            $courseId = (int) ($_POST['course_id'] ?? 0);
            if (!teacher_owns_course((int) $user['id'], $courseId)) {
                throw new RuntimeException('You cannot edit this course.');
            }

            if ($action === 'save_assignment') {
                $filePath = save_uploaded_file('assignment_file', 'assignments', ['pdf', 'doc', 'docx', 'zip']);
                $title = trim((string) ($_POST['title'] ?? ''));
                $stmt = db()->prepare('INSERT INTO lms_assignments (course_id, title, description, file_path, due_date) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$courseId, $title, trim((string) ($_POST['description'] ?? '')), $filePath, (string) ($_POST['due_date'] ?? date('Y-m-d'))]);
                notify_course_students($courseId, 'New assignment posted', 'An assignment titled "' . $title . '" has been uploaded.', app_url('student/courses.php?course_id=' . $courseId . '&view=assignment'));
                $message = 'Assignment posted.';
            } elseif ($action === 'save_lecture') {
                $filePath = save_uploaded_file('lecture_file', 'lectures', ['ppt', 'pptx', 'pdf', 'doc', 'docx']);
                $stmt = db()->prepare('INSERT INTO lectures (course_id, title, file_path, lecture_date) VALUES (?, ?, ?, ?)');
                $stmt->execute([$courseId, trim((string) ($_POST['title'] ?? '')), $filePath, $_POST['lecture_date'] !== '' ? (string) $_POST['lecture_date'] : null]);
                $message = 'Lecture uploaded.';
            }
        }
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$coursesStmt = db()->prepare(
    'SELECT c.*,
        COUNT(DISTINCT e.student_user_id) AS student_count,
        COUNT(DISTINCT a.assignment_id) AS assignment_count,
        COUNT(DISTINCT l.id) AS lecture_count
     FROM courses c
     LEFT JOIN lms_enrollments e ON e.course_id = c.course_id
     LEFT JOIN lms_assignments a ON a.course_id = c.course_id
     LEFT JOIN lectures l ON l.course_id = c.course_id
     WHERE c.teacher_id = ?
     GROUP BY c.course_id
     ORDER BY c.course_code'
);
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();
$selectedCourse = $courses[0] ?? null;
if (isset($_GET['course_id'])) {
    foreach ($courses as $course) {
        if ((int) $course['course_id'] === (int) $_GET['course_id']) {
            $selectedCourse = $course;
            break;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Add Course</h3></div>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_course">
        <div class="inline-form-row">
            <div><label for="code">Code</label><input id="code" name="code" required></div>
            <div><label for="title">Title</label><input id="title" name="title" required></div>
            <div><label for="description">Description</label><textarea id="description" name="description"></textarea></div>
            <div><label for="credit_hours">Credits</label><input id="credit_hours" name="credit_hours" type="number" min="1" value="3"></div>
            <div><label for="semester">Semester</label><input id="semester" name="semester" value="Spring 2026"></div>
            <div style="align-self:end;"><button class="btn btn-primary" type="submit">Add Course</button></div>
        </div>
    </form>
</div>

<div class="card mt-4">
    <div class="card-header"><h3>Your Courses</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Code</th><th>Title</th><th>Students</th><th>Assignments</th><th>Lectures</th></tr>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><a href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['course_id']) ?>"><?= e($course['course_code']) ?></a></td>
                    <td><?= e($course['course_title']) ?></td>
                    <td><?= (int) $course['student_count'] ?></td>
                    <td><?= (int) $course['assignment_count'] ?></td>
                    <td><?= (int) $course['lecture_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<section class="course-layout mt-4">
    <aside class="course-list-panel">
        <div class="course-list-head">My Courses</div>
        <?php foreach ($courses as $course): ?>
            <a class="course-list-item <?= $selectedCourse && (int) $selectedCourse['course_id'] === (int) $course['course_id'] ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['course_id']) ?>">
                <strong><?= e($course['course_code']) ?></strong>
                <span><?= e($course['course_title']) ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="course-detail-panel">
        <?php if (!$selectedCourse): ?>
            <div class="card"><h2>No courses yet</h2><p class="muted">Add a course to begin.</p></div>
        <?php else: ?>
            <div class="course-summary card">
                <h1><?= e($selectedCourse['course_code'] . ' - ' . $selectedCourse['course_title']) ?></h1>
                <p class="muted"><?= e($selectedCourse['semester_name']) ?> | <?= (int) $selectedCourse['credit_hours'] ?> credit hours</p>
                <p><?= e($selectedCourse['description'] ?: 'No course description available.') ?></p>
            </div>
            <div class="course-tabs teacher-course-actions">
                <a class="course-tab <?= $view === 'overview' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['course_id'] . '&view=overview') ?>">Overview</a>
                <a class="course-tab <?= $view === 'assignments' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['course_id'] . '&view=assignments') ?>">Assignments</a>
                <a class="course-tab <?= $view === 'lectures' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['course_id'] . '&view=lectures') ?>">Lectures</a>
            </div>

            <?php if ($view === 'overview'): ?>
                <div class="stat-row">
                    <div class="stat-card-v2"><div class="stat-label">Students</div><div class="stat-number"><?= (int) $selectedCourse['student_count'] ?></div></div>
                    <div class="stat-card-v2"><div class="stat-label">Assignments</div><div class="stat-number"><?= (int) $selectedCourse['assignment_count'] ?></div></div>
                    <div class="stat-card-v2"><div class="stat-label">Lectures</div><div class="stat-number"><?= (int) $selectedCourse['lecture_count'] ?></div></div>
                </div>
            <?php elseif ($view === 'assignments'): ?>
                <div class="grid-2">
                    <form class="card" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_assignment">
                        <input type="hidden" name="course_id" value="<?= (int) $selectedCourse['course_id'] ?>">
                        <h3>Upload Assignment</h3>
                        <label for="assignment_title">Title</label>
                        <input id="assignment_title" name="title" required>
                        <label for="assignment_description">Description</label>
                        <textarea id="assignment_description" name="description"></textarea>
                        <label for="assignment_file">Assignment File</label>
                        <input id="assignment_file" name="assignment_file" type="file" accept=".pdf,.doc,.docx,.zip">
                        <label for="due_date">Due Date</label>
                        <input id="due_date" name="due_date" type="date" required>
                        <button class="btn btn-primary" type="submit">Post Assignment</button>
                    </form>
                    <div class="card">
                        <div class="card-header"><h3>Assignments</h3></div>
                        <div class="table-responsive">
                        <?php $assignmentStmt = db()->prepare('SELECT * FROM lms_assignments WHERE course_id = ? ORDER BY due_date DESC'); $assignmentStmt->execute([(int) $selectedCourse['course_id']]); ?>
                        <table>
                            <tr><th>Title</th><th>Due</th><th>File</th></tr>
                            <?php foreach ($assignmentStmt->fetchAll() as $assignment): ?>
                                <tr><td><?= e($assignment['title']) ?></td><td><?= e($assignment['due_date']) ?></td><td><?php if ($assignment['file_path']): ?><a href="<?= app_url($assignment['file_path']) ?>" target="_blank">Download</a><?php endif; ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($view === 'lectures'): ?>
                <div class="grid-2">
                    <form class="card" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_lecture">
                        <input type="hidden" name="course_id" value="<?= (int) $selectedCourse['course_id'] ?>">
                        <h3>Upload Lecture</h3>
                        <label for="lecture_title">Title</label>
                        <input id="lecture_title" name="title" required>
                        <label for="lecture_date">Date</label>
                        <input id="lecture_date" name="lecture_date" type="date">
                        <label for="lecture_file">Lecture File</label>
                        <input id="lecture_file" name="lecture_file" type="file" accept=".ppt,.pptx,.pdf,.doc,.docx" required>
                        <button class="btn btn-primary" type="submit">Upload Lecture</button>
                    </form>
                    <div class="card">
                        <div class="card-header"><h3>Lectures</h3></div>
                        <div class="table-responsive">
                        <?php $lectureStmt = db()->prepare('SELECT * FROM lectures WHERE course_id = ? ORDER BY id DESC'); $lectureStmt->execute([(int) $selectedCourse['course_id']]); ?>
                        <table>
                            <tr><th>Title</th><th>Date</th><th>File</th></tr>
                            <?php foreach ($lectureStmt->fetchAll() as $lecture): ?>
                                <tr><td><?= e($lecture['title']) ?></td><td><?= e($lecture['lecture_date']) ?></td><td><a href="<?= app_url($lecture['file_path']) ?>" target="_blank">Download</a></td></tr>
                            <?php endforeach; ?>
                        </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
