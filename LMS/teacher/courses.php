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
            $stmt = db()->prepare('INSERT INTO courses (code, title, description, credit_hours, teacher_id, semester) VALUES (?, ?, ?, ?, ?, ?)');
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
                $stmt = db()->prepare('INSERT INTO assignments (course_id, title, description, file_path, due_date) VALUES (?, ?, ?, ?, ?)');
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
        COUNT(DISTINCT e.student_id) AS student_count,
        COUNT(DISTINCT a.id) AS assignment_count,
        COUNT(DISTINCT l.id) AS lecture_count
     FROM courses c
     LEFT JOIN enrollments e ON e.course_id = c.id
     LEFT JOIN assignments a ON a.course_id = c.id
     LEFT JOIN lectures l ON l.course_id = c.id
     WHERE c.teacher_id = ?
     GROUP BY c.id
     ORDER BY c.code'
);
$coursesStmt->execute([$user['id']]);
$courses = $coursesStmt->fetchAll();
$selectedCourse = $courses[0] ?? null;
if (isset($_GET['course_id'])) {
    foreach ($courses as $course) {
        if ((int) $course['id'] === (int) $_GET['course_id']) {
            $selectedCourse = $course;
            break;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>My Courses</h1>
        <p class="muted">Manage your course content, assignments, and lectures.</p>
    </div>
</div>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="teacher-course-meta dashboard-block">
    <form class="card" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_course">
        <h2>Add Course</h2>
        <label for="code">Code</label>
        <input id="code" name="code" required>
        <label for="title">Title</label>
        <input id="title" name="title" required>
        <label for="description">Description</label>
        <textarea id="description" name="description"></textarea>
        <label for="credit_hours">Credit Hours</label>
        <input id="credit_hours" name="credit_hours" type="number" min="1" value="3">
        <label for="semester">Semester</label>
        <input id="semester" name="semester" value="Spring 2026">
        <button class="btn" type="submit">Add Course</button>
    </form>

    <div class="table-card">
        <h2>Your Courses</h2>
        <table>
            <tr><th>Code</th><th>Title</th><th>Students</th><th>Assignments</th><th>Lectures</th></tr>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><a href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['id']) ?>"><?= e($course['code']) ?></a></td>
                    <td><?= e($course['title']) ?></td>
                    <td><?= (int) $course['student_count'] ?></td>
                    <td><?= (int) $course['assignment_count'] ?></td>
                    <td><?= (int) $course['lecture_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<section class="teacher-course-shell">
    <aside class="course-list-panel">
        <div class="course-list-head">My Courses</div>
        <?php foreach ($courses as $course): ?>
            <a class="course-list-item <?= $selectedCourse && (int) $selectedCourse['id'] === (int) $course['id'] ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $course['id']) ?>">
                <strong><?= e($course['code']) ?></strong>
                <span><?= e($course['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="course-detail-panel">
        <?php if (!$selectedCourse): ?>
            <div class="card"><h2>No courses yet</h2><p class="muted">Add a course to begin.</p></div>
        <?php else: ?>
            <div class="course-summary card">
                <h1><?= e($selectedCourse['code'] . ' - ' . $selectedCourse['title']) ?></h1>
                <p class="muted"><?= e($selectedCourse['semester']) ?> | <?= (int) $selectedCourse['credit_hours'] ?> credit hours</p>
                <p><?= e($selectedCourse['description'] ?: 'No course description available.') ?></p>
            </div>
            <div class="course-tabs teacher-course-actions">
                <a class="course-tab <?= $view === 'overview' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['id'] . '&view=overview') ?>">Overview</a>
                <a class="course-tab <?= $view === 'assignments' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['id'] . '&view=assignments') ?>">Assignments</a>
                <a class="course-tab <?= $view === 'lectures' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php?course_id=' . (int) $selectedCourse['id'] . '&view=lectures') ?>">Lectures</a>
            </div>

            <?php if ($view === 'overview'): ?>
                <div class="grid">
                    <div class="card"><h3>Students</h3><p class="stat"><?= (int) $selectedCourse['student_count'] ?></p></div>
                    <div class="card"><h3>Assignments</h3><p class="stat"><?= (int) $selectedCourse['assignment_count'] ?></p></div>
                    <div class="card"><h3>Lectures</h3><p class="stat"><?= (int) $selectedCourse['lecture_count'] ?></p></div>
                </div>
            <?php elseif ($view === 'assignments'): ?>
                <div class="grid">
                    <form class="card" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_assignment">
                        <input type="hidden" name="course_id" value="<?= (int) $selectedCourse['id'] ?>">
                        <h2>Upload Assignment</h2>
                        <label for="assignment_title">Title</label>
                        <input id="assignment_title" name="title" required>
                        <label for="assignment_description">Description</label>
                        <textarea id="assignment_description" name="description"></textarea>
                        <label for="assignment_file">Assignment File</label>
                        <input id="assignment_file" name="assignment_file" type="file" accept=".pdf,.doc,.docx,.zip">
                        <label for="due_date">Due Date</label>
                        <input id="due_date" name="due_date" type="date" required>
                        <button class="btn" type="submit">Post Assignment</button>
                    </form>
                    <div class="table-card">
                        <h2>Assignments</h2>
                        <?php $assignmentStmt = db()->prepare('SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date DESC'); $assignmentStmt->execute([(int) $selectedCourse['id']]); ?>
                        <table>
                            <tr><th>Title</th><th>Due</th><th>File</th></tr>
                            <?php foreach ($assignmentStmt->fetchAll() as $assignment): ?>
                                <tr><td><?= e($assignment['title']) ?></td><td><?= e($assignment['due_date']) ?></td><td><?php if ($assignment['file_path']): ?><a href="<?= app_url($assignment['file_path']) ?>" target="_blank">Download</a><?php endif; ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            <?php elseif ($view === 'lectures'): ?>
                <div class="grid">
                    <form class="card" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_lecture">
                        <input type="hidden" name="course_id" value="<?= (int) $selectedCourse['id'] ?>">
                        <h2>Upload Lecture</h2>
                        <label for="lecture_title">Title</label>
                        <input id="lecture_title" name="title" required>
                        <label for="lecture_date">Date</label>
                        <input id="lecture_date" name="lecture_date" type="date">
                        <label for="lecture_file">Lecture File</label>
                        <input id="lecture_file" name="lecture_file" type="file" accept=".ppt,.pptx,.pdf,.doc,.docx" required>
                        <button class="btn" type="submit">Upload Lecture</button>
                    </form>
                    <div class="table-card">
                        <h2>Lectures</h2>
                        <?php $lectureStmt = db()->prepare('SELECT * FROM lectures WHERE course_id = ? ORDER BY id DESC'); $lectureStmt->execute([(int) $selectedCourse['id']]); ?>
                        <table>
                            <tr><th>Title</th><th>Date</th><th>File</th></tr>
                            <?php foreach ($lectureStmt->fetchAll() as $lecture): ?>
                                <tr><td><?= e($lecture['title']) ?></td><td><?= e($lecture['lecture_date']) ?></td><td><a href="<?= app_url($lecture['file_path']) ?>" target="_blank">Download</a></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
