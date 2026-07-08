<?php
// 9. Attendance/mark.php
// Mark attendance for students

require_once '../../15. Includes/db.php';
require_once '../../15. Includes/auth.php';
require_once '../../15. Includes/header.php';

requireAnyRole(['SuperAdmin', 'Admin', 'Teacher']);

$page_title = 'Mark Attendance';
$conn = getConnection();

$error = '';
$success = '';
$students = [];
$course_id = 0;
$class_date = date('Y-m-d');

// Get teacher's courses (if teacher role)
$user = getCurrentUser();
$teacher_id = 0;
if (isTeacher()) {
    // Get teacher_id from user
    $teacher_data = getSingleRecord("SELECT teacher_id FROM teachers WHERE user_id = ?", [$user['user_id']], 'i');
    if ($teacher_data) {
        $teacher_id = $teacher_data['teacher_id'];
    }
}

// Get courses for dropdown
if (isTeacher() && $teacher_id > 0) {
    $courses = getAllRecords("
        SELECT DISTINCT c.course_id, c.course_code, c.course_title 
        FROM courses c
        JOIN teacher_courses tc ON c.course_id = tc.course_id
        WHERE tc.teacher_id = ?
    ", [$teacher_id], 'i');
} else {
    $courses = getAllRecords("SELECT course_id, course_code, course_title FROM courses WHERE status = 'Active'");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $class_date = sanitize($_POST['class_date']);
    $attendance = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    
    if ($course_id == 0) {
        $error = "Please select a course!";
    } elseif (empty($attendance)) {
        $error = "No attendance data submitted!";
    } else {
        // Get teacher_id if not set
        if ($teacher_id == 0) {
            $teacher_data = getSingleRecord("SELECT teacher_id FROM users u JOIN teachers t ON u.user_id = t.user_id WHERE u.user_id = ?", [$user['user_id']], 'i');
            if ($teacher_data) {
                $teacher_id = $teacher_data['teacher_id'];
            }
        }
        
        // Delete existing attendance for this date and course
        deleteRecord("DELETE FROM attendance WHERE course_id = ? AND class_date = ?", [$course_id, $class_date], 'is');
        
        // Insert new attendance
        $inserted = 0;
        foreach ($attendance as $student_id => $status) {
            $sql = "INSERT INTO attendance (student_id, course_id, teacher_id, class_date, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $result = insertRecord($sql, [$student_id, $course_id, $teacher_id, $class_date, $status], 'iiiss');
            if ($result > 0) {
                $inserted++;
            }
        }
        
        if ($inserted > 0) {
            $success = "Attendance marked for $inserted students!";
        } else {
            $error = "Error marking attendance!";
        }
    }
}

// Get students for the selected course
if (isset($_POST['course_id']) && $_POST['course_id'] > 0) {
    $course_id = (int)$_POST['course_id'];
    if (isset($_POST['class_date'])) {
        $class_date = sanitize($_POST['class_date']);
    }
    
    $students = getAllRecords("
        SELECT DISTINCT s.student_id, s.full_name, s.roll_no 
        FROM students s
        JOIN student_fee sf ON s.student_id = sf.student_id
        WHERE s.status = 'Active' AND s.program_id IN (
            SELECT department_id FROM courses WHERE course_id = ?
        )
        ORDER BY s.full_name
    ", [$course_id], 'i');
    
    // Get existing attendance for today
    if (!empty($students)) {
        $existing_attendance = getAllRecords("
            SELECT student_id, status FROM attendance 
            WHERE course_id = ? AND class_date = ?
        ", [$course_id, $class_date], 'is');
        
        $attendance_map = [];
        foreach ($existing_attendance as $att) {
            $attendance_map[$att['student_id']] = $att['status'];
        }
        
        // Merge status into students array
        foreach ($students as &$student) {
            $student['attendance_status'] = isset($attendance_map[$student['student_id']]) 
                ? $attendance_map[$student['student_id']] 
                : 'Present';
        }
    }
}
?>
<?php include '../../15. Includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clipboard-check"></i> Mark Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- Course Selection Form -->
                    <form method="POST" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>"
                                        <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="class_date" class="form-control" 
                                   value="<?php echo $class_date; ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Load Students
                            </button>
                        </div>
                        <?php if (!empty($students)): ?>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-success" onclick="markAll('Present')">
                                <i class="fas fa-check"></i> All Present
                            </button>
                            <button type="button" class="btn btn-danger" onclick="markAll('Absent')">
                                <i class="fas fa-times"></i> All Absent
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                    
                    <?php if (!empty($students)): ?>
                        <!-- Attendance Form -->
                        <form method="POST" id="attendanceForm">
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <input type="hidden" name="class_date" value="<?php echo $class_date; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; foreach($students as $student): ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><?php echo htmlspecialchars($student['roll_no'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" 
                                                           value="Present" 
                                                           <?php echo $student['attendance_status'] == 'Present' ? 'checked' : ''; ?>
                                                           class="btn-check" id="present_<?php echo $student['student_id']; ?>">
                                                    <label class="btn btn-success btn-sm" for="present_<?php echo $student['student_id']; ?>">
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    
                                                    <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" 
                                                           value="Absent" 
                                                           <?php echo $student['attendance_status'] == 'Absent' ? 'checked' : ''; ?>
                                                           class="btn-check" id="absent_<?php echo $student['student_id']; ?>">
                                                    <label class="btn btn-danger btn-sm" for="absent_<?php echo $student['student_id']; ?>">
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                    
                                                    <input type="radio" name="attendance[<?php echo $student['student_id']; ?>]" 
                                                           value="Leave" 
                                                           <?php echo $student['attendance_status'] == 'Leave' ? 'checked' : ''; ?>
                                                           class="btn-check" id="leave_<?php echo $student['student_id']; ?>">
                                                    <label class="btn btn-warning btn-sm" for="leave_<?php echo $student['student_id']; ?>">
                                                        <i class="fas fa-pause"></i> Leave
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </form>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $course_id > 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No students found for this course.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAll(status) {
    <?php foreach($students as $student): ?>
        document.getElementById(status.toLowerCase() + '_<?php echo $student['student_id']; ?>').checked = true;
    <?php endforeach; ?>
}
</script>

<?php include '../../15. Includes/footer.php'; ?>