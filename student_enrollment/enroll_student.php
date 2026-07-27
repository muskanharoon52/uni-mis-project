<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

// Check if logged in
if (!function_exists('isLoggedIn')) {
    die("isLoggedIn() function not found in auth.php");
}

if (!isLoggedIn()) {
    header('Location: ../modules/sso/login.php');
    exit;
}

$conn = getConnection();
$error = '';
$success = '';

// Get parameters
$student_id = isset($_GET['student']) ? $_GET['student'] : '';
$section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;

// Check what columns exist in student_enrollments table
$check_columns = "SHOW COLUMNS FROM student_enrollments";
$cols_result = mysqli_query($conn, $check_columns);
$enrollment_columns = [];
if ($cols_result) {
    while ($col = mysqli_fetch_assoc($cols_result)) {
        $enrollment_columns[] = $col['Field'];
    }
}

// Check available columns in student_enrollments
$has_course_id = in_array('course_id', $enrollment_columns);
$has_section_id = in_array('section_id', $enrollment_columns);
$has_student_id = in_array('student_id', $enrollment_columns);
$has_enrollment_date = in_array('enrollment_date', $enrollment_columns);
$has_status = in_array('status', $enrollment_columns);
$has_grade = in_array('grade', $enrollment_columns);

// ============================================
// FIXED: Check what columns exist in courses table
// ============================================
$check_course_columns = "SHOW COLUMNS FROM courses";
$course_cols_result = mysqli_query($conn, $check_course_columns);
$course_columns = [];
if ($course_cols_result) {
    while ($col = mysqli_fetch_assoc($course_cols_result)) {
        $course_columns[] = $col['Field'];
    }
}

// Build courses query based on existing columns
$course_select_fields = ['course_id'];
if (in_array('course_code', $course_columns)) {
    $course_select_fields[] = 'course_code';
}
if (in_array('course_name', $course_columns)) {
    $course_select_fields[] = 'course_name';
}
// Only add credits if it exists
if (in_array('credits', $course_columns)) {
    $course_select_fields[] = 'credits';
}

$courses_query = "SELECT " . implode(', ', $course_select_fields) . " 
                  FROM courses 
                  WHERE status = 'Active' 
                  ORDER BY course_name";
$courses_result = $conn->query($courses_query);
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch sections for dropdown
$sections_query = "SELECT s.*, p.program_name, c.course_name, c.course_code
                   FROM sections s
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   LEFT JOIN courses c ON s.course_id = c.course_id
                   WHERE s.status = 'Active'
                   ORDER BY p.program_name, s.section_name";
$sections_result = $conn->query($sections_query);
$sections = $sections_result ? $sections_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch students for dropdown - using admission_students table
$students_query = "SELECT s.student_id, s.full_name, s.student_name, p.program_name 
                   FROM admission_students s
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   WHERE s.status = 'active'
                   ORDER BY s.full_name";
