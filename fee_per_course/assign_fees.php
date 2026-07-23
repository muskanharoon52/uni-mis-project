<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';
$course_id = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$semester_id = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;

// Get course fee
$fee_query = "SELECT * FROM course_fees WHERE course_id = ? AND semester_id = ? AND is_active = 1";
$fee_stmt = $conn->prepare($fee_query);
$fee_stmt->bind_param("ii", $course_id, $semester_id);
$fee_stmt->execute();
$fee_result = $fee_stmt->get_result();
$fee_data = $fee_result->fetch_assoc();
$fee_stmt->close();

if (!$fee_data) {
    header("Location: index.php?error=No fee configured for this course and semester");
    exit;
}

// Get students enrolled in this course
$students_query = "SELECT DISTINCT s.student_id, s.roll_no, u.full_name 
                   FROM students s
                   LEFT JOIN users u ON s.user_id = u.user_id
                   LEFT JOIN student_courses sc ON s.student_id = sc.student_id
                   WHERE sc.course_id = ? AND s.status = 'active'
                   ORDER BY u.full_name";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $course_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result->fetch_all(MYSQLI_ASSOC);
$students_stmt->close();

// Check if fees already assigned
$assigned_query = "SELECT COUNT(*) as count FROM student_course_fees 
                   WHERE course_id = ? AND semester_id = ?";
$assigned_stmt = $conn->prepare($assigned_query);
$assigned_stmt->bind_param("ii", $course_id, $semester_id);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$assigned = $assigned_result->fetch_assoc();
$assigned_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_ids = $_POST['student_ids'] ?? [];
    $fee_amount = (float)$_POST['fee_amount'];
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));

    if (empty($student_ids)) {
        $error = "Please select at least one student";
    } else {
        $success_count = 0;
        $fail_count = 0;

        foreach ($student_ids as $student_id) {
            // Check if fee already exists for this student
            $check_query = "SELECT id FROM student_course_fees 
                            WHERE student_id = ? AND course_id = ? AND semester_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("sii", $student_id, $course_id, $semester_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing
                $update_query = "UPDATE student_course_fees SET 
                                 fee_amount = ?, due_date = ?, status = 'Unpaid' 
                                 WHERE student_id = ? AND course_id = ? AND semester_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("dssii", $fee_amount, $due_date, $student_id, $course_id, $semester_id);
                if ($update_stmt->execute()) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
                $update_stmt->close();
            } else {
                // Insert new
                $insert_query = "INSERT INTO student_course_fees 
                                (student_id, course_id, semester_id, fee_amount, due_date, status) 
                                VALUES (?, ?, ?, ?, ?, 'Unpaid')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("siids", $student_id, $course_id, $semester_id, $fee_amount, $due_date);
                if ($insert_stmt->execute()) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }

        if ($fail_count == 0) {
            header("Location: index.php?success=Fees assigned to $success_count students successfully!");
            exit;
        } else {
            $error = "Assigned to $success_count students, failed for $fail_count students.";
        }
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Assign Course Fees';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .assign-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .student-row {
        padding: 8px 15px;
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.2s;
    }
    
    .student-row:hover {
        background: #f8f9ff;
    }
    
    .student-row:last-child {
        border-bottom: none;
    }
    
    .student-row input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }
    
    .student-row label {
        cursor: pointer;
        margin-left: 8px;
    }
    
    .btn-assign {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .fee-info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    
    @media (max-width: 768px) {
        .assign-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="assign-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-plus"></i> Assign Course Fees</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <!-- Fee Info -->
            <div class="fee-info-box">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Course:</strong> <?= htmlspecialchars($fee_data['course_id']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Semester:</strong> <?= htmlspecialchars($fee_data['semester_id']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Fee Amount:</strong> Rs. <?= number_format($fee_data['fee_amount'], 2) ?>
                    </div>
                </div>
            </div>

            <?php if ($assigned['count'] > 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Fees are already assigned to <?= $assigned['count'] ?> students. 
                    Updating will overwrite existing fee records.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="fee_amount" value="<?= $fee_data['fee_amount'] ?>">

                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label">Select Students (<?= count($students) ?>)</label>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll(true)">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll(false)">
                                <i class="fas fa-times"></i> Deselect All
                            </button>
                        </div>
                    </div>
                    
                    <div class="student-list mt-2">
                        <?php if (!empty($students)): ?>
                            <?php foreach($students as $student): ?>
                                <div class="student-row d-flex align-items-center">
                                    <input type="checkbox" name="student_ids[]" 
                                           value="<?= $student['student_id'] ?>" 
                                           id="student_<?= $student['student_id'] ?>"
                                           <?= $assigned['count'] > 0 ? 'checked' : '' ?>>
                                    <label for="student_<?= $student['student_id'] ?>">
                                        <strong><?= htmlspecialchars($student['full_name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($student['student_id']) ?></small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No students enrolled in this course.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-assign" <?= empty($students) ? 'disabled' : '' ?>>
                        <i class="fas fa-save"></i> Assign Fees
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
    function selectAll(select) {
        document.querySelectorAll('input[name="student_ids[]"]').forEach(function(checkbox) {
            checkbox.checked = select;
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>