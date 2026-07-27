<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('teacher');

// Only allow localhost access
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('This script is only for local development.');
}

$message = '';
$error = '';

try {
    // Check if teacher has courses
    $stmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = ?');
    $stmt->execute([$user['teacher_id']]);
    $courseCheck = (int) $stmt->fetchColumn();

    if ($courseCheck === 0) {
        try {
            // Sample courses data
            $sampleCourses = [
                ['CS101', 'Introduction to Computer Science', 3],
                ['MATH201', 'Calculus II', 4],
                ['ENG101', 'English Composition', 3],
                ['PHY101', 'Physics Fundamentals', 4],
                ['CS102', 'Object Oriented Programming', 3],
                ['MATH202', 'Linear Algebra', 3],
                ['ENG102', 'Technical Writing', 3],
                ['PHY102', 'Electricity and Magnetism', 4]
            ];
            
            // Insert courses
            $ins = db()->prepare('INSERT INTO courses (course_code, course_title, teacher_id, semester_id, credit_hours, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            
            // Get default semester
            $semStmt = db()->query("SELECT semester_id FROM semesters WHERE semester_name LIKE '%Spring%' LIMIT 1");
            $semester = $semStmt->fetch();
            $semester_id = $semester ? $semester['semester_id'] : 1;
            
            $addedCount = 0;
            foreach ($sampleCourses as $c) {
                try {
                    // Check if course already exists
                    $check = db()->prepare('SELECT COUNT(*) FROM courses WHERE course_code = ?');
                    $check->execute([$c[0]]);
                    if ($check->fetchColumn() == 0) {
                        $ins->execute([$c[0], $c[1], $user['teacher_id'], $semester_id, $c[2], 'Active']);
                        $addedCount++;
                    }
                } catch (Exception $e) {
                    error_log('Error adding course ' . $c[0] . ': ' . $e->getMessage());
                }
            }
            // REMOVED: $message = 'Sample courses added successfully!';
            // Message removed as requested - courses will show in status
        } catch (Exception $e) {
            error_log('Attendance setup error: ' . $e->getMessage());
            $error = 'An error occurred while adding courses: ' . $e->getMessage();
        }
    }

    // Check and enroll students
    $enrollCheck = db()->query('SELECT COUNT(*) FROM lms_enrollments')->fetchColumn();

    if ($enrollCheck == 0) {
        try {
            // Get students
            $students = db()->query("SELECT u.user_id FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'Student' LIMIT 10")->fetchAll();
            
            // Get teacher's courses
            $coursesStmt = db()->prepare('SELECT course_id FROM courses WHERE teacher_id = ?');
            $coursesStmt->execute([$user['teacher_id']]);
            $courses = $coursesStmt->fetchAll();

            if (!empty($students) && !empty($courses)) {
                $enrollStmt = db()->prepare('INSERT IGNORE INTO lms_enrollments (student_user_id, course_id, enrolled_at) VALUES (?, ?, NOW())');
                $enrolledCount = 0;
                
                foreach ($students as $student) {
                    foreach ($courses as $course) {
                        try {
                            $enrollStmt->execute([$student['user_id'], $course['course_id']]);
                            $enrolledCount++;
                        } catch (Exception $e) {
                            // Duplicate entry - ignore
                        }
                    }
                }
                if ($enrolledCount > 0) {
                    $message .= ' ' . $enrolledCount . ' students enrolled in courses!';
                }
            } else {
                if (empty($students)) {
                    $error = 'No students found in the system.';
                }
                if (empty($courses)) {
                    $error = 'No courses found for this teacher.';
                }
            }
        } catch (Exception $e) {
            error_log('Attendance enrollment error: ' . $e->getMessage());
            $error = 'An error occurred while enrolling students: ' . $e->getMessage();
        }
    }

    // Get counts for display
    $countStmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE teacher_id = ?');
    $countStmt->execute([$user['teacher_id']]);
    $courseCount = (int) $countStmt->fetchColumn();
    
    $studentCount = (int) db()->query('SELECT COUNT(*) FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = \'Student\'')->fetchColumn();
    
    $enrollmentCount = (int) db()->query('SELECT COUNT(*) FROM lms_enrollments')->fetchColumn();

} catch (Exception $e) {
    error_log('Setup error: ' . $e->getMessage());
    $error = 'An unexpected error occurred: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3><i class="fas fa-chalkboard-teacher"></i> Teacher Data Setup</h3>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h5>Current Status:</h5>
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h2 class="text-primary"><?= $courseCount ?></h2>
                            <p class="text-muted">Courses Assigned</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h2 class="text-success"><?= $enrollmentCount ?></h2>
                            <p class="text-muted">Total Enrollments</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h2 class="text-info"><?= $studentCount ?></h2>
                            <p class="text-muted">Total Students</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($courseCount > 0): ?>
                <div class="mt-3">
                    <h6>Your Courses:</h6>
                    <ul>
                        <?php 
                        $coursesStmt = db()->prepare('SELECT course_code, course_title FROM courses WHERE teacher_id = ?');
                        $coursesStmt->execute([$user['teacher_id']]);
                        $courses = $coursesStmt->fetchAll();
                        foreach ($courses as $course): 
                        ?>
                            <li><?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_title']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="<?= app_url('teacher/attendance.php') ?>" class="btn btn-primary">
                    <i class="fas fa-clipboard-check"></i> Go to Attendance
                </a>
                <a href="<?= app_url('teacher/dashboard.php') ?>" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <?php if ($courseCount == 0): ?>
                    <button onclick="location.reload()" class="btn btn-warning">
                        <i class="fas fa-sync"></i> Retry Setup
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>