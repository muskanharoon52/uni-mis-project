<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$students = [];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch courses
$courses = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");

// If course selected, fetch students enrolled in that course
if ($course_id > 0) {
    $students_query = "SELECT DISTINCT s.student_id, s.roll_no, u.full_name 
                       FROM students s
                       LEFT JOIN users u ON s.user_id = u.user_id
                       LEFT JOIN student_courses sc ON s.student_id = sc.student_id
                       WHERE sc.course_id = ? AND s.status = 'active'
                       ORDER BY u.full_name";
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

    if (empty($error)) {
        $success_count = 0;
        $error_count = 0;

        foreach ($statuses as $student_id => $status) {
            // Check if attendance already exists
            $check_query = "SELECT attendance_id FROM attendance 
                            WHERE student_id = ? AND course_id = ? AND date = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("sis", $student_id, $course_id, $attendance_date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                $remark = $remarks[$student_id] ?? '';

                if ($check_result->num_rows > 0) {
                    // Update existing record - without faculty_id
                    $row = $check_result->fetch_assoc();
                    $update_query = "UPDATE attendance SET status = ?, remark = ? 
                                     WHERE attendance_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    if ($update_stmt) {
                        $update_stmt->bind_param("ssi", $status, $remark, $row['attendance_id']);
                        if ($update_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        $update_stmt->close();
                    }
                } else {
                    // Insert new record - without faculty_id
                    $insert_query = "INSERT INTO attendance 
                                    (student_id, course_id, date, status, remark) 
                                    VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sisss", $student_id, $course_id, $attendance_date, 
                                                $status, $remark);
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

<style>
    .mark-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .student-row {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.2s;
    }
    
    .student-row:hover {
        background: #f8f9ff;
    }
    
    .student-row:last-child {
        border-bottom: none;
    }
    
    .status-radio {
        margin-right: 15px;
    }
    
    .status-radio label {
        margin-left: 5px;
        cursor: pointer;
    }
    
    .status-radio input[type="radio"] {
        cursor: pointer;
    }
    
    .status-present { color: #27ae60; }
    .status-absent { color: #e74c3c; }
    .status-late { color: #f39c12; }
    .status-excused { color: #3498db; }
    
    .btn-mark {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .mark-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .student-row {
            padding: 10px;
        }
        
        .status-radio {
            margin-right: 8px;
        }
    }
</style>

<div class="mark-content">
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
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="setAllStatus('present')">
                                <i class="fas fa-check"></i> All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAllStatus('absent')">
                                <i class="fas fa-times"></i> All Absent
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAllStatus('late')">
                                <i class="fas fa-clock"></i> All Late
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="setAllStatus('excused')">
                                <i class="fas fa-check-circle"></i> All Excused
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
                                           value="present" id="present_<?= $student['student_id'] ?>" checked>
                                    <label for="present_<?= $student['student_id'] ?>" class="status-present">
                                        <i class="fas fa-check-circle"></i> Present
                                    </label>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="absent" id="absent_<?= $student['student_id'] ?>">
                                    <label for="absent_<?= $student['student_id'] ?>" class="status-absent">
                                        <i class="fas fa-times-circle"></i> Absent
                                    </label>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="late" id="late_<?= $student['student_id'] ?>">
                                    <label for="late_<?= $student['student_id'] ?>" class="status-late">
                                        <i class="fas fa-clock"></i> Late
                                    </label>
                                </div>
                                <div class="status-radio">
                                    <input type="radio" name="status[<?= $student['student_id'] ?>]" 
                                           value="excused" id="excused_<?= $student['student_id'] ?>">
                                    <label for="excused_<?= $student['student_id'] ?>" class="status-excused">
                                        <i class="fas fa-check-circle"></i> Excused
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
            <?php endif; ?>
        </div>
        
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