<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$errors = [];
$success = '';

// Get ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid assignment ID");
    exit;
}

// ============================================
// CHECK AND CREATE SESSIONS IF EMPTY
// ============================================
$check_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions");
if ($check_sessions) {
    $session_count = $check_sessions->fetch_assoc();
    
    if ($session_count['count'] == 0) {
        $default_sessions = [
            ['Fall 2024', '2024-09-01', '2024-12-31', 'Active'],
            ['Spring 2025', '2025-01-15', '2025-05-30', 'Active'],
            ['Summer 2025', '2025-06-01', '2025-08-31', 'Active'],
            ['Fall 2025', '2025-09-01', '2025-12-31', 'Inactive']
        ];
        
        foreach ($default_sessions as $session) {
            $insert_session = $conn->prepare("INSERT INTO sessions (session_name, start_date, end_date, status) VALUES (?, ?, ?, ?)");
            $insert_session->bind_param("ssss", $session[0], $session[1], $session[2], $session[3]);
            $insert_session->execute();
            $insert_session->close();
        }
    }
}

// ============================================
// GET ASSIGNMENT DATA
// ============================================
$query = "SELECT * FROM teacher_courses WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: index.php?error=Assignment not found");
    exit;
}

// ============================================
// GET DROPDOWN DATA
// ============================================

// Get teachers
$teacher_query = "SELECT teacher_id as id, teacher_name as name, specialization 
                 FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teacher_result = $conn->query($teacher_query);
$teachers = $teacher_result ? $teacher_result->fetch_all(MYSQLI_ASSOC) : [];

// Get courses
$course_query = "SELECT course_id as id, course_code, course_name, credit_hours 
                 FROM courses ORDER BY course_code";
$course_result = $conn->query($course_query);
$courses = $course_result ? $course_result->fetch_all(MYSQLI_ASSOC) : [];

// Get semesters
$semester_query = "SELECT semester_id as id, semester_name as name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Get sessions
$session_query = "SELECT session_id as id, session_name as name, status FROM sessions ORDER BY session_name DESC";
$session_result = $conn->query($session_query);
$sessions = $session_result ? $session_result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
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

    // Check if assignment already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM teacher_courses 
                       WHERE teacher_id = ? AND course_id = ? 
                       AND semester_id = ? AND session_id = ? AND section = ?
                       AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iiiisi", $teacher_id, $course_id, $semester_id, $session_id, $section, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "This teacher is already assigned to this course in this semester, session, and section!";
        }
        $check_stmt->close();
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

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Edit Teacher Assignment';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .assignment-edit-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
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
    
    .session-status-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 5px;
    }
    
    .session-status-badge.Active {
        background: #d4edda;
        color: #155724;
    }
    
    .session-status-badge.Inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    @media (max-width: 768px) {
        .assignment-edit-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="assignment-edit-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit"></i> Edit Teacher Assignment</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($sessions)): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> 
                No sessions found. Please add sessions first.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class="fas fa-info-circle text-primary"></i> Assignment Details
                </h6>
                <div class="row">
                    <!-- Teacher -->
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="0">Select Teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>" 
                                    <?php echo $assignment['teacher_id'] == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?> 
                                    (<?php echo htmlspecialchars($t['specialization'] ?? 'N/A'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($teachers)): ?>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                No teachers available. <a href="add_teacher.php">Add a teacher</a> first.
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Course -->
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="0">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" 
                                    <?php echo $assignment['course_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                    (<?php echo htmlspecialchars($c['credit_hours']); ?> Credits)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Semester -->
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
                    
                    <!-- Session -->
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Session</label>
                        <select name="session_id" class="form-select" required>
                            <option value="0">Select Session</option>
                            <?php if (!empty($sessions)): ?>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" 
                                        <?php echo $assignment['session_id'] == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                        <span class="session-status-badge <?php echo $s['status']; ?>">
                                            <?php echo $s['status']; ?>
                                        </span>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No sessions available</option>
                            <?php endif; ?>
                        </select>
                        <?php if(empty($sessions)): ?>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                No sessions available. Please add sessions first.
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Section -->
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Section</label>
                        <input type="text" name="section" class="form-control" 
                               placeholder="e.g., A, B, C" 
                               value="<?php echo htmlspecialchars($assignment['section'] ?? ''); ?>" 
                               maxlength="5" required>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>