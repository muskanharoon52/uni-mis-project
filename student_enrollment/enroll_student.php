<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Get parameters
$student_id = isset($_GET['student']) ? $_GET['student'] : '';
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

// Fetch sections for dropdown
$sections_query = "SELECT s.*, p.program_name, sm.semester_name 
                   FROM sections s
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   LEFT JOIN semesters sm ON s.semester_id = sm.semester_id
                   WHERE s.status = 'Active'
                   ORDER BY p.program_name, s.section_name";
$sections_result = $conn->query($sections_query);
$sections = $sections_result ? $sections_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch students for dropdown
$students_query = "SELECT s.student_id, s.roll_no, u.full_name, p.program_name 
                   FROM students s
                   LEFT JOIN users u ON s.user_id = u.user_id
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   WHERE s.status = 'active'
                   ORDER BY u.full_name";
$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $section_id = (int)$_POST['section_id'] ?? 0;
    $semester_id = (int)$_POST['semester_id'] ?? 0;

    if (empty($student_id)) $error = "Please select a student";
    elseif ($section_id <= 0) $error = "Please select a section";
    elseif ($semester_id <= 0) $error = "Please select a semester";

    if (empty($error)) {
        // Check if student already enrolled in this section
        $check_query = "SELECT enrollment_id FROM student_enrollments 
                        WHERE student_id = ? AND section_id = ? AND semester_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("sii", $student_id, $section_id, $semester_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Student is already enrolled in this section!";
        } else {
            // Check if section has capacity
            $capacity_query = "SELECT capacity, enrolled_count FROM sections WHERE section_id = ?";
            $capacity_stmt = $conn->prepare($capacity_query);
            $capacity_stmt->bind_param("i", $section_id);
            $capacity_stmt->execute();
            $capacity_result = $capacity_stmt->get_result();
            $section_data = $capacity_result->fetch_assoc();
            $capacity_stmt->close();

            if ($section_data && $section_data['enrolled_count'] >= $section_data['capacity']) {
                $error = "Section is full! Capacity: " . $section_data['capacity'];
            } else {
                // Start transaction
                $conn->begin_transaction();

                try {
                    // Insert enrollment
                    $insert_query = "INSERT INTO student_enrollments 
                                    (student_id, section_id, semester_id, enrollment_date, status) 
                                    VALUES (?, ?, ?, CURDATE(), 'Enrolled')";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("sii", $student_id, $section_id, $semester_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();

                    // Update section enrolled count
                    $update_query = "UPDATE sections SET enrolled_count = enrolled_count + 1 WHERE section_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $section_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    // Update student's section and semester
                    $update_student = "UPDATE students SET section_id = ?, semester = ? WHERE student_id = ?";
                    $update_student_stmt = $conn->prepare($update_student);
                    $update_student_stmt->bind_param("iis", $section_id, $semester_id, $student_id);
                    $update_student_stmt->execute();
                    $update_student_stmt->close();

                    $conn->commit();
                    header("Location: index.php?success=Student enrolled successfully!");
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error enrolling student: " . $e->getMessage();
                }
            }
        }
        $check_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Enroll Student';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .enroll-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    @media (max-width: 768px) {
        .enroll-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="enroll-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-plus"></i> Enroll Student in Section</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Student <span class="required-star">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        <?php foreach($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>" 
                                <?= $student_id == $student['student_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['full_name']) ?> 
                                (<?= htmlspecialchars($student['student_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Section <span class="required-star">*</span></label>
                    <select name="section_id" class="form-select" required>
                        <option value="">Select Section</option>
                        <?php foreach($sections as $section): ?>
                            <option value="<?= $section['section_id'] ?>" 
                                <?= $section_id == $section['section_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['section_name']) ?> - 
                                <?= htmlspecialchars($section['program_name']) ?> 
                                (<?= htmlspecialchars($section['semester_name']) ?>)
                                [<?= $section['enrolled_count'] ?>/<?= $section['capacity'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Semester <span class="required-star">*</span></label>
                    <select name="semester_id" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php 
                        $semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
                        while($row = $semesters->fetch_assoc()): 
                        ?>
                            <option value="<?= $row['semester_id'] ?>">
                                <?= htmlspecialchars($row['semester_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Enroll Student
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>