$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $grade = isset($_POST['grade']) ? trim($_POST['grade']) : '';

    // Validation
    if (empty($student_id)) {
        $error = "Please select a student";
    } elseif ($course_id <= 0) {
        $error = "Please select a course";
    } elseif ($section_id <= 0) {
        $error = "Please select a section";
    }

    if (empty($error)) {
        // Check if student already enrolled in this course and section
        $check_query = "SELECT enrollment_id FROM student_enrollments 
                        WHERE student_id = ? AND course_id = ? AND section_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("sii", $student_id, $course_id, $section_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Student is already enrolled in this course and section!";
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
                    // Build insert query based on existing columns
                    $insert_fields = ['student_id', 'course_id', 'section_id'];
                    $insert_params = [$student_id, $course_id, $section_id];
                    $types = "sii";

                    if ($has_enrollment_date) {
                        $insert_fields[] = 'enrollment_date';
                        $insert_params[] = date('Y-m-d');
                        $types .= "s";
                    }

                    if ($has_status) {
                        $insert_fields[] = 'status';
                        $insert_params[] = 'Active';
                        $types .= "s";
                    }

                    if ($has_grade && !empty($grade)) {
                        $insert_fields[] = 'grade';
                        $insert_params[] = $grade;
                        $types .= "s";
                    }

                    $placeholders = implode(', ', array_fill(0, count($insert_fields), '?'));
                    $insert_query = "INSERT INTO student_enrollments (" . implode(', ', $insert_fields) . ") 
                                    VALUES (" . $placeholders . ")";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    if ($insert_stmt === false) {
                        throw new Exception("Error preparing insert: " . $conn->error);
                    }
                    
                    $insert_stmt->bind_param($types, ...$insert_params);
                    $insert_stmt->execute();
                    $insert_stmt->close();

                    // Update section enrolled count
                    $update_query = "UPDATE sections SET enrolled_count = enrolled_count + 1 WHERE section_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $section_id);
                    $update_stmt->execute();
                    $update_stmt->close();

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
        max-width: 700px;
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
    
    .enrollment-info {
        background: #e7f3ff;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #007bff;
        margin-bottom: 20px;
    }
    
    .section-capacity {
        font-size: 12px;
        color: #6c757d;
    }
    
    .capacity-full {
        color: #dc3545;
        font-weight: bold;
    }
    
    .capacity-available {
        color: #28a745;
        font-weight: bold;
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
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="enrollment-info">
                <i class="fas fa-info-circle text-primary"></i>
                <strong>Enrollment Instructions:</strong>
                <ul class="mb-0 mt-1">
                    <li>Select a student, course, and section</li>
                    <li>Student will be enrolled in the selected course and section</li>
                    <li>Section capacity will be checked before enrollment</li>
                </ul>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Student <span class="required-star">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>" 
                                <?= $student_id == $student['student_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['full_name'] ?? $student['student_name'] ?? 'Unknown') ?> 
                                (<?= htmlspecialchars($student['student_id']) ?>)
                                <?= !empty($student['program_name']) ? '- ' . htmlspecialchars($student['program_name']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select the student to enroll</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Course <span class="required-star">*</span></label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>">
                                <?php 
                                $course_display = '';
                                if (isset($course['course_code']) && !empty($course['course_code'])) {
                                    $course_display .= $course['course_code'] . ' - ';
                                }
                                $course_display .= $course['course_name'] ?? 'Unknown Course';
                                echo htmlspecialchars($course_display);
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select the course to enroll in</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Section <span class="required-star">*</span></label>
                    <select name="section_id" class="form-select" required>
                        <option value="">-- Select Section --</option>
                        <?php foreach($sections as $section): ?>
                            <?php 
                            $capacity_status = $section['enrolled_count'] >= $section['capacity'] ? 'full' : 'available';
                            $capacity_class = $capacity_status == 'full' ? 'capacity-full' : 'capacity-available';
                            ?>
                            <option value="<?= $section['section_id'] ?>" 
                                <?= $section_id == $section['section_id'] ? 'selected' : '' ?>
                                <?= $capacity_status == 'full' ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($section['section_name']) ?> - 
                                <?= htmlspecialchars($section['course_name'] ?? 'N/A') ?>
                                (<?= htmlspecialchars($section['program_name'] ?? 'N/A') ?>)
                                <span class="section-capacity <?= $capacity_class ?>">
                                    [<?= $section['enrolled_count'] ?>/<?= $section['capacity'] ?>]
                                    <?= $capacity_status == 'full' ? '🔴 FULL' : '✅ Available' ?>
                                </span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select the section (full sections are disabled)</div>
                </div>

                <?php if ($has_grade): ?>
                <div class="mb-3">
                    <label class="form-label">Grade (Optional)</label>
                    <select name="grade" class="form-select">
                        <option value="">-- No Grade --</option>
                        <option value="A">A</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B">B</option>
                        <option value="B-">B-</option>
                        <option value="C+">C+</option>
                        <option value="C">C</option>
                        <option value="C-">C-</option>
                        <option value="D">D</option>
                        <option value="F">F</option>
                        <option value="I">I (Incomplete)</option>
                        <option value="W">W (Withdrawal)</option>
                    </select>
                    <div class="form-text">Grade can be assigned later if not selected now</div>
                </div>
                <?php endif; ?>

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