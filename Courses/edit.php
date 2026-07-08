<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid course ID");
    exit;
}

// Get course data
$query = "SELECT * FROM courses WHERE course_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: index.php?error=Course not found");
    exit;
}

// Get departments for dropdown
$dept_query = "SELECT department_id as id, department_name as name FROM departments WHERE status = 'Active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = $dept_result ? $dept_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters for dropdown
$semester_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_title = trim($_POST['course_title'] ?? '');
    $credit_hours = (int)($_POST['credit_hours'] ?? 3);
    $department_id = (int)($_POST['department_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    // ✅ REMOVED description
    // $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_title)) $errors[] = "Course title is required";
    if ($credit_hours < 1 || $credit_hours > 6) $errors[] = "Credit hours must be between 1 and 6";
    if (empty($department_id) || $department_id == 0) $errors[] = "Department is required";
    if (empty($semester_id) || $semester_id == 0) $errors[] = "Semester is required";

    // Check if course code already exists for other course
    if (empty($errors)) {
        $check_query = "SELECT course_id FROM courses WHERE course_code = ? AND course_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $course_code, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors[] = "Course code '$course_code' already exists for another course!";
        }
        $check_stmt->close();
    }

    // Update course - ✅ REMOVED description from query
    if (empty($errors)) {
        $update_query = "UPDATE courses SET 
            course_code = ?,
            course_title = ?,
            credit_hours = ?,
            department_id = ?,
            semester_id = ?,
            status = ?
            WHERE course_id = ?";

        $stmt = $conn->prepare($update_query);

        if ($stmt === false) {
            die("Error in update query: " . $conn->error);
        }

        // ✅ REMOVED description from bind_param
        $stmt->bind_param(
            "ssiissi",  // 7 parameters instead of 8
            $course_code,
            $course_title,
            $credit_hours,
            $department_id,
            $semester_id,
            $status,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Course updated successfully");
            exit;
        } else {
            $errors[] = "Error updating course: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Edit Course';
include __DIR__ . '/../includes/navbar.php';
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
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-edit"></i> Edit Course</h4>
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
                           value="<?php echo htmlspecialchars($course['course_code']); ?>" 
                           required>
                    <small class="text-muted">Current: <strong><?php echo htmlspecialchars($course['course_code']); ?></strong></small>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="required-field">Course Title</label>
                    <input type="text" name="course_title" class="form-control" 
                           placeholder="e.g., Programming Fundamentals" 
                           value="<?php echo htmlspecialchars($course['course_title']); ?>" 
                           required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Credit Hours</label>
                    <select name="credit_hours" class="form-select" required>
                        <option value="1" <?php echo $course['credit_hours'] == 1 ? 'selected' : ''; ?>>1 Credit</option>
                        <option value="2" <?php echo $course['credit_hours'] == 2 ? 'selected' : ''; ?>>2 Credits</option>
                        <option value="3" <?php echo $course['credit_hours'] == 3 ? 'selected' : ''; ?>>3 Credits</option>
                        <option value="4" <?php echo $course['credit_hours'] == 4 ? 'selected' : ''; ?>>4 Credits</option>
                        <option value="5" <?php echo $course['credit_hours'] == 5 ? 'selected' : ''; ?>>5 Credits</option>
                        <option value="6" <?php echo $course['credit_hours'] == 6 ? 'selected' : ''; ?>>6 Credits</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?php echo $course['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $course['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <!-- ✅ REMOVED Description Field from HTML -->
                <!-- 
                <div class="col-12 mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Enter course description (optional)"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                </div>
                -->
            </div>
        </div>

        <!-- Department & Semester -->
        <div class="form-section">
            <h6 class="form-section-title">
                <i class="fas fa-building text-success"></i> Department & Semester
            </h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="required-field">Department</label>
                    <select name="department_id" class="form-select" required>
                        <option value="0">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                <?php echo $course['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Semester</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="0">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>" 
                                <?php echo $course['semester_id'] == $sem['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sem['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Course
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>