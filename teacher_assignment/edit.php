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
    header("Location: index.php?error=Invalid assignment ID");
    exit;
}

// Get assignment data
$query = "SELECT * FROM teacher_courses WHERE id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: index.php?error=Assignment not found");
    exit;
}

// Get teachers for dropdown
$teacher_query = "SELECT teacher_id as id, teacher_name as name, designation 
                 FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teacher_result = $conn->query($teacher_query);
$teachers = $teacher_result ? $teacher_result->fetch_all(MYSQLI_ASSOC) : [];

// Get courses for dropdown
$course_query = "SELECT c.course_id as id, c.course_code, c.course_title, c.credit_hours, 
                 d.department_name
                 FROM courses c
                 LEFT JOIN departments d ON c.department_id = d.department_id
                 WHERE c.status = 'Active' 
                 ORDER BY c.course_code";
$course_result = $conn->query($course_query);
$courses = $course_result ? $course_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters for dropdown
$semester_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Get sessions for dropdown
$session_query = "SELECT session_id as id, session_name as name FROM sessions WHERE status = 'Active' ORDER BY session_name";
$session_result = $conn->query($session_query);
$sessions = $session_result ? $session_result->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $section = trim($_POST['section'] ?? '');

    // Validation
    if ($teacher_id <= 0) $errors[] = "Please select a teacher";
    if ($course_id <= 0) $errors[] = "Please select a course";
    if ($semester_id <= 0) $errors[] = "Please select a semester";
    if ($session_id <= 0) $errors[] = "Please select a session";
    if (empty($section)) $errors[] = "Section is required";

    // Check if assignment already exists for other record
    if (empty($errors)) {
        $check_query = "SELECT id FROM teacher_courses 
                       WHERE teacher_id = ? AND course_id = ? 
                       AND semester_id = ? AND session_id = ? 
                       AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        
        if ($check_stmt === false) {
            $errors[] = "Error preparing check query: " . $conn->error;
        } else {
            $check_stmt->bind_param("iiiii", $teacher_id, $course_id, $semester_id, $session_id, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "This teacher is already assigned to this course in this semester and session!";
            }
            $check_stmt->close();
        }
    }

    // Update assignment
    if (empty($errors)) {
        $update_query = "UPDATE teacher_courses SET 
                        teacher_id = ?,
                        course_id = ?,
                        semester_id = ?,
                        session_id = ?,
                        section = ?
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        
        if ($stmt === false) {
            $errors[] = "Error preparing update query: " . $conn->error;
        } else {
            // FIXED: 6 values (5 columns + 1 WHERE) with correct types
            // Types: i=integer, s=string
            // Values: teacher_id(i), course_id(i), semester_id(i), session_id(i), section(s), id(i)
            $stmt->bind_param("iiiisi", $teacher_id, $course_id, $semester_id, $session_id, $section, $id);
            
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?success=Assignment updated successfully");
                exit;
            } else {
                $errors[] = "Error updating assignment: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Edit Teacher Assignment';
include __DIR__ . '/../includes/navbar.php';
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
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-edit"></i> Edit Teacher Assignment</h4>
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
        <div class="form-section">
            <h6 class="form-section-title">
                <i class="fas fa-info-circle text-primary"></i> Assignment Details
            </h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="required-field">Teacher</label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="0">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" 
                                <?php echo $assignment['teacher_id'] == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?> 
                                (<?php echo htmlspecialchars($t['designation'] ?? 'N/A'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="0">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                <?php echo $assignment['course_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_title']); ?>
                                (<?php echo htmlspecialchars($c['credit_hours']); ?> Credits)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Semester</label>
                    <select name="semester_id" class="form-select" required>
                        <option value="0">Select Semester</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?php echo $s['id']; ?>" 
                                <?php echo $assignment['semester_id'] == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Session</label>
                    <select name="session_id" class="form-select" required>
                        <option value="0">Select Session</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?php echo $s['id']; ?>" 
                                <?php echo $assignment['session_id'] == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Section</label>
                    <input type="text" name="section" class="form-control" 
                           placeholder="e.g., A, B, C" 
                           value="<?php echo htmlspecialchars($assignment['section'] ?? ''); ?>" 
                           maxlength="5" required>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Assignment
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>