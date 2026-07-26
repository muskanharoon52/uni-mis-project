<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_name = trim($_POST['course_name'] ?? '');
    $credit_hours = (int)($_POST['credit_hours'] ?? 3);
    $program_id = (int)($_POST['program_id'] ?? 0);
    // FIX: Convert 0 to NULL for department_id
    $department_id = !empty($_POST['department_id']) && $_POST['department_id'] != 0 ? (int)$_POST['department_id'] : NULL;
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_name)) $errors[] = "Course title is required";
    if ($credit_hours < 1 || $credit_hours > 6) $errors[] = "Credit hours must be between 1 and 6";
    if (empty($program_id) || $program_id == 0) $errors[] = "Program is required";

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

    // Insert course - WITHOUT semester field
    if (empty($errors)) {
        $insert_query = "INSERT INTO courses (
            course_code, course_name, credit_hours, 
            program_id, department_id, description
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insert_query);

        if ($stmt === false) {
            die("Error in insert query: " . $conn->error);
        }

        // FIX: Changed bind_param types - "siiss" instead of "ssiiss"
        // The parameters are: string, string, integer, integer, string, string
        $stmt->bind_param(
            "ssiiss",  // s=string, s=string, i=integer, i=integer, s=string, s=string
            $course_code,
            $course_name,
            $credit_hours,
            $program_id,
            $department_id,  // This will be NULL if no department selected
            $description
        );

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
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Add Course';

// ============================================
// INCLUDE SIDEBAR
// ============================================
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .form-section-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f2f5;
    }
    
    .required-field::after {
        content: '*';
        color: #e74c3c;
        margin-left: 4px;
    }
    
    .courses-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    @media (max-width: 768px) {
        .courses-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<!-- ============================================ -->
<!-- CONTENT WITH MARGIN-LEFT TO PUSH RIGHT -->
<!-- ============================================ -->
<div class="courses-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
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
                    <div class="col-md-6 mb-3">
                        <label>Department</label>
                        <select name="department_id" class="form-select">
                            <!-- FIX: Changed value from "0" to "" -->
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>