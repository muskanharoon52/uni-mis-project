<?php
// Courses/add.php - Add Course

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
if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ../dashboard.php');
    exit;
}

$conn = getConnection();
$errors = [];

// Get programs for dropdown
$program_query = "SELECT program_id as id, program_name as name FROM programs ORDER BY program_name";
$program_result = $conn->query($program_query);
$programs = $program_result ? $program_result->fetch_all(MYSQLI_ASSOC) : [];

// Get departments for dropdown (if needed)
$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters for dropdown (FIX: To satisfy Foreign Key constraint)
$sem_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$sem_result = $conn->query($sem_query);
$semesters = $sem_result ? $sem_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_name = trim($_POST['course_name'] ?? '');
    $credit_hours = (int)($_POST['credit_hours'] ?? 3);
    $program_id = (int)($_POST['program_id'] ?? 0);
    $department_id = !empty($_POST['department_id']) && $_POST['department_id'] != 0 ? (int)$_POST['department_id'] : NULL;
    // FIX: Get semester_id from dropdown
    $semester_id = !empty($_POST['semester_id']) && $_POST['semester_id'] != 0 ? (int)$_POST['semester_id'] : NULL; 
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_name)) $errors[] = "Course title is required";
    if ($credit_hours < 1 || $credit_hours > 6) $errors[] = "Credit hours must be between 1 and 6";
    if (empty($program_id) || $program_id == 0) $errors[] = "Program is required";
    
    // FIX: Add validation for Semester since your DB requires it
    if (empty($semester_id) || $semester_id == 0) $errors[] = "Semester is required";

    // Check if course code already exists
    if (empty($errors)) {
        $check_query = "SELECT course_id FROM courses WHERE course_code = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $course_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors[] = "Course code '$course_code' already exists!";
        }
        $check_stmt->close();
    }

    // Insert course
    if (empty($errors)) {
        // Check what columns exist in courses table
        $check_columns = "SHOW COLUMNS FROM courses";
        $columns_result = $conn->query($check_columns);
        $course_columns = [];
        if ($columns_result) {
            while ($col = $columns_result->fetch_assoc()) {
                $course_columns[] = $col['Field'];
            }
        }
        
        // Build insert query based on existing columns
        $fields = ['course_code', 'course_name', 'credit_hours', 'program_id'];
        $values = ['?', '?', '?', '?'];
        $params = [$course_code, $course_name, $credit_hours, $program_id];
        $types = "ssii";
        
        if (in_array('semester_id', $course_columns)) {
            $fields[] = 'semester_id';
            $values[] = '?';
            $params[] = $semester_id;
            $types .= "i";
        }
        
        if (in_array('department_id', $course_columns)) {
            $fields[] = 'department_id';
            $values[] = '?';
            $params[] = $department_id;
            $types .= "i";
        }
        
        if (in_array('description', $course_columns)) {
            $fields[] = 'description';
            $values[] = '?';
            $params[] = $description;
            $types .= "s";
        }
        
        // Check for created_at or created_date
        if (in_array('created_at', $course_columns)) {
            $fields[] = 'created_at';
            $values[] = 'NOW()';
        } elseif (in_array('created_date', $course_columns)) {
            $fields[] = 'created_date';
            $values[] = 'NOW()';
        }
        
        // Check for status column
        if (in_array('status', $course_columns)) {
            $fields[] = 'status';
            $values[] = '?';
            $params[] = 'Active';
            $types .= "s";
        }
        
        $insert_query = "INSERT INTO courses (" . implode(', ', $fields) . ") 
                        VALUES (" . implode(', ', $values) . ")";

        $stmt = $conn->prepare($insert_query);

        if ($stmt === false) {
            die("Error in insert query: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Course added successfully");
            exit;
        } else {
            $errors[] = "Error adding course: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Add Course';

// ============================================
// INCLUDE SIDEBAR
// ============================================
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-plus-circle"></i> Add New Course</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Course Information -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-info-circle text-primary"></i> Course Information
                </h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Course Code</label>
                        <input type="text" name="course_code" class="form-control" 
                               placeholder="e.g., CS101" 
                               value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>" 
                               required>
                        <small class="text-muted">Example: CS101, ENG102, MATH201</small>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="required-field">Course Title</label>
                        <input type="text" name="course_name" class="form-control" 
                               placeholder="e.g., Programming Fundamentals" 
                               value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>" 
                               required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Credit Hours</label>
                        <select name="credit_hours" class="form-select" required>
                            <option value="1" <?php echo ($_POST['credit_hours'] ?? 3) == 1 ? 'selected' : ''; ?>>1 Credit</option>
                            <option value="2" <?php echo ($_POST['credit_hours'] ?? 3) == 2 ? 'selected' : ''; ?>>2 Credits</option>
                            <option value="3" <?php echo ($_POST['credit_hours'] ?? 3) == 3 ? 'selected' : ''; ?>>3 Credits</option>
                            <option value="4" <?php echo ($_POST['credit_hours'] ?? 3) == 4 ? 'selected' : ''; ?>>4 Credits</option>
                            <option value="5" <?php echo ($_POST['credit_hours'] ?? 3) == 5 ? 'selected' : ''; ?>>5 Credits</option>
                            <option value="6" <?php echo ($_POST['credit_hours'] ?? 3) == 6 ? 'selected' : ''; ?>>6 Credits</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Enter course description (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Program & Department -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-building text-success"></i> Program & Department
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Program</label>
                        <select name="program_id" class="form-select" required>
                            <option value="0">Select Program</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" 
                                    <?php echo ($_POST['program_id'] ?? 0) == $prog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- NEW: Semester Dropdown to fix the foreign key error -->
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="0">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo $sem['id']; ?>" 
                                    <?php echo ($_POST['semester_id'] ?? 0) == $sem['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sem['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select the semester this course belongs to</small>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department (Optional)</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo ($_POST['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Optional: Select department if applicable</small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Course
                </button>
            </div>
        </form>
        
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>