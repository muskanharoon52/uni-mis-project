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

// Get programs for dropdown
$program_query = "SELECT program_id as id, program_name as name FROM programs ORDER BY program_name";
$program_result = $conn->query($program_query);
$programs = $program_result ? $program_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = strtoupper(trim($_POST['course_code'] ?? ''));
    $course_name = trim($_POST['course_name'] ?? '');
    $credit_hours = (int)($_POST['credit_hours'] ?? 3);
    $program_id = (int)($_POST['program_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($course_code)) $errors[] = "Course code is required";
    if (empty($course_name)) $errors[] = "Course title is required";
    if ($credit_hours < 1 || $credit_hours > 6) $errors[] = "Credit hours must be between 1 and 6";
    if (empty($program_id) || $program_id == 0) $errors[] = "Program is required";
    if (empty($semester) || $semester == 0) $errors[] = "Semester is required";

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

    // Update course
    if (empty($errors)) {
        $update_query = "UPDATE courses SET 
            course_code = ?,
            course_name = ?,
            credit_hours = ?,
            program_id = ?,
            semester = ?,
            description = ?
            WHERE course_id = ?";

        $stmt = $conn->prepare($update_query);

        if ($stmt === false) {
            die("Error in update query: " . $conn->error);
        }

        $stmt->bind_param(
            "ssiissi",
            $course_code,
            $course_name,
            $credit_hours,
            $program_id,
            $semester,
            $description,
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
    
    /* FIX: Content container with margin-left to push content right */
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
                        <input type="text" name="course_name" class="form-control" 
                               placeholder="e.g., Programming Fundamentals" 
                               value="<?php echo htmlspecialchars($course['course_name']); ?>" 
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
                    <div class="col-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Enter course description (optional)"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Program & Semester -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-building text-success"></i> Program & Semester
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Program</label>
                        <select name="program_id" class="form-select" required>
                            <option value="0">Select Program</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" 
                                    <?php echo $course['program_id'] == $prog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="0">Select Semester</option>
                            <option value="1" <?php echo $course['semester'] == 1 ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="2" <?php echo $course['semester'] == 2 ? 'selected' : ''; ?>>Semester 2</option>
                            <option value="3" <?php echo $course['semester'] == 3 ? 'selected' : ''; ?>>Semester 3</option>
                            <option value="4" <?php echo $course['semester'] == 4 ? 'selected' : ''; ?>>Semester 4</option>
                            <option value="5" <?php echo $course['semester'] == 5 ? 'selected' : ''; ?>>Semester 5</option>
                            <option value="6" <?php echo $course['semester'] == 6 ? 'selected' : ''; ?>>Semester 6</option>
                            <option value="7" <?php echo $course['semester'] == 7 ? 'selected' : ''; ?>>Semester 7</option>
                            <option value="8" <?php echo $course['semester'] == 8 ? 'selected' : ''; ?>>Semester 8</option>
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>