<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// ============================================
// CHECK AND CREATE SESSIONS IF EMPTY
// ============================================
$check_sessions = $conn->query("SELECT COUNT(*) as count FROM sessions");
if ($check_sessions) {
    $session_count = $check_sessions->fetch_assoc();
    
    if ($session_count['count'] == 0) {
        // Insert default sessions
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
        
        // Show success message
        echo '<div class="alert alert-success alert-dismissible fade show" style="margin-left:250px; margin-top:10px;">
                <i class="fas fa-check-circle"></i> 
                Default sessions have been created automatically!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Fetch all required data for dropdowns
$teachers_query = "SELECT teacher_id, teacher_name, teacher_code FROM teachers WHERE status = 'Active' ORDER BY teacher_name";
$teachers_result = $conn->query($teachers_query);
$teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];

$courses_query = "SELECT course_id, course_code, course_name, credit_hours FROM courses ORDER BY course_code";
$courses_result = $conn->query($courses_query);
$courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];

$semesters_query = "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name";
$semesters_result = $conn->query($semesters_query);
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch sessions - show all sessions
$sessions_query = "SELECT session_id, session_name, status FROM sessions ORDER BY session_name DESC";
$sessions_result = $conn->query($sessions_query);
$sessions = $sessions_result ? $sessions_result->fetch_all(MYSQLI_ASSOC) : [];

// Debug: Check if sessions are fetched
if (empty($sessions)) {
    // Try without status filter
    $sessions_query = "SELECT session_id, session_name, status FROM sessions ORDER BY session_name DESC";
    $sessions_result = $conn->query($sessions_query);
    $sessions = $sessions_result ? $sessions_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $section = trim($_POST['section'] ?? 'A');
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if ($teacher_id <= 0 || $course_id <= 0 || $semester_id <= 0 || $session_id <= 0) {
        $error = 'Please select teacher, course, semester, and session.';
    } else {
        // Check if assignment already exists
        $check_sql = "SELECT id FROM teacher_courses 
                      WHERE teacher_id = ? AND course_id = ? AND semester_id = ? AND session_id = ? AND section = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iiiis", $teacher_id, $course_id, $semester_id, $session_id, $section);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'This assignment already exists for the same teacher, course, semester, session, and section.';
        } else {
            // Insert assignment using teacher_courses table
            $insert_sql = "INSERT INTO teacher_courses 
                          (teacher_id, course_id, semester_id, session_id, section, is_primary, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiiisis", $teacher_id, $course_id, $semester_id, $session_id, $section, $is_primary, $status);
            
            if ($insert_stmt->execute()) {
                header("Location: index.php?success=Assignment created successfully!");
                exit();
            } else {
                $error = "Error creating assignment: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Create Teacher Assignment';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .assignment-form-content {
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
    
    .form-container .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-container .text-danger {
        color: #e74c3c !important;
    }
    
    .form-container .form-control:focus,
    .form-container .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    .btn-submit {
        padding: 10px 30px;
        font-weight: 600;
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
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
    
    @media (max-width: 768px) {
        .assignment-form-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="assignment-form-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-plus-circle"></i> Create Teacher Assignment</h4>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> 
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Debug: Show session count -->
        <?php if (empty($sessions)): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> 
                No sessions found in the database. Please add sessions first.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Assignment Form -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="row">
                    <!-- Teacher -->
                    <div class="col-md-6 mb-3">
                        <label for="teacher_id" class="form-label">
                            Teacher <span class="required-star">*</span>
                        </label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php if (!empty($teachers)): ?>
                                <?php foreach($teachers as $teacher): ?>
                                    <option value="<?= $teacher['teacher_id'] ?>" 
                                        <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($teacher['teacher_name']) ?> (<?= htmlspecialchars($teacher['teacher_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No teachers available</option>
                            <?php endif; ?>
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
                        <label for="course_id" class="form-label">
                            Course <span class="required-star">*</span>
                        </label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php if (!empty($courses)): ?>
                                <?php foreach($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" 
                                        <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                                        (<?= $course['credit_hours'] ?> Credits)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No courses available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Semester -->
                    <div class="col-md-6 mb-3">
                        <label for="semester_id" class="form-label">
                            Semester <span class="required-star">*</span>
                        </label>
                        <select class="form-select" id="semester_id" name="semester_id" required>
                            <option value="">Select Semester</option>
                            <?php if (!empty($semesters)): ?>
                                <?php foreach($semesters as $semester): ?>
                                    <option value="<?= $semester['semester_id'] ?>" 
                                        <?php echo (isset($_POST['semester_id']) && $_POST['semester_id'] == $semester['semester_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($semester['semester_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No semesters available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Session -->
                    <div class="col-md-6 mb-3">
                        <label for="session_id" class="form-label">
                            Session <span class="required-star">*</span>
                        </label>
                        <select class="form-select" id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            <?php if (!empty($sessions)): ?>
                                <?php foreach($sessions as $session): ?>
                                    <option value="<?= $session['session_id'] ?>" 
                                        <?php echo (isset($_POST['session_id']) && $_POST['session_id'] == $session['session_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($session['session_name']) ?>
                                        <span class="session-status-badge <?= $session['status'] ?>">
                                            <?= $session['status'] ?>
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
                                No sessions available. Please run the SQL to add sessions.
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Section -->
                    <div class="col-md-6 mb-3">
                        <label for="section" class="form-label">
                            Section <span class="required-star">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="section" 
                               name="section" 
                               placeholder="e.g., A" 
                               required
                               value="<?= htmlspecialchars($_POST['section'] ?? 'A') ?>">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Section letter (A, B, C, etc.)
                        </small>
                    </div>
                    
                    <!-- Primary Instructor -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Assignment Type</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" 
                                   <?php echo (isset($_POST['is_primary']) && $_POST['is_primary']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_primary">
                                <i class="fas fa-star text-warning"></i> Mark as Primary Instructor
                            </label>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Check if this teacher is the primary instructor for this course
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Active assignments will be visible in the system
                    </small>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> Create Assignment
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>