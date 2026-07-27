<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$teacherId = (int) $user['teacher_id'];
$active = 'attendance';
$pageTitle = 'Attendance Management';

// Get teacher's courses
$coursesStmt = db()->prepare(
    'SELECT c.course_id, c.course_code, c.course_title, c.semester_name,
        (SELECT COUNT(*) FROM lms_enrollments e WHERE e.course_id = c.course_id) as student_count
     FROM courses c
     WHERE c.teacher_id = ?
     ORDER BY c.course_code'
);
$coursesStmt->execute([$teacherId]);
$courses = $coursesStmt->fetchAll();

// Handle course selection
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : (($courses[0]['course_id'] ?? 0));
$classDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$message = '';
$error = '';

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $courseId = (int) $_POST['course_id'];
    $classDate = $_POST['class_date'] ?? date('Y-m-d');
    
    if ($courseId <= 0) {
        $error = 'Please select a valid course.';
    } else {
        // Verify teacher owns this course
        $ownStmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE course_id = ? AND teacher_id = ?');
        $ownStmt->execute([$courseId, $teacherId]);
        if ($ownStmt->fetchColumn() == 0) {
            $error = 'You are not authorized to mark attendance for this course.';
        } else {
            // Get enrolled students with their student_id from students table
            $studentsStmt = db()->prepare(
                'SELECT s.student_id, u.full_name, u.username
                 FROM lms_enrollments e
                 JOIN users u ON u.user_id = e.student_user_id
                 JOIN students s ON s.user_id = u.user_id
                 WHERE e.course_id = ?
                 ORDER BY u.full_name'
            );
            $studentsStmt->execute([$courseId]);
            $students = $studentsStmt->fetchAll();
            
            if (empty($students)) {
                $error = 'No students enrolled in this course.';
            } else {
                $db = db();
                $db->beginTransaction();
                try {
                    $insertStmt = $db->prepare(
                        'INSERT INTO attendance (student_id, course_id, teacher_id, class_date, status, remark, marked_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW())
                         ON DUPLICATE KEY UPDATE status = VALUES(status), remark = VALUES(remark), marked_at = NOW()'
                    );
                    
                    foreach ($students as $student) {
                        $studentId = (int) $student['student_id'];
                        $status = $_POST['status_' . $studentId] ?? 'Absent';
                        $remark = trim($_POST['remark_' . $studentId] ?? '');
                        
                        if (!in_array($status, ['Present', 'Absent', 'Leave'])) {
                            $status = 'Absent';
                        }
                        
                        $insertStmt->execute([$studentId, $courseId, $teacherId, $classDate, $status, $remark]);
                    }
                    $db->commit();
                    $message = 'Attendance marked successfully for ' . date('M j, Y', strtotime($classDate));
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error marking attendance: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get enrolled students for selected course
$students = [];
$existingAttendance = [];
if ($courseId > 0) {
    $studentsStmt = db()->prepare(
        'SELECT s.student_id, u.full_name, u.username
         FROM lms_enrollments e
         JOIN users u ON u.user_id = e.student_user_id
         JOIN students s ON s.user_id = u.user_id
         WHERE e.course_id = ?
         ORDER BY u.full_name'
    );
    $studentsStmt->execute([$courseId]);
    $students = $studentsStmt->fetchAll();
    
    // Get existing attendance for this date
    if (!empty($students)) {
        $studentIds = array_map(fn($s) => (int)$s['student_id'], $students);
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $attStmt = db()->prepare(
            "SELECT student_id, status, remark FROM attendance 
             WHERE course_id = ? AND class_date = ? AND student_id IN ($placeholders)"
        );
        $params = array_merge([$courseId, $classDate], $studentIds);
        $attStmt->execute($params);
        $attRows = $attStmt->fetchAll();
        foreach ($attRows as $row) {
            $existingAttendance[(int)$row['student_id']] = $row;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom: 20px;">
    <h4><i class="fas fa-clipboard-check"></i> Attendance Management</h4>
    <div class="page-header-actions">
        <a href="<?= app_url('teacher/dashboard.php') ?>" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<!-- Course Selector -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3>Select Course</h3>
    </div>
    <div class="card-content">
        <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label>Course</label>
                <select name="course_id" class="form-control" required onchange="this.form.submit()">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int)$course['course_id'] ?>" <?= $courseId == $course['course_id'] ? 'selected' : '' ?>>
                            <?= e($course['course_code'] . ' - ' . $course['course_title']) ?> (<?= $course['semester_name'] ?? 'N/A' ?>) - <?= (int)$course['student_count'] ?> students
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width: 160px;">
                <label>Class Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($classDate) ?>" required onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<?php if ($courseId > 0): ?>
    <?php 
    $selectedCourse = null;
    foreach ($courses as $c) {
        if ((int)$c['course_id'] === $courseId) { $selectedCourse = $c; break; }
    }
    ?>
    
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>Mark Attendance - <?= e($selectedCourse['course_code'] ?? '') ?> - <?= e($selectedCourse['course_title'] ?? '') ?></h3>
            <span class="muted">Date: <?= e(date('l, M j, Y', strtotime($classDate))) ?></span>
        </div>
        <div class="card-content">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h5>No Students Enrolled</h5>
                    <p>No students are enrolled in this course yet.</p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="course_id" value="<?= (int)$courseId ?>">
                    <input type="hidden" name="class_date" value="<?= e($classDate) ?>">
                    <input type="hidden" name="mark_attendance" value="1">
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Student Name</th>
                                    <th>Username</th>
                                    <th style="width: 140px;">Status</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($students as $student): 
                                    $sid = (int)$student['student_id'];
                                    $currentStatus = $existingAttendance[$sid]['status'] ?? 'Absent';
                                    $currentRemark = $existingAttendance[$sid]['remark'] ?? '';
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= e($student['full_name']) ?></strong></td>
                                    <td class="muted"><?= e($student['username']) ?></td>
                                    <td>
                                        <select name="status_<?= $sid ?>" class="form-control form-control-sm">
                                            <option value="Present" <?= $currentStatus === 'Present' ? 'selected' : '' ?>>Present</option>
                                            <option value="Absent" <?= $currentStatus === 'Absent' ? 'selected' : '' ?>>Absent</option>
                                            <option value="Leave" <?= $currentStatus === 'Leave' ? 'selected' : '' ?>>Leave</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="remark_<?= $sid ?>" class="form-control form-control-sm" value="<?= e($currentRemark) ?>" placeholder="Optional">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 16px; display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" class="btn btn-outline" onclick="markAll('Present')">
                            <i class="fas fa-check"></i> Mark All Present
                        </button>
                        <button type="button" class="btn btn-outline" onclick="markAll('Absent')">
                            <i class="fas fa-times"></i> Mark All Absent
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-content" style="text-align: center; padding: 3rem;">
            <i class="fas fa-clipboard-check" style="font-size: 3rem; color: var(--muted); margin-bottom: 1rem;"></i>
            <h3>No Courses Assigned</h3>
            <p class="muted">You don't have any courses assigned yet.</p>
        </div>
    </div>
<?php endif; ?>

<script>
function markAll(status) {
    document.querySelectorAll('select[name^="status_"]').forEach(select => {
        select.value = status;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>