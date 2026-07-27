<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('teacher');

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('This script is only for local development.');
}

$message = '';
$error = '';

$stmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = ?');
$stmt->execute([$user['teacher_id']]);
$courseCheck = (int) $stmt->fetchColumn();

if ($courseCheck === 0) {
    try {
        $sampleCourses = [
            ['CS101', 'Introduction to Computer Science', 3],
            ['MATH201', 'Calculus II', 4],
            ['ENG101', 'English Composition', 3],
            ['PHY101', 'Physics Fundamentals', 4]
        ];
        $ins = db()->prepare('INSERT INTO courses (course_code, course_title, teacher_id, semester_name, credit_hours) VALUES (?, ?, ?, ?, ?)');
        foreach ($sampleCourses as $c) {
            $ins->execute([$c[0], $c[1], $user['teacher_id'], 'Spring 2026', $c[2]]);
        }
        $message = 'Sample courses added successfully!';
    } catch (Exception $e) {
        error_log('Attendance setup error: ' . $e->getMessage());
        $error = 'An error occurred while adding courses.';
    }
}

$enrollCheck = db()->query('SELECT COUNT(*) FROM lms_enrollments')->fetchColumn();

if ($enrollCheck == 0) {
    try {
        $students = db()->query("SELECT u.user_id FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'Student' LIMIT 5")->fetchAll();
        $coursesStmt = db()->prepare('SELECT course_id FROM courses WHERE teacher_id = ?');
        $coursesStmt->execute([$user['teacher_id']]);
        $courses = $coursesStmt->fetchAll();

        if (!empty($students) && !empty($courses)) {
            $enrollStmt = db()->prepare('INSERT INTO lms_enrollments (student_user_id, course_id) VALUES (?, ?)');
            foreach ($students as $student) {
                foreach ($courses as $course) {
                    $check = db()->prepare('SELECT COUNT(*) FROM lms_enrollments WHERE student_user_id = ? AND course_id = ?');
                    $check->execute([$student['user_id'], $course['course_id']]);
                    if ($check->fetchColumn() == 0) {
                        $enrollStmt->execute([$student['user_id'], $course['course_id']]);
                    }
                }
            }
            $message .= ' Students enrolled in courses!';
        }
    } catch (Exception $e) {
        error_log('Attendance enrollment error: ' . $e->getMessage());
        $error = 'An error occurred while enrolling students.';
    }
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = ?');
$countStmt->execute([$user['teacher_id']]);
$courseCount = (int) $countStmt->fetchColumn();
$studentCount = (int) db()->query('SELECT COUNT(*) FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = \'Student\'')->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>Setup Teacher Data</h3></div>
    <div class="card-body" style="padding: 2rem;">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <p>Current Status:</p>
        <ul>
            <li><strong>Courses assigned:</strong> <?= $courseCount ?></li>
            <li><strong>Students enrolled:</strong> <?= (int) db()->query('SELECT COUNT(*) FROM lms_enrollments')->fetchColumn() ?></li>
            <li><strong>Students:</strong> <?= $studentCount ?></li>
        </ul>

        <a href="<?= app_url('teacher/attendance.php') ?>" class="btn btn-primary">Go to Attendance</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
