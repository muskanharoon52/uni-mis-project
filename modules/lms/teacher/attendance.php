<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('teacher');

// Only allow this in development
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('This script is only for local development.');
}

$message = '';
$error = '';

// Check if teacher already has courses
$courseCheck = db()->query("SELECT COUNT(*) FROM courses WHERE teacher_id = {$user['teacher_id']}")->fetchColumn();

if ($courseCheck == 0) {
    try {
        // Insert sample courses
        $sampleCourses = [
            ['CS101', 'Introduction to Computer Science', 3],
            ['MATH201', 'Calculus II', 4],
            ['ENG101', 'English Composition', 3],
            ['PHY101', 'Physics Fundamentals', 4]
        ];
        
        foreach ($sampleCourses as $course) {
            $stmt = db()->prepare(
                "INSERT INTO courses (course_code, course_title, teacher_id, semester_name, credit_hours) 
                 VALUES (?, ?, ?, 'Spring 2026', ?)"
            );
            $stmt->execute([$course[0], $course[1], $user['teacher_id'], $course[2]]);
        }
        $message = 'Sample courses added successfully!';
    } catch (Exception $e) {
        $error = 'Error adding courses: ' . $e->getMessage();
    }
}

// Check if students are enrolled
$enrollmentCheck = db()->query("SELECT COUNT(*) FROM lms_enrollments")->fetchColumn();

if ($enrollmentCheck == 0) {
    try {
        // Get some student IDs
        $students = db()->query("SELECT user_id FROM users WHERE role = 'student' LIMIT 5")->fetchAll();
        $courses = db()->query("SELECT course_id FROM courses WHERE teacher_id = {$user['teacher_id']}")->fetchAll();
        
        if (!empty($students) && !empty($courses)) {
            $enrollStmt = db()->prepare("INSERT INTO lms_enrollments (student_user_id, course_id) VALUES (?, ?)");
            
            foreach ($students as $student) {
                foreach ($courses as $course) {
                    // Avoid duplicate enrollment
                    $check = db()->prepare(
                        "SELECT COUNT(*) FROM lms_enrollments WHERE student_user_id = ? AND course_id = ?"
                    );
                    $check->execute([$student['user_id'], $course['course_id']]);
                    if ($check->fetchColumn() == 0) {
                        $enrollStmt->execute([$student['user_id'], $course['course_id']]);
                    }
                }
            }
            $message .= ' Students enrolled in courses!';
        }
    } catch (Exception $e) {
        $error = 'Error enrolling students: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3>Setup Teacher Data</h3></div>
    <div class="card-body" style="padding: 2rem;">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <p>Current Status:</p>
        <ul>
            <li><strong>Courses assigned:</strong> <?= db()->query("SELECT COUNT(*) FROM courses WHERE teacher_id = {$user['teacher_id']}")->fetchColumn() ?></li>
            <li><strong>Students enrolled:</strong> <?= db()->query("SELECT COUNT(*) FROM lms_enrollments")->fetchColumn() ?></li>
            <li><strong>Students:</strong> <?= db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?></li>
        </ul>
        
        <a href="<?= app_url('teacher/attendance.php') ?>" class="btn btn-primary">Go to Attendance</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>