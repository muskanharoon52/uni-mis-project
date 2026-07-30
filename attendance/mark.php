<?php
// attendance/mark.php - Mark Attendance

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

// Check if logged in
if (!function_exists('isLoggedIn')) {
    die("isLoggedIn() function not found in auth.php");
}

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

// Check if user has permission
if (!in_array($role, ['sso', 'admin', 'teacher'])) {
    header('Location: ../dashboard.php');
    exit;
}

$conn = getConnection();
$error = '';
$students = [];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch courses
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");

// If course selected, fetch students enrolled in that course
if ($course_id > 0) {
    // Get the course's teacher_id
    $course_teacher_query = "SELECT teacher_id FROM courses WHERE course_id = ?";
    $course_teacher_stmt = $conn->prepare($course_teacher_query);
    $course_teacher_stmt->bind_param("i", $course_id);
    $course_teacher_stmt->execute();
    $course_teacher_result = $course_teacher_stmt->get_result();
    $course_data = $course_teacher_result->fetch_assoc();
    $teacher_id = $course_data['teacher_id'] ?? 0;
    $course_teacher_stmt->close();
    
    // Fetch students enrolled in this course
    $students_query = "SELECT DISTINCT s.student_id, s.roll_no, s.full_name 
                       FROM students s
                       LEFT JOIN student_courses sc ON s.student_id = sc.student_id
                       WHERE sc.course_id = ? AND s.status = 'Active'
                       ORDER BY s.full_name";
    $stmt = $conn->prepare($students_query);
    if ($stmt) {
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $students_result = $stmt->get_result();
        $students = $students_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $attendance_date = $_POST['date'];
    $statuses = $_POST['status'] ?? [];
    $remarks = $_POST['remark'] ?? [];

    if ($course_id <= 0) {
        $error = "Please select a course";
    } elseif (empty($statuses)) {
        $error = "Please mark attendance for at least one student";
    }

    // Get teacher_id for this course
    $teacher_id = 0;
    if ($course_id > 0) {
        $tq = $conn->prepare("SELECT teacher_id FROM courses WHERE course_id = ?");
        $tq->bind_param("i", $course_id);
        $tq->execute();
        $tr = $tq->get_result()->fetch_assoc();
        $teacher_id = (int)($tr['teacher_id'] ?? 0);
        $tq->close();
    }

    if (empty($error)) {
        $success_count = 0;
        $error_count = 0;

        foreach ($statuses as $student_id => $status) {
            // Check if attendance already exists
            $check_query = "SELECT attendance_id FROM attendance 
                            WHERE student_id = ? AND course_id = ? AND class_date = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("iis", $student_id, $course_id, $attendance_date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                $remark = $remarks[$student_id] ?? '';

                if ($check_result->num_rows > 0) {
                    // Update existing record
                    $row = $check_result->fetch_assoc();
                    $update_query = "UPDATE attendance SET status = ?, remark = ?, teacher_id = ? 
                                     WHERE attendance_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    if ($update_stmt) {
                        $update_stmt->bind_param("ssii", $status, $remark, $teacher_id, $row['attendance_id']);
                        if ($update_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        $update_stmt->close();
                    }
                } else {
                    // Insert new record with required teacher_id and class_date
                    $insert_query = "INSERT INTO attendance 
                                     (student_id, course_id, teacher_id, class_date, date, status, remark) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("iiissss", $student_id, $course_id, $teacher_id, 
                                                $attendance_date, $attendance_date, $status, $remark);
                        if ($insert_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        $insert_stmt->close();
                    }
                }
                $check_stmt->close();
            }
        }

        if ($error_count == 0 && $success_count > 0) {
            header("Location: index.php?success=Attendance marked successfully for $success_count students!");
            exit;
        } elseif ($success_count > 0) {
            $error = "Attendance marked for $success_count students, but failed for $error_count students.";
        } else {
            $error = "Failed to mark attendance. Please try again.";
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Mark Attendance';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-check-double"></i> Mark Attendance</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="GET" action="" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php while($row = $courses->fetch_assoc()): ?>
                            <option value="<?= $row['course_id'] ?>" 
                                <?= $course_id == $row['course_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?= $date ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-users"></i> Load Students
                    </button>
                </div>
            </form>

            <?php if (!empty($students)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                    <input type="hidden" name="date" value="<?= $date ?>">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>Students (<?= count($students) ?>)</h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="setAllStatus('Present')">
                                <i class="fas fa-check"></i> All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAllStatus('Absent')">
                                <i class="fas fa-times"></i> All Absent
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAllStatus('Leave')">
                                <i class="fas fa-clock"></i> All Leave
                            </button>
                        </div>
                    </div>

                    <div class="student-list">
                        <?php foreach($students as $student): ?>
                            <div class="student-row d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($student['full_name'] ?? $student['student_id']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($student['student_id']) ?></small>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="Present" id="present_<?= $student['student_id'] ?>" checked>
                                    <label for="present_<?= $student['student_id'] ?>" class="status-present">
                                        <i class="fas fa-check-circle"></i> Present
                                    </label>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="Absent" id="absent_<?= $student['student_id'] ?>">
                                    <label for="absent_<?= $student['student_id'] ?>" class="status-absent">
                                        <i class="fas fa-times-circle"></i> Absent
                                    </label>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="Leave" id="leave_<?= $student['student_id'] ?>">
                                    <label for="leave_<?= $student['student_id'] ?>" class="status-late">
                                        <i class="fas fa-clock"></i> Leave
                                    </label>
                                </div>
                                <div class="ms-3" style="min-width: 150px;">
                                    <input type="text" name="remark[<?= $student['student_id'] ?>]" 
                                           class="form-control form-control-sm" placeholder="Remark">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-mark">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php elseif ($course_id > 0): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p>No students found for this course.</p>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                    <p>Select a course and click "Load Students" to start marking attendance.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

<script>
    function setAllStatus(status) {
        document.querySelectorAll('input[type="radio"][name^="status["]').forEach(function(radio) {
            if (radio.value === status) {
                radio.checked = true;
            }
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>